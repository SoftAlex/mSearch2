<?php

$settings = array();

$tmp = array(
	'frontend_css' => array(
		'value' => '[[+cssUrl]]web/default.css'
		,'xtype' => 'textfield'
		,'area' => 'mse2_main'
	)
	,'frontend_js' => array(
		'value' => '[[+jsUrl]]web/default.js'
		,'xtype' => 'textfield'
		,'area' => 'mse2_main'
	)

	,'index_fields' => array(
		'xtype' => 'textarea'
		,'value' => 'content:3,description:2,introtext:2,pagetitle:3,longtitle:3'
		,'area' => 'mse2_index'
	)
	,'index_comments' => array(
		'xtype' => 'combo-boolean'
		,'value' => true
		,'area' => 'mse2_index'
	)
	,'index_comments_weight' => array(
		'xtype' => 'numberfield'
		,'value' => 1
		,'area' => 'mse2_index'
	)
	,'index_min_words_length' => array(
		'xtype' => 'numberfield'
		,'value' => 3
		,'area' => 'mse2_index'
	)

	,'search_exact_match_bonus' => array(
		'xtype' => 'numberfield'
		,'value' => 5
		,'area' => 'mse2_search'
	)
	,'search_all_words_bonus' => array(
		'xtype' => 'numberfield'
		,'value' => 5
		,'area' => 'mse2_search'
	)
	,'search_split_words' => array(
		'xtype' => 'textfield'
		,'value' => '#\s#'
		,'area' => 'mse2_search'
	)
	,'filters_handler_class' => array(
		'xtype' => 'textfield'
		,'value' => 'mse2FiltersHandler'
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