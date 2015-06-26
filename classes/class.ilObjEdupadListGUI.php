<?php
/* Copyright (c) 2011 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once "./Services/Repository/classes/class.ilObjectPluginListGUI.php";

/**
* ListGUI implementation for Edupad object plugin. This one
* handles the presentation in container items (categories, courses, ...)
* together with the corresponfing ...Access class.
*
* PLEASE do not create instances of larger classes here. Use the
* ...Access class to get DB data and keep it small.
*
* @author Fabian Schmid <fs@studer-raimann.ch>
* @author Martin Studer <ms@studer-raimann.ch>
*
*/
class ilObjEdupadListGUI extends ilObjectPluginListGUI
{
	
	/**
	* Init type
	*/
	function initType()
	{
		$this->setType("xpad");
	}
	
	/**
	* inititialize new item
	* 
	*
	* @param  int     $a_ref_id   reference id
	* @param  int     $a_obj_id   object id
	* @param  string    $a_title    title
	* @param  string    $a_description  description
	*/
	function initItem($a_ref_id, $a_obj_id, $a_title = "", $a_description = "")
	{
		//Don't display some options
		$this->copy_enabled = false;
		parent::initItem($a_ref_id, $a_obj_id, $a_title, $a_description);
	}
	
	/**
	* Get name of gui class handling the commands
	*/
	function getGuiClass()
	{
		return "ilObjEdupadGUI";
	}
	
	/**
	* Get commands
	*/
	function initCommands()
	{
		return array
		(
			array(
				"permission" => "read",
				"cmd" => "editContent",
				"default" => true),
			array(
				"permission" => "write",
				"cmd" => "editProperties",
				"txt" => $this->txt("edit"),
				"default" => false),
		);
	}

	/**
	* Get item properties
	*
	* @return	array		array of property arrays:
	*						"alert" (boolean) => display as an alert property (usually in red)
	*						"property" (string) => property name
	*						"value" (string) => property value
	*/
	function getProperties()
	{
		global $lng, $ilUser;

		$props = array();
		
		$this->plugin->includeClass("class.ilObjEdupadAccess.php");
		return $props;
	}
}
?>
