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
if (empty($minQuery)) {$minQuery = $modx->getOption('index_min_words_length', null, 3, true);}
if (empty($depth)) {$depth = 10;}
if (empty($classActive)) {$classActive = 'active';}
$output = array('filters' => '', 'results' => '');

$ids = array();
$query = @$_REQUEST[$queryVar];
if (!empty($resources)) {
	$ids = array_map('trim', explode(',', $resources));
}
else if (isset($_REQUEST[$queryVar]) && empty($query)) {
	return $modx->lexicon('mse2_err_no_query');
}
else if (isset($_REQUEST[$queryVar]) && mb_strlen($query,'UTF-8') < $minQuery) {
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
	$pdoFetch->addTime('Searched for ids: ('.implode(',',$ids).')');
}

// Filter by parents
if (!empty($parents) && $parents > 0) {
	$pids = array_map('trim', explode(',', $parents));
	$parents = $pids;
	if (!empty($depth) && $depth > 0) {
		foreach ($pids as $v) {
			if (!is_numeric($v)) {continue;}
			$parents = array_merge($parents, $modx->getChildIds($v, $depth));
		}
	}

	$ids = !empty($ids) ? array_intersect($ids,$parents) : $parents;
	$pdoFetch->addTime('Filtered ids by parents: ('.implode(',',$ids).')');
}



// Start building "Where" expression
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

// Saving params into session for ajax requests
$_SESSION['mFilter2'][$modx->resource->id]['scriptProperties'] = $scriptProperties;
// Saving log
$log = $pdoFetch->timings;
$pdoFetch->timings = array();


// ---------------------- Loading results
$sort = '';
$suggestions = array();
$page = '';
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
			)
		);

		if (!empty($_REQUEST['sort'])) {
			$sort = $_REQUEST['sort'];
		}
		else if (!empty($scriptProperties['sort'])) {
			$tmp = array_map('trim', explode(',',$scriptProperties['sort']));
			$sort = implode(',', $tmp);
		}
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

		if (!empty($sort)) {
			$paginatorProperties['sortby'] = $mSearch2->getSortFields($sort);
			$paginatorProperties['sortdir'] = '';
		}

		$_SESSION['mFilter2'][$modx->resource->id]['paginatorProperties'] = $paginatorProperties;

		if (!empty($_GET)) {
			$matched = $mSearch2->Filter($ids, $_REQUEST);
			if (empty($scriptProperties['disableSuggestions'])) {
				$suggestions = $mSearch2->getSuggestions($ids, $_REQUEST, $matched);
			}
			$paginatorProperties['resources'] = is_array($ids) ? implode(',', $matched) : $matched;
		}
		$paginator->setProperties($paginatorProperties);
		$paginator->setCacheable(false);
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
$pdoFetch->addTime('Getting filters for ids: ('.implode(',',$ids).')');
$filters = '';
if (!empty($ids)) {
	$filters = $mSearch2->getFilters($ids);
}
if (empty($filters)) {
	$pdoFetch->addTime('No filters retrieved');
	$output['filters'] = $modx->lexicon('mse2_err_no_filters');
}
else {
	$pdoFetch->addTime('Filters retrieved');

	$request = array();
	foreach ($_GET as $k => $v) {
		$request[$k] = explode(',',$v);
	}

	foreach ($filters as $filter => $data) {
		if (empty($data)) {continue;}
		$tplOuter = !empty($scriptProperties['tplFilter.outer.'.$filter]) ? $scriptProperties['tplFilter.outer.'.$filter] : $scriptProperties['tplFilter.outer.default'];
		$tplRow = !empty($scriptProperties['tplFilter.row.'.$filter]) ? $scriptProperties['tplFilter.row.'.$filter] : $scriptProperties['tplFilter.row.default'];
		$tplEmpty = !empty($scriptProperties['tplFilter.empty.'.$filter]) ? $scriptProperties['tplFilter.empty.'.$filter] : '';

		// Caching chunk for quick placeholders
		$pdoFetch->getChunk($tplRow);

		$rows = '';
		list($table,$filter2) = explode($mSearch2->config['filter_delimeter'], $filter);
		$idx = 0;
		foreach ($data as $v) {
			if (empty($v)) {continue;}
			$checked = isset($request[$filter]) && in_array($v['value'], $request[$filter]);
			if (empty($scriptProperties['disableSuggestions'])) {
				if ($checked) {$num = '';}
				else if (!empty($suggestions[$filter][$v['value']])) {
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
			,'delimeter' => $mSearch2->config['filter_delimeter']
		), $pdoFetch->config['fastMode']);
	}

	if (empty($output['filters'])) {$output['filters'] = $modx->lexicon('mse2_err_no_filters');}
	else {
		$pdoFetch->addTime('Filters templated');
	}
}

// Active class for sort links
$output[$sort] = $classActive;

// Setting values for frontend javascript
$modx->regClientStartupScript('<script type="text/javascript">
	mSearch2Config.start_sort = "'.$scriptProperties['sort'].'";
	mSearch2Config.start_page = 1;
	mSearch2Config.sort = "'.$sort.'";
	mSearch2Config.page = "'.$page.'";
	mSearch2Config.delimeter = "'.$mSearch2->config['filter_delimeter'].'";
	mSearch2Config.query = "'.@$_REQUEST[$queryVar].'";
</script>');

// Process main chunk
$output = $pdoFetch->getChunk($scriptProperties['tplOuter'], $output, $pdoFetch->config['fastMode']);
if ($modx->user->hasSessionContext('mgr') && !empty($showLog)) {
	$output .= '<pre class="mFilterLog">' . print_r($pdoFetch->getTime(), 1) . '</pre>';
}

return $output;