<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');
$tempColumns = Array (
	"tx_mmbirthdaygreeting_birthday" => Array (		
		"exclude" => 1,		
		"label" => "LLL:EXT:mm_birthdaygreeting/locallang_db.xml:fe_users.tx_mmbirthdaygreeting_birthday",		
		"config" => Array (
			"type" => "input",	
			"size" => "10",	
			"max" => "10",
			"eval" => "trim",
		)
	),
);


t3lib_div::loadTCA("fe_users");
t3lib_extMgm::addTCAcolumns("fe_users",$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes("fe_users","tx_mmbirthdaygreeting_birthday;;;;1-1-1");


t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key';

	// add FlexForm field to tt_content
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY."_pi1"]='pi_flexform';

t3lib_extMgm::addPiFlexFormValue($_EXTKEY."_pi1", 'FILE:EXT:mm_birthdaygreeting/flexform_ds_pi1.xml');

t3lib_extMgm::addPlugin(Array('LLL:EXT:mm_birthdaygreeting/locallang_db.xml:tt_content.list_type_pi1', $_EXTKEY.'_pi1'),'list_type');


t3lib_extMgm::addStaticFile($_EXTKEY,"pi1/static/","MM Birthday-Greeting");
?>