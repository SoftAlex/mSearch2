<?php

/* @var mSearch2 $mSearch2 */
$mSearch2 = $modx->getService('msearch2','mSearch2',$modx->getOption('msearch2_core_path',null,$modx->getOption('core_path').'components/msearch2/').'model/msearch2/',$scriptProperties);

$text = array('белого',"красный","синий");

$words = $mSearch2->getBaseForms($text, 1);
print_r($words);

echo '<br/><br/>';
$words = $mSearch2->getAllForms($text, 1);
print_r($words);

die;
