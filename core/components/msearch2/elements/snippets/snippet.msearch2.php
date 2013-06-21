<?php
/* @var mSearch2 $mSearch2 */
$mSearch2 = $modx->getService('msearch2','mSearch2',$modx->getOption('msearch2.core_path',null,$modx->getOption('core_path').'components/msearch2/').'model/msearch2/',$scriptProperties);
/* @var pdoFetch $pdoFetch */
$pdoFetch = $modx->getService('pdofetch','pdoFetch', MODX_CORE_PATH.'components/pdotools/model/pdotools/',$scriptProperties);
$pdoFetch->addTime('pdoTools loaded.');

$class = 'modResource';
if (empty($queryVar)) {$queryVar = 'query';}
if (empty($parentsVar)) {$parentsVar = 'parents';}
if (empty($minQuery)) {$minQuery = $modx->getOption('index_min_words_length', null, 3, true);}
if (empty($depth)) {$depth = 10;}
if (empty($offset)) {$offset = 0;}
if (empty($htagOpen)) {$htagOpen = '<b>';}
if (empty($htagClose)) {$htagClose = '</b>';}
if (empty($outputSeparator)) {$outputSeparator = "\n";}
$returnIds = !empty($returnIds);
$fastMode = !empty($fastMode);

$found = array();
$query = @$_REQUEST[$queryVar];
if (empty($resources)) {
	if (empty($query) && isset($_REQUEST[$queryVar])) {
		return $modx->lexicon('mse2_err_no_query');
	}
	else if (mb_strlen($query,'UTF-8') < $minQuery && !empty($query)) {
		return $modx->lexicon('mse2_err_min_query');
	}
	else if (empty($query)) {
		return;
	}
	else {
		$query = htmlspecialchars(strip_tags(trim($query)));
		$modx->setPlaceholder('mse2_'.$queryVar, $query);
	}

	$found = $mSearch2->Search($query);
	$ids = array_keys($found);
	$resources = implode(',', $ids);

	if ($returnIds) {
		return !empty($resources) ? $resources : '0';
	}
	else if (empty($found)) {
		return $modx->lexicon('mse2_err_no_results');
	}
}

/*----------------------------------------------------------------------------------*/

// Start building "Where" expression
$where = array("id IN ({$resources})");
if (empty($showUnpublished)) {$where['published'] = 1;}
if (empty($showHidden)) {$where['hidemenu'] = 0;}
if (empty($showDeleted)) {$where['deleted'] = 0;}

// Filter by parents
if (!empty($scriptProperties[$parentsVar]) && $scriptProperties[$parentsVar] > 0) {
	$parents = $scriptProperties[$parentsVar];
}
else if (!empty($_REQUEST[$parentsVar])) {
	$parents = $modx->stripTags($_REQUEST[$parentsVar]);
}
else {$parents = 0;}

if (!empty($parents) && $parents > 0) {
	$pids = array_map('trim', explode(',', $parents));
	$parents = $pids;
	if (!empty($depth) && $depth > 0) {
		foreach ($pids as $v) {
			if (!is_numeric($v)) {continue;}
			$parents = array_merge($parents, $modx->getChildIds($v, $depth));
		}
	}

	if (!empty($parents)) {
		$where['parent:IN'] = $parents;
	}
}

// Adding custom where parameters
if (!empty($scriptProperties['where'])) {
	$tmp = $modx->fromJSON($scriptProperties['where']);
	if (is_array($tmp)) {
		$where = array_merge($where, $tmp);
	}
}
unset($scriptProperties['where']);
$pdoFetch->addTime('"Where" expression built.');
// End of building "Where" expression

// Joining tables
$leftJoin = array(
	'{"class":"mseIntro","alias":"Intro","on":"`modResource`.`id`=`Intro`.`resource`"}'
);

// Fields to select
$resourceColumns = !empty($includeContent) ?  $modx->getSelectColumns($class, $class) : $modx->getSelectColumns($class, $class, '', array('content'), true);
$select = array('"'.$class.'":"'.$resourceColumns.'"');
$select[] = '"Intro":"intro" ';

// Default parameters
$default = array(
	'class' => $class
	,'where' => $modx->toJSON($where)
	,'leftJoin' => '['.implode(',',$leftJoin).']'
	,'select' => '{'.implode(',',$select).'}'
	,'sortby' => !empty($sortby) ? $sortby : "find_in_set(`id`,'{$resources}')"
	,'sortdir' => !empty($sortdir) ? $sortdir : ''
	//,'groupby' => $class.'.id'
	,'fastMode' => $fastMode
	,'return' => 'data'
	,'nestedChunkPrefix' => 'msearch2_'
);

// Merge all properties and run!
$pdoFetch->setConfig(array_merge($default,$scriptProperties));
$pdoFetch->addTime('Query parameters are prepared.');
$rows = $pdoFetch->run();

// Initializing chunk for template rows
if (!empty($tpl)) {$pdoFetch->getChunk($tpl);}

// Processing rows
$output = null; $offset++;
if (!empty($rows) && is_array($rows)) {
	foreach ($rows as $k => $row) {
		// Processing main fields
		$row['weight'] = @$found[$row['id']];
		$row['intro'] = $mSearch2->Highlight($row['intro'], $query, $htagOpen, $htagClose);
		$row['idx'] = $offset++;

		// Processing chunk
		$output[] = empty($tpl)
			? '<pre>'.str_replace(array('[',']','`'), array('&#91;','&#93;','&#96;'), htmlentities(print_r($row, true), ENT_QUOTES, 'UTF-8')).'</pre>'
			: $pdoFetch->getChunk($tpl, $row, $pdoFetch->config['fastMode']);
	}
	$pdoFetch->addTime('Returning processed chunks');
	if (!empty($output)) {
		$output = implode($outputSeparator, $output);
	}
}

if ($modx->user->hasSessionContext('mgr') && !empty($showLog)) {
	$output .= '<pre class="mSearchLog">' . print_r($pdoFetch->getTime(), 1) . '</pre>';
}

// Return output
if (!empty($toPlaceholder)) {
	$modx->setPlaceholder($toPlaceholder, $output);
}
else {
	return $output;
}