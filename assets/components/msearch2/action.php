<?php

if (!isset($modx)) {
	define('MODX_API_MODE', true);
	require_once dirname(dirname(dirname(dirname(__FILE__)))).'/index.php';
	$modx->getService('error','error.modError');
	$modx->getRequest();
	$modx->setLogLevel(modX::LOG_LEVEL_ERROR);
	$modx->setLogTarget('FILE');
	$modx->error->message = null;
	$ctx = !empty($_REQUEST['ctx']) ? $_REQUEST['ctx'] : 'web';
	if ($ctx != 'web') {$modx->switchContext($ctx);}
}

if (empty($_REQUEST['action'])) {
	exit($modx->toJSON(array('success' => false, 'message' => 'Access denied')));
}
else {
	$action = $_REQUEST['action'];
}

if (!empty($_REQUEST['pageId']) && !$modx->resource) {
	$modx->resource = $modx->getObject('modResource', $_REQUEST['pageId']);
	$config = $_SESSION['mFilter2'][@$_REQUEST['pageId']]['scriptProperties'];
}
else {$config = array();}

/* @var mSearch2 $mSearch2 */
$mSearch2 = $modx->getService('msearch2','mSearch2', MODX_CORE_PATH.'components/msearch2/model/msearch2/', $config);
$mSearch2->initialize($modx->context->key);
/* @var pdoFetch $pdoFetch */
$pdoFetch = $modx->getService('pdofetch','pdoFetch', MODX_CORE_PATH.'components/pdotools/model/pdotools/', $config);
$pdoFetch->addTime('pdoTools loaded.');

switch ($action) {
	case 'filter':
		$paginatorProperties = $_SESSION['mFilter2'][@$_REQUEST['pageId']]['paginatorProperties'];

		unset($_REQUEST['pageId'], $_REQUEST['action']);
		$_GET = $_REQUEST;

		if (!empty($_REQUEST['sort'])) {
			$paginatorProperties['sortby'] = $mSearch2->getSortFields($_REQUEST['sort']);
			$paginatorProperties['sortdir'] = '';
		}

		$pdoFetch->addTime('Getting filters for saved ids: ('.$paginatorProperties['resources'].')');
		$ids = $mSearch2->Filter($paginatorProperties['resources'], $_REQUEST);
		$pdoFetch->addTime('Filters retrieved.');
		if (empty($config['disableSuggestions'])) {
			$suggestions = $mSearch2->getSuggestions($paginatorProperties['resources'], $_REQUEST, $ids);
			$pdoFetch->addTime('Suggestions retrieved.');
		} else {
			$suggestions = array();
			$pdoFetch->addTime('Suggestions disabled by snippet parameter.');
		}

		// Saving log
		$log = $pdoFetch->timings;
		$pdoFetch->timings = array();

		if (!empty($ids)) {
			$paginatorProperties['resources'] = is_array($ids) ? implode(',', $ids) : $ids;
			$results = $modx->runSnippet($mSearch2->config['paginator'], $paginatorProperties);
			$pagination = $modx->getPlaceholder($paginatorProperties['pageNavVar']);
			$total = $modx->getPlaceholder($paginatorProperties['totalVar']);

			if (!empty($paginatorProperties['fastMode'])) {
				$results = $pdoFetch->fastProcess($results);
				$pagination = $pdoFetch->fastProcess($pagination);
			}
			else {
				$maxIterations= (integer) $modx->getOption('parser_max_iterations', null, 10);
				$modx->getParser()->processElementTags('', $results, false, false, '[[', ']]', array(), $maxIterations);
				$modx->getParser()->processElementTags('', $results, true, true, '[[', ']]', array(), $maxIterations);
				$modx->getParser()->processElementTags('', $pagination, false, false, '[[', ']]', array(), $maxIterations);
				$modx->getParser()->processElementTags('', $pagination, true, true, '[[', ']]', array(), $maxIterations);
			}
		}
		else {
			$results = $pagination = '';
		}

		$pdoFetch->timings = $log;
		$response = array(
			'success' => true
			,'message' => ''
			,'data' => array(
				'results' => !empty($results) ? $results : $modx->lexicon('mse2_err_no_results')
				,'pagination' => $pagination
				,'total' => empty($total) ? 0 : $total
				,'suggestions' => $suggestions
				,'log' => ($modx->user->hasSessionContext('mgr') && !empty($config['showLog'])) ? print_r($pdoFetch->getTime(), 1) : ''
			)
		);
		$response = $modx->toJSON($response);

		break;

	default:
		$response = $modx->toJSON(array('success' => false, 'message' => 'Access denied'));
}

@session_write_close();
exit($response);