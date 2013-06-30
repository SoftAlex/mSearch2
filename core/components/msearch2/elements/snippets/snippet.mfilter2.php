<?php
/* @var array $scriptProperties */
/* @var mSearch2 $mSearch2 */
$mSearch2 = $modx->getService('msearch2','mSearch2',$modx->getOption('msearch2.core_path',null,$modx->getOption('core_path').'components/msearch2/').'model/msearch2/',$scriptProperties);
$mSearch2->initialize($modx->context->key);
/* @var pdoFetch $pdoFetch */
$pdoFetch = $modx->getService('pdofetch','pdoFetch', MODX_CORE_PATH.'components/pdotools/model/pdotools/',$scriptProperties);
$pdoFetch->setConfig($scriptProperties);
$pdoFetch->addTime('pdoTools loaded.');
$_SESSION['mFilter2'][$modx->resource->id] = array();

if (empty($queryVar)) {$queryVar = 'query';}
if (empty($parentsVar)) {$parentsVar = 'parents';}
if (empty($totalVar)) {$totalVar = 'total';}
if (empty($minQuery)) {$minQuery = $modx->getOption('index_min_words_length', null, 3, true);}
if (!isset($plPrefix)) {$plPrefix = '';}
if (empty($depth)) {$depth = 10;}
if (empty($limit)) {$limit = 10;}
if (empty($classActive)) {$classActive = 'active';}
if (isset($scriptProperties['disableSuggestions'])) {$scriptProperties['suggestions'] = empty($scriptProperties['disableSuggestions']);}
$output = array('filters' => '', 'results' => '');
$ids = $found = $log = array();


// ---------------------- Retrieving ids of resources for filter
$query = @$_REQUEST[$queryVar];
if (!empty($resources)) {
	$ids = array_map('trim', explode(',', $resources));
	$pdoFetch->addTime('Received ids: ('.implode(',',$ids).')');
}
else if (isset($_REQUEST[$queryVar]) && empty($query)) {
	return $modx->lexicon('mse2_err_no_query');
}
else if (isset($_REQUEST[$queryVar]) && !preg_match('/^[0-9]{2,}$/', $query) && mb_strlen($query,'UTF-8') < $minQuery) {
	return $modx->lexicon('mse2_err_min_query');
}
else if (isset($_REQUEST[$queryVar])) {
	$query = htmlspecialchars(strip_tags(trim($query)));
	$modx->setPlaceholder('mse2_'.$queryVar, $query);

	$found = $mSearch2->Search($query);
	$ids = array_keys($found);

	if (empty($ids)) {
		return $modx->lexicon('mse2_err_no_results');
	}
	$pdoFetch->addTime('Found ids: ('.implode(',',$ids).')');
}

// Filter ids by parents
if (empty($scriptProperties[$parentsVar]) && !empty($_REQUEST[$parentsVar])) {$parents = $_REQUEST[$parentsVar];}
else {$parents = $scriptProperties[$parentsVar];}
if (!empty($parents) && $parents > 0) {
	$pids = array_map('trim', explode(',', $parents));
	$parents = array();
	if (!empty($depth) && $depth > 0) {
		foreach ($pids as $v) {
			if (!is_numeric($v)) {continue;}
			$parents = array_merge($parents, $modx->getChildIds($v, $depth));
		}
	}
	$ids = !empty($ids) ? array_intersect($ids,$parents) : $parents;

	// Support for ms2 multi categories
	if (class_exists('msProduct')) {
		$q = $modx->newQuery('msCategoryMember', array('category_id:IN' => $pids));
		$q->select('product_id');
		if ($q->prepare() && $q->stmt->execute()) {
			$members = $q->stmt->fetchAll(PDO::FETCH_COLUMN);
			if (!empty($members)) {
				$pdoFetch->addTime('Added ids by ms2 category members: ('.implode(',',$members).')');
				$ids = array_unique(array_merge($members, $ids));
			}
		}
	}

	$pdoFetch->addTime('Filtered ids by parents: ('.implode(',',$ids).')');
}

