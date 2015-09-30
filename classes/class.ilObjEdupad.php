<?php
/* Copyright (c) 2011 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once('./Services/Repository/classes/class.ilObjectPlugin.php');

/**
 * Class ilObjEdupad
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 * @author Martin Studer <ms@studer-raimann.ch>
 *
 * $Id$
 */
class ilObjEdupad extends ilObjectPlugin {

	/**
	 * @var bool
	 */
	protected $scrolling = false;
	/**
	 * @var string
	 */
	protected $teamhost = '';
	/**
	 * @var string
	 */
	protected $subdomain = '';
	/**
	 * @var string
	 */
	protected $teamuser = '';
	/**
	 * @var string
	 */
	protected $teampassword = '';
	/**
	 * @var string
	 */
	protected $iliaspadpassword = '';
	/**
	 * @var string
	 */
	protected $httpprotocol = '';
	/**
	 * @var string
	 */
	protected $padid = '';
	/**
	 * @var string
	 */
	protected $padtoken = '';
	/**
	 * @var string
	 */
	protected $proxy_host = '';
	/**
	 * @var string
	 */
	protected $proxy_port = '';
	/**
	 * @var bool
	 */
	protected $use_proxy = false;
	/**
	 * @var string
	 */
	protected $default_text = '';


	/**
	 * @param int $a_ref_id
	 */
	public function __construct($a_ref_id = 0) {
		$this->readIniFile();
		parent::__construct($a_ref_id);
	}


	protected function readIniFile() {
		$ini = new ilIniFile('./Customizing/global/plugins/Services/Repository/RepositoryObject/Edupad/edupad.ini.php');
		$ini->read();
		$this->setTeamhost($ini->readVariable('hosts', 'teamhost'));
		$this->setTeamsubdomain($ini->readVariable('hosts', 'teamsubdomain'));
		$this->setTeamuser($ini->readVariable('accounts', 'teamuser'));
		$this->setTeampassword($ini->readVariable('accounts', 'teampassword'));
		if ($ini->readVariable('hosts', 'usehttps') == 1) {
			$this->setHttpProtocol('https://');
		} else {
			$this->setHttpProtocol('http://');
		}
		$this->setProxyHost($ini->readVariable('proxy', 'proxyhost'));
		$this->setProxyPort($ini->readVariable('proxy', 'proxyport'));
		$this->setUseProxy((bool)$ini->readVariable('proxy', 'useproxy'));
		$this->setIliaspadpassword($ini->readVariable('accounts', 'iliaspadpassword'));
		$this->setDefaultText($ini->readVariable('default', 'defaulttext'));
		$this->setScrolling((bool)$ini->readVariable('template', 'scrolling'));
	}


	final function initType() {
		$this->setType('xpad');
	}


	public function doCreate() {
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;
		$pad_id = $this->createEdupad();
		$ilDB->manipulate('INSERT INTO rep_robj_xpad_data ' . '(id, pad_id) VALUES (' . $ilDB->quote($this->getId(), 'integer') . ','
			. $ilDB->quote($pad_id, 'text') . ')');
	}


	public function doRead() {
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;
		$this->readIniFile();
		$set = $ilDB->query('SELECT pad_id FROM rep_robj_xpad_data ' . ' WHERE id = ' . $ilDB->quote($this->getId(), 'integer'));
		while ($rec = $ilDB->fetchObject($set)) {
			$this->setPadId($rec->pad_id);
		}
	}


	public function doDelete() {
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;
		$ilDB->manipulate("DELETE FROM rep_robj_xpad_data WHERE " . " id = " . $ilDB->quote($this->getId(), "integer"));
	}


	/**
	 * @param $a_target_id
	 * @param $a_copy_id
	 * @param $new_obj
	 *
	 * @description Wird bei dieser Version noch nicht angeboten. Hier müssten die Pads auch auf dem eduPad-Server geklont werden!
	 */
	public function doClone($a_target_id, $a_copy_id, $new_obj) {
		$new_obj->setPadId($this->getPadId());
		$new_obj->update();
	}


	/**
	 * @param $pad_id
	 *
	 * @return array
	 */
	public function doSearch($pad_id) {
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;
		$set = $ilDB->query('SELECT id FROM rep_robj_xpad_data WHERE pad_id = ' . $ilDB->quote($pad_id, 'text'));
		while ($rec = $ilDB->fetchObject($set)) {
			$ilObjects[] = $rec->id;
		}

		return $ilObjects;
	}


	/**
	 * @return string
	 */
	protected function createEdupad() {
		$ch = curl_init();
		# for debugging
		curl_setopt($ch, CURLOPT_HEADER, true);
		# Return-Werte nicht direkt ausgeben
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		# parse cookies
		curl_setopt($ch, CURLOPT_COOKIEFILE, '/dev/null');
		# follow all redirects
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		# proxy settings
		if ($this->getUseProxy()) {
			curl_setopt($ch, CURLOPT_PROXY, $this->getProxyHost());
			curl_setopt($ch, CURLOPT_PROXYPORT, $this->getProxyPort());
			curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, true);
		}
		# first, post to get a cookie
		curl_setopt($ch, CURLOPT_URL, $this->getHttpProtocol() . $this->getTeamhost() . '/ep/account/sign-in');
		curl_exec($ch);

