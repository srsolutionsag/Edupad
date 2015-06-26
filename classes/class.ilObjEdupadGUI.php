<?php
/* Copyright (c) 2011 ILIAS open source, Extended GPL, see docs/LICENSE */
//error_reporting(E_ALL);
//ini_set('display_error', 'stdout');
require_once('./Services/Repository/classes/class.ilObjectPluginGUI.php');
require_once('Services/Form/classes/class.ilPropertyFormGUI.php');
require_once('class.ilObjEdupad.php');

/**
 * User Interface class for Edupad repository object.
 *
 * User interface classes process GET and POST parameter and call
 * application classes to fulfill certain tasks.
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 * @author            Martin Studer <ms@studer-raimann.ch>
 *
 * $Id$
 *
 * Integration into control structure:
 * - The GUI class is called by ilRepositoryGUI
 * - GUI classes used by this class are ilPermissionGUI (provides the rbac
 *   screens) and ilInfoScreenGUI (handles the info screen).
 *
 * @ilCtrl_isCalledBy ilObjEdupadGUI: ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI, ilCommonActionDispatcherGUI
 * @ilCtrl_Calls      ilObjEdupadGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilCommonActionDispatcherGUI
 *
 */
class ilObjEdupadGUI extends ilObjectPluginGUI {

	/**
	 * @var ilCtrl
	 */
	protected $ctrl;
	/**
	 * @var ilAccessHandler
	 */
	protected $access;
	/**
	 * @var ilLanguage
	 */
	public $lng;
	/**
	 * @var ilObjUser
	 */
	protected $user;
	/**
	 * @var ilTabsGUI
	 */
	protected $tabs;
	/**
	 * @var ilTemplate
	 */
	public $tpl;
	/**
	 * @var ilPropertyFormGUI
	 */
	protected $form;
	/**
	 * @var ilEdupadPlugin
	 */
	protected $pl;
	/**
	 * @var ilObjEdupad
	 */
	public $object;


	/**
	 * @return string
	 */
	final function getType() {
		return 'xpad';
	}


	/**
	 * @param int $a_ref_id
	 * @param int $a_id_type
	 * @param int $a_parent_node_id
	 */
	public function __construct($a_ref_id = 0, $a_id_type = self::REPOSITORY_NODE_ID, $a_parent_node_id = 0) {
		global $ilCtrl, $ilAccess, $lng, $ilUser, $ilTabs, $tpl;
		parent::__construct($a_ref_id, $a_id_type, $a_parent_node_id);
		$this->ctrl = $ilCtrl;
		$this->access = $ilAccess;
		$this->lng = $lng;
		$this->user = $ilUser;
		$this->tabs = $ilTabs;
		$this->tpl = $tpl;
		$this->pl = new ilEdupadPlugin();
	}


	/**
	 * @param $cmd
	 */
	public function performCommand($cmd) {
		switch ($cmd) {
			case 'editProperties':
			case 'updateProperties':
				$this->checkPermission('write');
				$this->$cmd();
				break;
			case 'editContent':
			case 'timeSlider':
				$this->checkPermission('read');
				$this->$cmd();
				break;
		}
	}


	/**
	 * @return string
	 */
	public function getAfterCreationCmd() {
		return 'editContent';
	}


	/**
	 * @return string
	 */
	public function getStandardCmd() {
		return 'editContent';
	}


	public function setTabs() {
		if ($this->access->checkAccess('read', '', $this->object->getRefId())) {
			$this->tabs->addTab('edit', $this->txt('pad'), $this->ctrl->getLinkTarget($this, 'editContent'));
		}
		$this->addInfoTab();
		if ($this->access->checkAccess('read', '', $this->object->getRefId())) {
			$this->tabs->addTab('timeslider', $this->txt('timeslider'), $this->ctrl->getLinkTarget($this, 'timeSlider'));
		}
		if ($this->access->checkAccess('write', '', $this->object->getRefId())) {
			$this->tabs->addTab('properties', $this->txt('properties'), $this->ctrl->getLinkTarget($this, 'editProperties'));
		}
		$this->addPermissionTab();
	}


	public function editProperties() {
		$this->tabs->activateTab('properties');
		$this->initPropertiesForm();
		$this->getPropertiesValues();
		$this->tpl->setContent($this->form->getHTML());
	}