// ---------------------- Nothing to filter, exit
if (empty($ids)) {
	if ($modx->user->hasSessionContext('mgr') && !empty($showLog)) {
		$log = '<pre class="mFilterLog">' . print_r($pdoFetch->getTime(), 1) . '</pre>';
	}
	$output = array(
		'filters' => $modx->lexicon('mse2_err_no_filters')
		,'results' => $modx->lexicon('mse2_err_no_results')
		,$totalVar => 0
	);
	if (!empty($toPlaceholder)) {
		$output['log'] = $log;
		$modx->setPlaceholders($output, $plPrefix);
		return;
	}
	else {
		$output = $pdoFetch->getChunk($scriptProperties['tplOuter'], $output, $pdoFetch->config['fastMode']);
		$output .= $log;

		return $output;
	}
}


// ---------------------- Checking resources by status and custom "where" parameter
$where = array('id:IN' => $ids);
if (empty($showUnpublished)) {$where['published'] = 1;}
if (empty($showHidden)) {$where['hidemenu'] = 0;}
if (empty($showDeleted)) {$where['deleted'] = 0;}
if (!empty($scriptProperties['where'])) {
	$tmp = $modx->fromJSON($scriptProperties['where']);
	if (!empty($tmp) && is_array($tmp)) {
		$where = array_merge($where, $tmp);
	}
}
unset($scriptProperties['where']);
$q = $modx->newQuery('modResource', $where);
$q->select('id');
if ($q->prepare() && $q->stmt->execute()) {
	$tmp = $q->stmt->fetchAll(PDO::FETCH_COLUMN);
	$ids = array_intersect($ids, $tmp);
}
$pdoFetch->addTime('Filtered ids by status and custom "where" param: ('.implode(',',$ids).')');

// ---------------------- Checking for suggestions processing
// Checking by results count
if (!empty($scriptProperties['suggestionsMaxResults']) && count($ids) > $scriptProperties['suggestionsMaxResults']) {
	$scriptProperties['suggestions'] = false;
	$pdoFetch->addTime('Suggestions disabled by "suggestionsMaxResults" parameter: results count is '.count($ids).', max allowed is '.$scriptProperties['suggestionsMaxResults']);
}
else {
	$pdoFetch->addTime('Total number of results: '.count($ids));
}

// Then get filters
$pdoFetch->addTime('Getting filters for ids: ('.implode(',',$ids).')');
$filters = '';
if (!empty($ids)) {
	$filters = $mSearch2->getFilters($ids);
	// And checking by filters count
	if (!empty($filters) && $scriptProperties['suggestions']) {
		$count = 0;
		foreach ($filters as $tmp) {
			$count += count(array_values($tmp));
		}
		if (!empty($scriptProperties['suggestionsMaxFilters']) && $count > $scriptProperties['suggestionsMaxFilters']) {
			$scriptProperties['suggestions'] = false;
			$pdoFetch->addTime('Suggestions disabled by "suggestionsMaxFilters" parameter: filters count is '.$count.', max allowed is '.$scriptProperties['suggestionsMaxFilters']);
		}
		else {
			$pdoFetch->addTime('Total number of filters: '.$count);
		}
	}
}