		# login;  Pads werden immer mit einem globalen und allgemeinem Teamaccount erzeugt.
		curl_setopt($ch, CURLOPT_URL, $this->getHttpProtocol() . $this->getTeamhost() . '/ep/account/sign-in');
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, 'email=' . urlencode($this->getTeamuser()) . '&password=' . urlencode($this->getTeampassword()));
		curl_exec($ch);
		curl_setopt($ch, CURLOPT_URL, $this->getHttpProtocol() . $this->getTeamhost() . '/ep/pad/newpad?defaultText=lorem');
		curl_setopt($ch, CURLOPT_POST, true);

		$result = curl_exec($ch);

		//Auf der HTML-Seite, welche zurückgeliefert wird die globalPadId (z.B. 2$10) suchen und die padId bestimmen.
		$tmpStr = explode('"globalPadId":"', $result);
		$tmpStr = explode('"},"colorPalette"', $tmpStr[1]);
		$globalPadId = $tmpStr[0];
		$tmpStr = explode('$', $globalPadId);
		$pad_id = $tmpStr[1];
		$v = $tmpStr[0];

		return $pad_id;
	}


	/**
	 * @param ilObjUser $ilUser
	 */
	public function addUser(ilObjUser $ilUser) {
		$ch = curl_init();
		# for debugging
		curl_setopt($ch, CURLOPT_HEADER, true);
		# Return-Werte nicht direkt ausgeben
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		# parse cookies
		curl_setopt($ch, CURLOPT_COOKIEFILE, '/dev/null');
		# follow all redirects
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		# proxy settings
		if ($this->getUseProxy()) {
			curl_setopt($ch, CURLOPT_PROXY, $this->getProxyHost());
			curl_setopt($ch, CURLOPT_PROXYPORT, $this->getProxyPort());
			curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, true);
		}
		# first, post to get a cookie
		curl_setopt($ch, CURLOPT_URL, $this->getHttpProtocol() . $this->getTeamhost() . '/ep/account/sign-in');
		$result = curl_exec($ch);
		# login
		curl_setopt($ch, CURLOPT_URL, $this->getHttpProtocol() . $this->getTeamhost() . '/ep/account/sign-in');
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, 'email=' . urlencode($this->getTeamuser()) . '&password=' . urlencode($this->getTeampassword()));
		$result = curl_exec($ch);
		curl_setopt($ch, CURLOPT_URL, $this->getHttpProtocol() . $this->getTeamhost() . '/ep/admin/account-manager/new');
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS,
			'email=' . $this->getIliasUser($ilUser) . '@' . $this->getTeamhost() . '&fullName=' . $ilUser->getFirstname() . '+'
			. $ilUser->getLastname() . '&tempPass=B9T7XK&btn=Create+Account');
		$result = curl_exec($ch);
	}


	/**
	 * @param ilObjUser $ilUser
	 * @param           $edupad_user_id
	 */
	public function updateUser(ilObjUser $ilUser, $edupad_user_id) {
		$ch = curl_init();
		# for debugging
		curl_setopt($ch, CURLOPT_HEADER, true);
		# Return-Werte nicht direkt ausgeben
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		# parse cookies
		curl_setopt($ch, CURLOPT_COOKIEFILE, '/dev/null');
		# follow all redirects
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
		# proxy settings
		if ($this->getUseProxy()) {
			curl_setopt($ch, CURLOPT_PROXY, $this->getProxyHost());
			curl_setopt($ch, CURLOPT_PROXYPORT, $this->getProxyPort());
			curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, true);
		}
		# first, post to get a cookie
		curl_setopt($ch, CURLOPT_URL, $this->getHttpProtocol() . $this->getTeamhost() . '/ep/account/sign-in');
		$result = curl_exec($ch);
		# login
		curl_setopt($ch, CURLOPT_URL, $this->getHttpProtocol() . $this->getTeamhost() . '/ep/account/sign-in');
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, 'email=' . urlencode($this->getTeamuser()) . '&password=' . urlencode($this->getTeampassword()));
		$result = curl_exec($ch);
		curl_setopt($ch, CURLOPT_URL, $this->getHttpProtocol() . $this->getTeamhost() . '/ep/admin/account-manager/account/' . $edupad_user_id);
		curl_setopt($ch, CURLOPT_HTTPGET, true);
		$result = curl_exec($ch);
		curl_setopt($ch, CURLOPT_URL, $this->getHttpProtocol() . $this->getTeamhost() . '/ep/admin/account-manager/account/' . $edupad_user_id);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS,
			'newEmail=' . $this->getIliasUser($ilUser) . '@' . $this->getTeamhost() . '&newFullName=' . $ilUser->getFirstname() . '+'
			. $ilUser->getLastname() . '&btn=Save');
		$result = curl_exec($ch);
	}


	/**
	 * @param ilObjUser $ilUser
	 *
	 * @return string
	 */
	public function getEdupadHash(ilObjUser $ilUser) {
		return md5($this->getPadToken() . $this->getIliaspadpassword() . $this->getPadId() . $this->getIliasUser($ilUser) . '@' . $this->GetTeamhost()
			. $this->getTeamsubdomain());
	}


	/**
	 * @param ilObjUser $ilUser
	 *
	 * @return mixed
	 */
	public function getIliasUser(ilObjUser $ilUser) {
		return str_replace('@', '-at-', $ilUser->getLogin());
	}


	/**
	 * Set Teamhost
	 *
	 * @param  string $a_val Teamhost e.g plugin.learnonline.iliaspad.ch
	 */
	public function setTeamhost($a_val) {
		$this->teamhost = $a_val;
	}


	/**
	 * Get Teamhost
	 *
	 * @return string Teamhost e.g plugin.learnonline.iliaspad.ch
	 */
	public function getTeamhost() {
		return $this->teamhost;
	}


	/**
	 * Set Teamsubdomain
	 *
	 * @param  string $a_val Teamsubdomain e.g devmst3
	 */
	public function setTeamsubdomain($a_val) {
		$this->subdomain = $a_val;
	}


	/**
	 * Get Teamsubdomain
	 *
	 * @return string Teamhost e.g devmst3
	 */
	public function getTeamsubdomain() {
		return $this->subdomain;
	}


	/**
	 * Set Teamuser
	 *
	 * @param  string $a_val Teamuser e.g. admin@devmst3.iliaspad.ch
	 */
	public function setTeamuser($a_val) {
		$this->teamuser = $a_val;
	}


	/**
	 * Get Teamuser
	 *
	 * @return string  Teamuser
	 */
	public function getTeamuser() {
		return $this->teamuser;
	}


	/**
	 * Set Teampassword
	 *
	 * @param  string $a_val Teampassword e.g. K4Jdh82f, used for login at edupad and creating pads
	 */
	public function setTeampassword($a_val) {
		$this->teampassword = $a_val;
	}


	/**
	 * Get Teampassword
	 *
	 * @return string  Teampassword
	 */
	public function getTeampassword() {
		return $this->teampassword;
	}


	/**
	 * Set Iliaspadpassword
	 *
	 * @param  string $a_val Iliaspadpassword e.g. K4Jdh82f, used for creating Token
	 */
	public function setIliaspadpassword($a_val) {
		$this->iliaspadpassword = $a_val;
	}


	/**
	 * Get Iliaspadpassword
	 *
	 * @return string  Iliaspadpassword
	 */
	public function getIliaspadpassword() {
		return $this->iliaspadpassword;
	}


	/**
	 * Set Usehttps
	 *
	 * @param  string $a_val 1,0
	 */
	public function setHttpProtocol($a_val) {
		$this->httpprotocol = $a_val;
	}


	/**
	 * Get Usehttps
	 *
	 * @return string  Iliaspadpassword
	 */
	public function getHttpProtocol() {
		return $this->httpprotocol;
	}


	/**
	 * Set padid
	 *
	 * @param    string $a_val PadId e.g. 12, PadIds of pro Pads are normally integer values
	 */
	public function setPadId($a_val) {
		$this->padid = $a_val;
	}


	/**
	 * Get padid
	 *
	 * @return    string  PadId
	 */
	public function getPadId() {
		return $this->padid;
	}


	/**
	 * Set PadToken
	 *
	 * Padtoken wird von eduPad als Cookie gesetzt und bei jedem Aufruf in der URL übergeben.
	 *
	 * @param  string $a_val padtoken
	 */
	public function setPadToken($a_val) {
		$this->padtoken = $a_val;
	}


	/**
	 * Get PadToken
	 *
	 * @return string  padtoken
	 */
	public function getPadToken() {
		return $this->padtoken;
	}


	/**
	 * @param $status
	 */
	public function setUseProxy($status) {
		$this->use_proxy = (bool)$status;
	}


	/**
	 * @return mixed
	 */
	public function getUseProxy() {
		return $this->use_proxy;
	}


	/**
	 * @param $host
	 */
	public function setProxyHost($host) {
		$this->proxy_host = $host;
	}


	/**
	 * @return mixed
	 */
	public function getProxyHost() {
		return $this->proxy_host;
	}


	/**
	 * @param $port
	 */
	public function setProxyPort($port) {
		$this->proxy_port = $port;
	}


	/**
	 * @return mixed
	 */
	public function getProxyPort() {
		return $this->proxy_port;
	}


	/**
	 * @param $default_text
	 */
	public function setDefaultText($default_text) {
		$this->default_text = $default_text;
	}


	/**
	 * @return mixed
	 */
	public function getDefaultText() {
		return $this->default_text;
	}


	/**
	 * @param boolean $scrolling
	 */
	public function setScrolling($scrolling) {
		$this->scrolling = $scrolling;
	}


	/**
	 * @return boolean
	 */
	public function getScrolling() {
		return $this->scrolling;
	}
}

?>
