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
	 * @param int $a_ref_id
	 */
	public function __construct($a_ref_id = 0) {
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;
		parent::__construct($a_ref_id);
		$this->db = $ilDB;
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
		$pad_id = $this->createEdupad();
		$this->db->manipulate('INSERT INTO rep_robj_xpad_data ' . '(id, pad_id) VALUES ('
			. $this->db->quote($this->getId(), 'integer') . ',' . $this->db->quote($pad_id, 'text') . ')');
	}


	public function doRead() {
		$set = $this->db->query('SELECT pad_id FROM rep_robj_xpad_data ' . ' WHERE id = '
			. $this->db->quote($this->getId(), 'integer'));
		while ($rec = $this->db->fetchObject($set)) {
			$this->setPadId($rec->pad_id);
		}
	}


	public function doDelete() {
		$this->db->manipulate("DELETE FROM rep_robj_xpad_data WHERE " . " id = "
			. $this->db->quote($this->getId(), "integer"));
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
	function doSearch($pad_id) {
		$set = $this->db->query('SELECT id FROM rep_robj_xpad_data WHERE pad_id = '
			. $this->db->quote($pad_id, 'text'));
		while ($rec = $this->db->fetchObject($set)) {
			$ilObjects[] = $rec->id;
		}

		return $ilObjects;
	}


	/**
	 * @return string
	 */
	private function createEdupad() {
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
		# login;  Pads werden immer mit einem globalen und allgemeinem Teamaccount erzeugt.
		curl_setopt($ch, CURLOPT_URL, $this->getHttpProtocol() . $this->getTeamhost() . '/ep/account/sign-in');
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS,
			'email=' . urlencode($this->getTeamuser()) . '&password=' . urlencode($this->getTeampassword()));
		$result = curl_exec($ch);
		curl_setopt($ch, CURLOPT_URL,
			$this->getHttpProtocol() . $this->getTeamhost() . '/ep/pad/newpad?defaultText=lorem');
		curl_setopt($ch, CURLOPT_POST, true);
		//curl_setopt($ch, CURLOPT_POSTFIELDS, '');
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
		curl_setopt($ch, CURLOPT_POSTFIELDS,
			'email=' . urlencode($this->getTeamuser()) . '&password=' . urlencode($this->getTeampassword()));
		$result = curl_exec($ch);
		curl_setopt($ch, CURLOPT_URL,
			$this->getHttpProtocol() . $this->getTeamhost() . '/ep/admin/account-manager/new');
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS,
			'email=' . $this->getIliasUser($ilUser) . '@' . $this->getTeamhost() . '&fullName='
			. $ilUser->getFirstname() . '+' . $ilUser->getLastname() . '&tempPass=B9T7XK&btn=Create+Account');
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
		curl_setopt($ch, CURLOPT_POSTFIELDS,
			'email=' . urlencode($this->getTeamuser()) . '&password=' . urlencode($this->getTeampassword()));
		$result = curl_exec($ch);
		curl_setopt($ch, CURLOPT_URL,
			$this->getHttpProtocol() . $this->getTeamhost() . '/ep/admin/account-manager/account/' . $edupad_user_id);
		curl_setopt($ch, CURLOPT_HTTPGET, true);
		$result = curl_exec($ch);
		curl_setopt($ch, CURLOPT_URL,
			$this->getHttpProtocol() . $this->getTeamhost() . '/ep/admin/account-manager/account/' . $edupad_user_id);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS,
			'newEmail=' . $this->getIliasUser($ilUser) . '@' . $this->getTeamhost() . '&newFullName='
			. $ilUser->getFirstname() . '+' . $ilUser->getLastname() . '&btn=Save'); //FSX 20110404
		$result = curl_exec($ch);
	}


	/**
	 * @param ilObjUser $ilUser
	 *
	 * @return string
	 */
	public function getEdupadHash(ilObjUser $ilUser) {
		return md5($this->getPadToken() . $this->getIliaspadpassword() . $this->getPadId()
			. $this->getIliasUser($ilUser) . '@' . $this->GetTeamhost() . $this->getTeamsubdomain());
	}


	/**
	 * @param ilObjUser $ilUser
	 *
	 * @return mixed
	 */
	public function getIliasUser(ilObjUser $ilUser) {
		return str_replace('@', '-at-', $ilUser->getLogin());
	}


	//
	// Set/Get Methods for our Edupad properties
	//
	/**
	 * Set Teamhost
	 *
	 * @param  string $a_val Teamhost e.g plugin.learnonline.iliaspad.ch
	 */
	function setTeamhost($a_val) {
		$this->teamhost = $a_val;
	}


	/**
	 * Get Teamhost
	 *
	 * @return string Teamhost e.g plugin.learnonline.iliaspad.ch
	 */
	function getTeamhost() {
		return $this->teamhost;
	}


	/**
	 * Set Teamsubdomain
	 *
	 * @param  string $a_val Teamsubdomain e.g devmst3
	 */
	function setTeamsubdomain($a_val) {
		$this->subdomain = $a_val;
	}


	/**
	 * Get Teamsubdomain
	 *
	 * @return string Teamhost e.g devmst3
	 */
	function getTeamsubdomain() {
		return $this->subdomain;
	}


	/**
	 * Set Teamuser
	 *
	 * @param  string $a_val Teamuser e.g. admin@devmst3.iliaspad.ch
	 */
	function setTeamuser($a_val) {
		$this->teamuser = $a_val;
	}


	/**
	 * Get Teamuser
	 *
	 * @return string  Teamuser
	 */
	function getTeamuser() {
		return $this->teamuser;
	}


	/**
	 * Set Teampassword
	 *
	 * @param  string $a_val Teampassword e.g. K4Jdh82f, used for login at edupad and creating pads
	 */
	function setTeampassword($a_val) {
		$this->teampassword = $a_val;
	}


	/**
	 * Get Teampassword
	 *
	 * @return string  Teampassword
	 */
	function getTeampassword() {
		return $this->teampassword;
	}


	/**
	 * Set Iliaspadpassword
	 *
	 * @param  string $a_val Iliaspadpassword e.g. K4Jdh82f, used for creating Token
	 */
	function setIliaspadpassword($a_val) {
		$this->iliaspadpassword = $a_val;
	}


	/**
	 * Get Iliaspadpassword
	 *
	 * @return string  Iliaspadpassword
	 */
	function getIliaspadpassword() {
		return $this->iliaspadpassword;
	}


	/**
	 * Set Usehttps
	 *
	 * @param  string $a_val 1,0
	 */
	function setHttpProtocol($a_val) {
		$this->httpprotocol = $a_val;
	}


	/**
	 * Get Usehttps
	 *
	 * @return string  Iliaspadpassword
	 */
	function getHttpProtocol() {
		return $this->httpprotocol;
	}


	/**
	 * Set padid
	 *
	 * @param    string $a_val PadId e.g. 12, PadIds of pro Pads are normally integer values
	 */
	function setPadId($a_val) {
		$this->padid = $a_val;
	}


	/**
	 * Get padid
	 *
	 * @return    string  PadId
	 */
	function getPadId() {
		return $this->padid;
	}


	/**
	 * Set PadToken
	 *
	 * Padtoken wird von eduPad als Cookie gesetzt und bei jedem Aufruf in der URL übergeben.
	 *
	 * @param  string $a_val padtoken
	 */
	function setPadToken($a_val) {
		$this->padtoken = $a_val;
	}


	/**
	 * Get PadToken
	 *
	 * @return string  padtoken
	 */
	function getPadToken() {
		return $this->padtoken;
	}


	/**
	 * @param $status
	 */
	public function setUseProxy($status) {
		$this->useProxy = (bool)$status;
	}


	/**
	 * @return mixed
	 */
	public function getUseProxy() {
		return $this->useProxy;
	}


	/**
	 * @param $host
	 */
	public function setProxyHost($host) {
		$this->host = $host;
	}


	/**
	 * @return mixed
	 */
	public function getProxyHost() {
		return $this->host;
	}


	/**
	 * @param $port
	 */
	public function setProxyPort($port) {
		$this->port = $port;
	}


	/**
	 * @return mixed
	 */
	public function getProxyPort() {
		return $this->port;
	}


	/**
	 * @param $defaultText
	 */
	public function setDefaultText($defaultText) {
		$this->defaultText = $defaultText;
	}


	/**
	 * @return mixed
	 */
	public function getDefaultText() {
		return $this->defaultText;
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