// ---------------------- Loading results
$start_sort = implode(',', array_map('trim' , explode(',', $scriptProperties['sort'])));
$start_limit = $limit;
$suggestions = array();
$page = $sort = '';
if (!empty($ids)) {
	/* @var modSnippet $paginator */
	if ($paginator = $modx->getObject('modSnippet', array('name' => $scriptProperties['paginator']))) {
		$paginatorProperties = array_merge(
			$paginator->getProperties()
			,$scriptProperties
			,array(
				'resources' => implode(',',$ids)
				,'parents' => '0'
				,'element' => $scriptProperties['element']
				,'defaultSort' => $start_sort
				,'toPlaceholder' => false
			)
		);

		// Trying to save weight of found ids if using mSearch2
		$weight = false;
		if (!empty($found) && strtolower($paginatorProperties['element']) == 'msearch2') {
			$tmp = array();
			foreach ($ids as $v) {$tmp[$v] = @$found[$v];}
			$paginatorProperties['resources'] = $modx->toJSON($tmp);
			$weight = true;
		}

		if (!empty($_REQUEST['sort'])) {$sort = $_REQUEST['sort'];}
		else if (!empty($start_sort)) {$sort = $start_sort;}
		else {
			$sortby = !empty($scriptProperties['sortby']) ? $scriptProperties['sortby'] : '';
			if (!empty($sortby)) {
				$sortdir = !empty($scriptProperties['sortdir']) ? $scriptProperties['sortdir'] : 'asc';
				$sort = 'resource'.$mSearch2->config['filter_delimeter'].$sortby.$mSearch2->config['method_delimeter'].$sortdir;
			}
		}
		if (!empty($_REQUEST[$paginatorProperties['pageVarKey']])) {
			$page = (int) $_REQUEST[$paginatorProperties['pageVarKey']];
		}
		if (!empty($_REQUEST['limit'])) {$limit = $_REQUEST['limit'];}
		if (!empty($sort)) {
			$paginatorProperties['sortby'] = $mSearch2->getSortFields($sort);
			$paginatorProperties['sortdir'] = '';
		}

		$_SESSION['mFilter2'][$modx->resource->id]['paginatorProperties'] = $paginatorProperties;

		// We have a delimeters in $_GET, so need to filter resources
		if (strpos(implode(array_keys($_GET)), $mSearch2->config['filter_delimeter']) !== false) {
			$matched = $mSearch2->Filter($ids, $_REQUEST);
			$matched = array_intersect($ids, $matched);
			if ($scriptProperties['suggestions']) {
				$suggestions = $mSearch2->getSuggestions($ids, $_REQUEST, $matched);
				$pdoFetch->addTime('Suggestions retrieved.');
			}
			// Trying to save weight of found ids again
			if ($weight) {
				$tmp = array();
				foreach ($matched as $v) {$tmp[$v] = @$found[$v];}
				$paginatorProperties['resources'] = $modx->toJSON($tmp);
			}
			else {
				$paginatorProperties['resources'] = implode(',', $matched);
			}
		}
		$paginator->setProperties($paginatorProperties);
		$paginator->setCacheable(false);

		// Saving log
		$log = $pdoFetch->timings;
		$pdoFetch->timings = array();

		$output['results'] = !empty($paginatorProperties['resources']) ? $paginator->process() : $modx->lexicon('mse2_err_no_results');

	}
	else {
		$modx->log(modX::LOG_LEVEL_ERROR, '[mSearch2] Could not find pagination snippet with name: "'.$scriptProperties['paginator'].'"');
	}
}

