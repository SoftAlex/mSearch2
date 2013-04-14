<?php
/**
 * Loads system settings into build
 *
 * @package msearch2
 * @subpackage build
 */
$settings = array();

$tmp = array(
	/*
	'some_setting' => array(
		'xtype' => 'combo-boolean'
		,'value' => true
		,'area' => 'msearch2_main'
	)
	*/
);

foreach ($tmp as $k => $v) {
	/* @var modSystemSetting $setting */
	$setting = $modx->newObject('modSystemSetting');
	$setting->fromArray(array_merge(
		array(
			'key' => 'msearch2_'.$k
			,'namespace' => 'msearch2'
		), $v
	),'',true,true);

	$settings[] = $setting;
}

unset($tmp);
return $settings;