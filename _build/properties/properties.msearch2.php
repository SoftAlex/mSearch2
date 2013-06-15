<?php
/**
 * Properties for the mSearch2 snippet.
 *
 * @package msearch2
 * @subpackage build
 */

$properties = array();

$tmp = array(
	'tpl' => array(
		'type' => 'textfield'
		,'value' => 'tpl.mSearch2.row'
	)
	,'returnIds' => array(
		'type' => 'combo-boolean'
		,'value' => false
	)
	,'showLog' => array(
		'type' => 'combo-boolean'
		,'value' => false
	)
	,'fastMode' => array(
		'type' => 'combo-boolean'
		,'value' => false
	)
	,'limit' => array(
		'type' => 'numberfield'
		,'value' => 10
	)
	,'offset' => array(
		'type' => 'numberfield'
		,'value' => 0
	)
	,'depth' => array(
		'type' => 'numberfield'
		,'value' => 10
	)
	,'outputSeparator' => array(
		'type' => 'textfield'
		,'value' => "\n"
	)
	,'toPlaceholder' => array(
		'type' => 'combo-boolean'
		,'value' => false
	)
	,'parents' => array(
		'type' => 'textfield'
		,'value' => ''
	)
	,'includeTVs' => array(
		'type' => 'textfield'
		,'value' => ''
	)
	,'tvPrefix' => array(
		'type' => 'textfield'
		,'value' => ''
	)
	,'where' => array(
		'type' => 'textfield'
		,'value' => ''
	)
	,'showUnpublished' => array(
		'type' => 'combo-boolean'
		,'value' => false
	)
	,'showDeleted' => array(
		'type' => 'combo-boolean'
		,'value' => false
	)
	,'showHidden' => array(
		'type' => 'combo-boolean'
		,'value' => true
	)
	,'introCutBefore' => array(
		'type' => 'numberfield'
		,'value' => 50
	)
	,'introCutAfter' => array(
		'type' => 'numberfield'
		,'value' => 250
	)
	,'htagOpen' => array(
		'type' => 'textfield'
		,'value' => '<b>'
	)
	,'htagClose' => array(
		'type' => 'textfield'
		,'value' => '</b>'
	)
	,'parentsVar' => array(
		'type' => 'textfield'
		,'value' => 'parents'
	)
	,'queryVar' => array(
		'type' => 'textfield'
		,'value' => 'query'
	)

);

foreach ($tmp as $k => $v) {
	$properties[] = array_merge(array(
			'name' => $k
			,'desc' => 'mse2_prop_'.$k
			,'lexicon' => 'msearch2:properties'
		), $v
	);
}

return $properties;