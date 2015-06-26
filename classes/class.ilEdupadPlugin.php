<?php
/* Copyright (c) 2011 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once('./Services/Repository/classes/class.ilRepositoryObjectPlugin.php');


/**
 * Edupad repository object plugin
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @author  Martin Studer <ms@studer-raimann.ch>
 * @version $Id$
 *
 */
class ilEdupadPlugin extends ilRepositoryObjectPlugin {

	/**
	 * @return string
	 */
	public function getPluginName() {
		return 'Edupad';
	}
}

?>
