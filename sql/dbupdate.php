<#1>
<?php
$fields = array(
	'id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'is_online' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => false
	),
	'album_url' => array(
		'type' => 'text',
		'length' => 1024,
		'fixed' => false,
		'notnull' => false
	)
);
$ilDB->dropTable("rep_robj_xmma_data", false);
$ilDB->createTable("rep_robj_xmma_data", $fields);
$ilDB->addPrimaryKey("rep_robj_xmma_data", array("id"));?>
<#2>
<?php 
$fields = array(
	'keyword' => array(
		'type' => 'text',
		'length' => 50,
		'fixed' => false,
		'notnull' => true
	),
	'value' => array(
		'type' => 'clob',
		'notnull' => false,
		'default' => null
	)
);
$ilDB->dropTable("rep_robj_xmma_settings", false);
$ilDB->createTable("rep_robj_xmma_settings", $fields);
$ilDB->addPrimaryKey("rep_robj_xmma_settings", array("keyword"));
?>
<#3>
<?php 
$ilDB->insert("rep_robj_xmma_settings", array("keyword" => array("text", "secret_key"), "value" => array("clob", null)));
?>
<#4>
<?php 
$attributes = array(
	'type' => 'integer',
	'length' => 2,
	'default' => 0,
	'notnull' => true);
$ilDB->addTableColumn("rep_robj_xmma_data", "update_mode", $attributes);
?>
<#5>
<?php
$ilDB->insert("rep_robj_xmma_settings", array("keyword" => array("text", "url_format"), "value" => array("clob", null)));
?>
<#6>
<?php 
$attributes = array(
	'type' => 'clob',
	'default' => null,
	'notnull' => false);
$ilDB->addTableColumn("rep_robj_xmma_data", "album_xml", $attributes);
?>
<#7>
<?php 
$ilDB->insert("rep_robj_xmma_settings", array("keyword" => array("text", "url_album_xml_format"), "value" => array("clob", null)));
$ilDB->insert("rep_robj_xmma_settings", array("keyword" => array("text", "url_album_list_format"), "value" => array("clob", null)));
?>
