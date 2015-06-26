<#1>
<?php

$fields = array(
	'id' => array(
		'type' => 'integer',
		'length' => 8,
		'notnull' => true
	),
	'pad_id' => array(
		'type' => 'text',
		'length' => 10,
		'fixed' => false,
		'notnull' => false
	),
);

$ilDB->createTable("rep_robj_xpad_data", $fields);
$ilDB->addPrimaryKey("rep_robj_xpad_data", array("id"));

?>