	public function initPropertiesForm() {
		$this->form = new ilPropertyFormGUI();
		// Title
		$ti = new ilTextInputGUI($this->txt('title'), 'title');
		$ti->setRequired(true);
		$this->form->addItem($ti);
		// Description
		$ta = new ilTextAreaInputGUI($this->txt('description'), 'desc');
		$this->form->addItem($ta);
		$this->form->addCommandButton('updateProperties', $this->txt('save'));
		$this->form->setTitle($this->txt('edit_properties'));
		$this->form->setFormAction($this->ctrl->getFormAction($this));
	}


	public function getPropertiesValues() {
		$values['title'] = $this->object->getTitle();
		$values['desc'] = $this->object->getDescription();
		$this->form->setValuesByArray($values);
	}


	public function updateProperties() {
		$this->initPropertiesForm();
		if ($this->form->checkInput()) {
			$this->object->setTitle($this->form->getInput('title'));
			$this->object->setDescription($this->form->getInput('desc'));
			$this->object->update();
			ilUtil::sendSuccess($this->lng->txt('msg_obj_modified'), true);
			$this->ctrl->redirect($this, 'editProperties');
		}
		$this->form->setValuesByPost();
		$this->tpl->setContent($this->form->getHtml());
	}


	protected function editContent() {
		$this->buildFrame('pad');
	}


	protected function timeSlider() {
		$this->buildFrame('slider');
	}


	/**
	 * @param $type
	 */
	protected function buildFrame($type) {
		if ($type == 'pad') {
			$this->tabs->activateTab('edit');
		} elseif ($type == 'slider') {
			$this->tabs->activateTab('timeslider');
		}
		$this->object->addUser($this->user);
		$iframe = $this->pl->getTemplate('default/tpl.iframe.html');
		$iframe->setVariable('HOST', $this->object->getHttpProtocol() . $this->object->getTeamhost());
		$iframe->setVariable('PAD_ID', $this->object->getPadId());
		$iframe->setVariable('REF_ID', $this->object->getRefId());
		$iframe->setVariable('TYPE', $type);
		$iframe->setVariable('SCROLLING', $this->object->getScrolling() ? 'auto' : 'no');
		$iframe->setVariable('ACCOUNT', $this->object->getIliasUser($this->user) . '@' . $this->object->getTeamhost());
		$this->tpl->setContent($iframe->get());
	}


