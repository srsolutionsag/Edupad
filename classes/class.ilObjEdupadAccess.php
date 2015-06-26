<?php
/* Copyright (c) 2011 ILIAS open source, Extended GPL, see docs/LICENSE */
include_once("./Services/Repository/classes/class.ilObjectPluginAccess.php");
/**
 * Access/Condition checking for Edupad object
 *
 * Please do not create instances of large application classes (like ilObjEdupad)
 * Write small methods within this class to determin the status.
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @author  Martin Studer <ms@studer-raimann.ch>
 * @version $Id$
 */
class ilObjEdupadAccess extends ilObjectPluginAccess {

	/**
	 * @param string $a_cmd
	 * @param string $a_permission
	 * @param int    $a_ref_id
	 * @param int    $a_obj_id
	 * @param string $a_user_id
	 *
	 * @return bool
	 */
	function _checkAccess($a_cmd, $a_permission, $a_ref_id, $a_obj_id, $a_user_id = "") {
		return true;
	}


	/**
	 * @param $a_target
	 *
	 * @return bool
	 */
	function _checkGoto($a_target) {
		return true;
	}
}

?>