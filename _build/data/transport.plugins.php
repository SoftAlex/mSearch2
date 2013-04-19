<?php
/**
 * Add plugins to build
 *
 * @package msearch2
 * @subpackage build
 */

$plugins = array();

$tmp = array(
	'mSearch2' => array(
		'file' => 'msearch2'
		,'description' => ''
		,'events' => array(
			'OnDocFormSave'
			,'OnResourceDelete'
			,'OnResourceUndelete'
			,'OnCommentSave'
			,'OnCommentRemove'
			,'OnCommentDelete'
			//,'OnCommentPublish'
		)
	)
);

foreach ($tmp as $k => $v) {
	/* @avr modplugin $plugin */
	$plugin = $modx->newObject('modPlugin');
	$plugin->fromArray(array(
		'id' => 0
		,'name' => $k
		,'category' => 0
		,'description' => @$v['description']
		,'plugincode' => getSnippetContent($sources['source_core'].'/elements/plugins/plugin.'.$v['file'].'.php')
		//,'static' => 1
		//,'static_file' => 'core/components/msearch2/elements/plugins/plugin.'.$v['file'].'.php'
	),'',true,true);

	$events = array();
	if (!empty($v['events'])) {
		foreach ($v['events'] as $k2 => $v2) {
			$events[$k2] = $modx->newObject('modPluginEvent');
			$events[$k2]->fromArray(array(
				'event' => $v2,
				'priority' => 0,
				'propertyset' => 0,
			),'',true,true);
		}
		unset($v['events']);
	}

	if (!empty($events)) {
		$plugin->addMany($events);
	}

	$plugins[] = $plugin;
}

unset($tmp, $properties);
return $plugins;