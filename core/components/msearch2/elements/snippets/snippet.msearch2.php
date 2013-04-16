<?php

/* @var mSearch2 $mSearch2 */
$mSearch2 = $modx->getService('msearch2','mSearch2',$modx->getOption('msearch2.core_path',null,$modx->getOption('core_path').'components/msearch2/').'model/msearch2/',$scriptProperties);
/* @var pdoFetch $pdoFetch */
if (!empty($modx->services['pdofetch'])) {unset($modx->services['pdofetch']);}
$pdoFetch = $modx->getService('pdofetch','pdoFetch', MODX_CORE_PATH.'components/pdotools/model/pdotools/',$scriptProperties);
$pdoFetch->config['nestedChunkPrefix'] = 'msearch2_';
$pdoFetch->addTime('pdoTools loaded.');

$class = 'modResource';
if (empty($queryVar)) {$queryVar = 'query';}
if (empty($parentsVar)) {$parentsVar = 'parents';}
if (empty($minQuery)) {$minQuery = 3;}
if (empty($plPrefix)) {$plPrefix = 'mse2.';}
if (empty($depth)) {$depth = 10;}
if (empty($offset)) {$offset = 0;}
$returnIds = !empty($returnIds);
$fastMode = !empty($fastMode);

$query = @$_REQUEST[$queryVar];
if (empty($query) && isset($_REQUEST[$queryVar])) {
	return $modx->lexicon('mse2_err_no_query');
}
else if (strlen($query) < $minQuery && !empty($query)) {
	return $modx->lexicon('mse2_err_min_query');
}
else if (empty($query)) {
	return;
}
else {
	$modx->setPlaceholder($plPrefix.'query', $query);
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

if (!empty($parents)) {
	$pids = array_map('trim', explode(',', $parents));
	$parents = $pids;
	foreach ($pids as $v) {
		if (!is_numeric($v)) {continue;}
		$parents = array_merge($parents, $modx->getChildIds($v, $depth));
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
$pdoFetch->addTime('"Where" expression built.');
// End of building "Where" expression

// Joining tables
$leftJoin = array(
	'{"class":"mseIntro","alias":"Intro","on":"`modResource`.`id`=`Intro`.`resource`"}'
);

// Include TVs
$tvsLeftJoin = $tvsSelect = array();
if (!empty($includeTVs)) {
	$tvs = array_map('trim',explode(',',$includeTVs));
	if(!empty($tvs[0])){
		$q = $modx->newQuery('modTemplateVar', array('name:IN' => $tvs));
		$q->select('id,name');
		if ($q->prepare() && $q->stmt->execute()) {
			$tv_ids = $q->stmt->fetchAll(PDO::FETCH_ASSOC);
			if (!empty($tv_ids)) {
				foreach ($tv_ids as $tv) {
					$leftJoin[] = '{"class":"modTemplateVarResource","alias":"TV'.$tv['name'].'","on":"TV'.$tv['name'].'.contentid = '.$class.'.id AND TV'.$tv['name'].'.tmplvarid = '.$tv['id'].'"}';
					$tvsSelect[] = ' "TV'.$tv['name'].'":"`TV'.$tv['name'].'`.`value` as `'.$tvPrefix.$tv['name'].'`" ';
				}
			}
		}
		$pdoFetch->addTime('Included list of tvs: <b>'.implode(', ',$tvs).'</b>.');
	}
}
// End of including TVs

// Fields to select
$resourceColumns = !empty($includeContent) ?  $modx->getSelectColumns($class, $class) : $modx->getSelectColumns($class, $class, '', array('content'), true);
$select = array('"'.$class.'":"'.$resourceColumns.'"');
$select[] = '"Intro":"intro" ';
if (!empty($tvsSelect)) {$select = array_merge($select, $tvsSelect);}

// Default parameters
$default = array(
	'class' => $class
	,'where' => $modx->toJSON($where)
	,'leftJoin' => '['.implode(',',$leftJoin).']'
	,'select' => '{'.implode(',',$select).'}'
	,'sortby' => "find_in_set(`id`,'{$resources}')"
	,'sortdir' => ''
	//,'groupby' => $class.'.id'
	,'fastMode' => $fastMode
	,'return' => 'data'
);

// Merge all properties and run!
$pdoFetch->config = array_merge($pdoFetch->config, $default);
$pdoFetch->addTime('Query parameters are prepared.');
$rows = $pdoFetch->run();

//echo '<pre>';print_r($rows);die;

// Initializing chunk for template rows
if (!empty($tpl)) {
	$pdoFetch->getChunk($tpl);
}

$modificators = $modx->getOption('ms2_price_snippet', null, false, true) || $setting = $modx->getOption('ms2_weight_snippet', null, false, true);

// Processing rows
$output = null; $offset++;
if (!empty($rows) && is_array($rows)) {
	foreach ($rows as $k => $row) {
		// Processing main fields
		$row['intro'] = $mSearch2->Highlight($row['intro'], $query);
		$row['weight'] = $found[$row['id']];
		$row['num'] = $offset++;

		// Processing quick fields
		if (!empty($tpl)) {
			$pl = $pdoFetch->makePlaceholders($row);
			$qfields = array_keys($pdoFetch->elements[$tpl]['placeholders']);
			foreach ($qfields as $field) {
				if (!empty($row[$field])) {
					$row[$field] = str_replace($pl['pl'], $pl['vl'], $pdoFetch->elements[$tpl]['placeholders'][$field]);
				}
				else {
					$row[$field] = '';
				}
			}
		}

		// Processing chunk
		$output[] = empty($tpl)
			? '<pre>'.str_replace(array('[',']','`'), array('&#91;','&#93;','&#96;'), htmlentities(print_r($row, true), ENT_QUOTES, 'UTF-8')).'</pre>'
			: $pdoFetch->getChunk($tpl, $row, $pdoFetch->config['fastMode']);
	}
	$pdoFetch->addTime('Returning processed chunks');
	if (empty($outputSeparator)) {$outputSeparator = "\n";}
	if (!empty($output)) {
		$output = implode($outputSeparator, $output);
	}
}

if ($modx->user->hasSessionContext('mgr') && !empty($showLog)) {
	$output .= '<pre class="msProductsLog">' . print_r($pdoFetch->getTime(), 1) . '</pre>';
}

// Return output
if (!empty($toPlaceholder)) {
	$modx->setPlaceholder($toPlaceholder, $output);
}
else {
	return $output;
}
