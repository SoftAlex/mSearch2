<?php

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
		,'source' => BUILD_PLUGIN_STATIC
		,'static' => 1
		,'static_file' => 'core/components/'.PKG_NAME_LOWER.'/elements/plugins/plugin.'.$v['file'].'.php'
	),'',true,true);

	$events = array();
	if (!empty($v['events'])) {
		foreach ($v['events'] as $v2) {
			/* @var modPluginEvent $event */
			$event = $modx->newObject('modPluginEvent');
			$event->fromArray(array(
				'event' => $v2,
				'priority' => 0,
				'propertyset' => 0,
			),'',true,true);

			$events[] = $event;
		}
		unset($v['events'],$event);
	}

	if (!empty($events)) {
		$plugin->addMany($events);
	}

	$plugins[] = $plugin;
}

unset($tmp, $properties);
return $plugins;