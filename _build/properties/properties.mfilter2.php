<?php
/**
 * Properties for the mSearch2 snippet.
 *
 * @package msearch2
 * @subpackage build
 */

$properties = array();

$tmp = array(
	'paginator' => array(
		'type' => 'textfield'
		,'value' => 'getPage'
	)
	,'element' => array(
		'type' => 'textfield'
		,'value' => 'mSearch2'
	)

	,'sort' => array(
		'type' => 'textfield'
		,'value' => ''
	)
	,'filters' => array(
		'type' => 'textarea'
		,'value' => 'resource|parent:parents'
	)
	,'showEmptyFilters' => array(
		'type' => 'combo-boolean'
		,'value' => true
	)

	,'resources' => array(
		'type' => 'textfield'
		,'value' => ''
	)
	,'parents' => array(
		'type' => 'textfield'
		,'value' => ''
	)
	,'depth' => array(
		'type' => 'numberfield'
		,'value' => 10
	)

	,'tplOuter' => array(
		'type' => 'textfield'
		,'value' => 'tpl.mFilter2.outer'
	)
	,'tplFilter.outer.default' => array(
		'type' => 'textfield'
		,'value' => 'tpl.mFilter2.filter.outer'
	)
	,'tplFilter.row.default' => array(
		'type' => 'textfield'
		,'value' => 'tpl.mFilter2.filter.checkbox'
	)

	,'showHidden' => array(
		'type' => 'combo-boolean'
		,'value' => true
	)
	,'showDeleted' => array(
		'type' => 'combo-boolean'
		,'value' => false
	)
	,'showUnpublished' => array(
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
	,'disableSuggestions' => array(
		'type' => 'combo-boolean'
		,'value' => false
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