	/**
	 * @param $a_target
	 *
	 * @return bool|void
	 * @description Do not use $this, 'cause _goto can be accessed statically
	 */
	public static function _goto($a_target) {
		global $ilCtrl, $ilAccess, $lng, $rbacsystem, $tree, $ilUser;
		/* Parameter werden wie folgt via URL übergeben:
		 * http://test.learnonline.ch/devmst3/goto.php?target=xpad_[refid]_[cookievalue]_[localPadId]_[type]_[accountRecordId]_[url]&client_id=test_demomaster'
		 *
		 * http://test.studer-raimann.ch/phbern/goto.php?target=xpad_82&client_id=ilias_phbern
		 * http://test.studer-raimann.ch/phbern/goto.php?target=crs_51&client_id=ilias_phbern
		*/
		$params = explode("_", $a_target[0]);
		$param['ref_id'] = $params[0];
		$param['token'] = $params[1];
		$param['pad_id'] = $params[2];
		$param['pad_type'] = $params[3];
		$param['pad_userid'] = $params[4];
		$param['pad_returnurl'] = $params[5];
		$il_type = self::getType();
		//Falls count($params) >= 2, handelt es sich um einen edupad-request.
		if (count($params) >= 2) {
			/* 
			* keine RefID, mittels pad_id prüfen, ob der Benutzer auf mindestens ein Pad Zugriff hat. 
			* Dies kann vorkommen bei Aufruf eines Exports oder View von Revisionen etc.
			* Pads können in ILIAS mehrfach verlinkt vorkommen.
			*/
			if ($param['ref_id'] == 'undefined') {
				$ilObjEdupad = new ilObjEdupad();
				$ilObjEdupad->setPadToken($param['token']);
				$ilObjEdupad->setPadId($param['pad_id']);
				// Get refs for pad_id
				$object_ids = $ilObjEdupad->doSearch($param['pad_id']);
				$refs = array();
				foreach ($object_ids as $key => $obj_id) {
					$rs = ilObject::_getAllReferences($obj_id);
					foreach ($rs as $r) {
						if ($tree->isInTree($r)) {
							$refs[] = $r;
						}
					}
				}
				// Check Permissons
				foreach ($refs as $key => $ref_id) {
					if ($rbacsystem->checkAccess('read', $ref_id)) {
						$access = true;
					}
				}
				if ($access) {
					//Redirect to Pad      
					header('Location: ' . $param['pad_returnurl'] . '&ilac=' . $ilObjEdupad->getEdupadHash($ilUser) . '&account='
						. $ilObjEdupad->getIliasUser($ilUser) . '@' . $ilObjEdupad->getTeamhost() . '&subdomain='
						. $ilObjEdupad->getTeamsubdomain()); //FSX 20110404
					return true;
				} else {
					//Redirect to Login-Mask
					header('Location: login.php?target=' . $il_type . '_' . $refs[0] . 'client_id=' . CLIENT_ID);

					return false;
				}
			} elseif ($rbacsystem->checkAccess('read', $param['ref_id'], 'xpad')) {
				$ilObjEdupad = new ilObjEdupad();
				$ilObjEdupad->updateUser($ilUser, $param['pad_userid']);
				$ilObjEdupad->setPadToken($param['token']);
				$ilObjEdupad->setPadId($param['pad_id']);
				//Debug
				//				if (! class_exists('Log')) {
				//					include_once 'Log.php';
				//				}
				//				$logger =& Log::singleton('null', NULL, 'auth[' . getmypid() . ']', array(), 6);
				//				$logger->log('eduPAD: checkAccess TRUE: Location: ' . $param['pad_returnurl'] . '&ilac='
				//					. $ilObjEdupad->getEdupadHash($ilUser) . '&account=' . $ilObjEdupad->getIliasUser($ilUser) . '@'
				//					. $ilObjEdupad->getTeamhost() . '&subdomain='
				//					. $ilObjEdupad->getTeamsubdomain(), 6); /* AUTH_LOG_INFO' */ //FSX 20110404
				header('Location: ' . $param['pad_returnurl'] . '&ilac=' . $ilObjEdupad->getEdupadHash($ilUser) . '&account='
					. $ilObjEdupad->getIliasUser($ilUser) . '@' . $ilObjEdupad->getTeamhost() . '&subdomain=' . $ilObjEdupad->getTeamsubdomain()
					. '&defaultText=default'); //FSX 20110404
			} else {
				header("Location: login.php?target=" . $il_type . "_" . $refs[0] . "client_id=" . CLIENT_ID);
				//Debug
				//				if (! class_exists('Log')) {
				//					include_once 'Log.php';
				//				}
				//				$logger =& Log::singleton('null', NULL, 'auth[' . getmypid() . ']', array(), 6);
				//				$logger->log("eduPAD: checkAccess FALSE: Location: login.php?target=" . $type . "_" . $refs[0]
				//					. "client_id=" . CLIENT_ID, 6); /* AUTH_LOG_INFO' */

				return;
			}
		} else {
			$ref_id = (int)$params[0];
			$class_name = $a_target[1];
			if ($ilAccess->checkAccess('read', '', $ref_id)) {
				$ilCtrl->initBaseClass('ilObjPluginDispatchGUI');
				$ilCtrl->setTargetScript('ilias.php');
				$ilCtrl->getCallStructure(strtolower('ilObjPluginDispatchGUI'));
				$ilCtrl->setParameterByClass($class_name, 'ref_id', $ref_id);
				$ilCtrl->redirectByClass(array(
					'ilobjplugindispatchgui',
					$class_name
				), '');
			} elseif ($ilAccess->checkAccess('visible', '', $ref_id)) {
				$ilCtrl->initBaseClass('ilObjPluginDispatchGUI');
				$ilCtrl->setTargetScript('ilias.php');
				$ilCtrl->getCallStructure(strtolower('ilObjPluginDispatchGUI'));
				$ilCtrl->setParameterByClass($class_name, 'ref_id', $ref_id);
				$ilCtrl->redirectByClass(array(
					'ilobjplugindispatchgui',
					$class_name
				), 'infoScreen');
			} elseif ($ilAccess->checkAccess('read', '', ROOT_FOLDER_ID)) {
				ilUtil::sendFailure(sprintf($lng->txt('msg_no_perm_read_item'), ilObject::_lookupTitle(ilObject::_lookupObjId($ref_id))));
				$_GET['cmd'] = 'frameset';
				$_GET['target'] = '';
				$_GET['ref_id'] = ROOT_FOLDER_ID;
				$_GET['baseClass'] = 'ilRepositoryGUI';
				include('ilias.php');
				exit;
			}
		}
	}
}

?>
