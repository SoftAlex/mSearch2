<?php
/**
 * mSearch2 Connector
 *
 * @package msearch2
 */
require_once dirname(dirname(dirname(dirname(__FILE__)))).'/config.core.php';
require_once MODX_CORE_PATH.'config/'.MODX_CONFIG_KEY.'.inc.php';
require_once MODX_CONNECTORS_PATH.'index.php';

$corePath = $modx->getOption('msearch2.core_path',null,$modx->getOption('core_path').'components/msearch2/');
require_once $corePath.'model/msearch2/msearch2.class.php';
$modx->mSearch2 = new mSearch2($modx);

$modx->lexicon->load('msearch2:default');

/* handle request */
$path = $modx->getOption('processorsPath',$modx->mSearch2->config, $corePath.'processors/');
$modx->request->handleRequest(array(
    'processors_path' => $path,
    'location' => '',
));