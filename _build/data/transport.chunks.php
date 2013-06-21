<?php

$chunks = array();

$tmp = array(
	'tpl.mSearch2.row' => array(
		'file' => 'msearch2.row'
		,'description' => ''
	)
	,'tpl.mFilter2.outer' => array(
		'file' => 'mfilter2.outer'
		,'description' => ''
	)
	,'tpl.mFilter2.filter.outer' => array(
		'file' => 'mfilter2.filter.outer'
		,'description' => ''
	)
	,'tpl.mFilter2.filter.checkbox' => array(
		'file' => 'mfilter2.filter.checkbox'
		,'description' => ''
	)
	,'tpl.mFilter2.filter.number' => array(
		'file' => 'mfilter2.filter.number'
		,'description' => ''
	)
);

foreach ($tmp as $k => $v) {
	/* @avr modChunk $chunk */
	$chunk = $modx->newObject('modChunk');
	$chunk->fromArray(array(
		'id' => 0
		,'name' => $k
		,'description' => @$v['description']
		,'snippet' => file_get_contents($sources['source_core'].'/elements/chunks/chunk.'.$v['file'].'.tpl')
		,'static' => BUILD_CHUNK_STATIC
		,'source' => 1
		,'static_file' => 'core/components/'.PKG_NAME_LOWER.'/elements/chunks/chunk.'.$v['file'].'.tpl'
	),'',true,true);

	$chunks[] = $chunk;
}

unset($tmp);
return $chunks;