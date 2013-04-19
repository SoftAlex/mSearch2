<?php
/**
 * Loads system settings into build
 *
 * @package msearch2
 * @subpackage build
 */
$settings = array();

$tmp = array(
	'index_fields' => array(
		'xtype' => 'textarea'
		,'value' => 'pagetitle:3,longtitle:3,description:2,introtext:2,content:2'
		,'area' => 'mse2_main'
	)
	,'index_comments' => array(
		'xtype' => 'combo-boolean'
		,'value' => true
		,'area' => 'mse2_main'
	)
	,'index_comments_weight' => array(
		'xtype' => 'numberfield'
		,'value' => 1
		,'area' => 'mse2_main'
	)
);

foreach ($tmp as $k => $v) {
	/* @var modSystemSetting $setting */
	$setting = $modx->newObject('modSystemSetting');
	$setting->fromArray(array_merge(
		array(
			'key' => 'mse2_'.$k
			,'namespace' => 'msearch2'
		), $v
	),'',true,true);

	$settings[] = $setting;
}

unset($tmp);
return $settings;