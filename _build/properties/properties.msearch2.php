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
		,'value' => 'tpl.mSearch2.item'
	)
	,'sortBy' => array(
		'type' => 'textfield'
		,'value' => 'name'
	)
	,'sortDir' => array(
		'type' => 'list'
		,'options' => array(
			array('text' => 'ASC', 'value' => 'ASC')
			,array('text' => 'DESC', 'value' => 'DESC')
		)
		,'value' => 'ASC'
	)
	,'limit' => array(
		'type' => 'numberfield'
		,'value' => 5
	)
	,'outputSeparator' => array(
		'type' => 'textfield'
		,'value' => "\n"
	)
	,'toPlaceholder' => array(
		'type' => 'combo-boolean'
		,'value' => false
	)
);

foreach ($tmp as $k => $v) {
	$properties[] = array_merge(array(
			'name' => $k
			,'desc' => 'msearch2_prop_'.$k
			,'lexicon' => 'msearch2:properties'
		), $v
	);
}

return $properties;