// ----------------------  Loading filters
$pdoFetch->timings = $log;
if (is_object($paginator)) {
	$pdoFetch->addTime('Fired paginator: "'.$scriptProperties['paginator'].'"');
}
else {
	$pdoFetch->addTime('Could not find pagination snippet with name: "'.$scriptProperties['paginator'].'"');
}
if (empty($filters)) {
	$pdoFetch->addTime('No filters retrieved');
	$output['filters'] = $modx->lexicon('mse2_err_no_filters');
	if (empty($output['results'])) {$output['results'] = $modx->lexicon('mse2_err_no_results');}
}
else {
	$pdoFetch->addTime('Filters retrieved');
	$request = array();
	foreach ($_GET as $k => $v) {
		$request[$k] = explode($mSearch2->config['values_delimeter'], $v);
	}

	foreach ($filters as $filter => $data) {
		if (empty($data)) {continue;}
		$tplOuter = !empty($scriptProperties['tplFilter.outer.'.$filter]) ? $scriptProperties['tplFilter.outer.'.$filter] : $scriptProperties['tplFilter.outer.default'];
		$tplRow = !empty($scriptProperties['tplFilter.row.'.$filter]) ? $scriptProperties['tplFilter.row.'.$filter] : $scriptProperties['tplFilter.row.default'];
		$tplEmpty = !empty($scriptProperties['tplFilter.empty.'.$filter]) ? $scriptProperties['tplFilter.empty.'.$filter] : '';

		// Caching chunk for quick placeholders
		$pdoFetch->getChunk($tplRow);

		$rows = $has_active = '';
		list($table,$filter2) = explode($mSearch2->config['filter_delimeter'], $filter);
		$idx = 0;
		foreach ($data as $v) {
			if (empty($v)) {continue;}
			$checked = isset($request[$filter]) && in_array($v['value'], $request[$filter]) && @$v['type'] != 'number';
			if ($scriptProperties['suggestions']) {
				if ($checked) {$num = ''; $has_active = 'has_active';}
				else if (isset($suggestions[$filter][$v['value']])) {
					$num = $suggestions[$filter][$v['value']];
				}
				else {
					$num = !empty($v['resources']) ? count($v['resources']) : '';
				}
			} else {$num = '';}

			$rows .= $pdoFetch->getChunk($tplRow, array(
				'filter' => $filter2
				,'table' => $table
				,'title' => $v['title']
				,'value' => $v['value']
				,'type' => $v['type']
				,'checked' => $checked ? 'checked' : ''
				,'selected' => $checked ? 'selected' : ''
				,'disabled' => !$checked && empty($num) && $scriptProperties['suggestions'] ? 'disabled' : ''
				,'delimeter' => $mSearch2->config['filter_delimeter']
				,'idx' => $idx++
				,'num' => $num
			), $pdoFetch->config['fastMode']);
		}

		$tpl = empty($rows) ? $tplEmpty : $tplOuter;
		$pdoFetch->getChunk($tpl);
		$output['filters'] .= $pdoFetch->getChunk($tpl, array(
			'filter' => $filter2
			,'table' => $table
			,'rows' => $rows
			,'has_active' => $has_active
			,'delimeter' => $mSearch2->config['filter_delimeter']
		), $pdoFetch->config['fastMode']);
	}

	if (empty($output['filters'])) {
		$output['filters'] = $modx->lexicon('mse2_err_no_filters');
		if (empty($output['results'])) {$output['results'] = $modx->lexicon('mse2_err_no_results');}
	}
	else {
		$pdoFetch->addTime('Filters templated');
	}
}

// Saving params into session for ajax requests
$_SESSION['mFilter2'][$modx->resource->id]['scriptProperties'] = $scriptProperties;

// Active class for sort links
if (!empty($sort)) {$output[$sort] = $classActive;}

// Setting values for frontend javascript
$modx->regClientStartupScript('<script type="text/javascript">
	mSearch2Config.start_sort = "'.$start_sort.'";
	mSearch2Config.start_limit= "'.$start_limit.'";
	mSearch2Config.start_page = "1";
	mSearch2Config.sort = "'.($sort == $start_sort ? '' : $sort).'";
	mSearch2Config.limit = "'.($limit == $start_limit ? '' : $limit).'";
	mSearch2Config.page = "'.$page.'";
	mSearch2Config.query = "'.@$_REQUEST[$queryVar].'";
</script>');

$pdoFetch->addTime('Total filter operations: '.$mSearch2->filter_operations);
// Process main chunk
$log = '';
if ($modx->user->hasSessionContext('mgr') && !empty($showLog)) {
	$log = '<pre class="mFilterLog">' . print_r($pdoFetch->getTime(), 1) . '</pre>';
}

if (!empty($toPlaceholder)) {
	$output['log'] = $log;
	$modx->setPlaceholders($output, $plPrefix);
}
else {
	$output = $pdoFetch->getChunk($scriptProperties['tplOuter'], $output, $pdoFetch->config['fastMode']);
	$output .= $log;

	return $output;
}