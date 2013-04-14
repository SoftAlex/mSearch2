<?php

/* @var mSearch2 $mSearch2 */
$mSearch2 = $modx->getService('msearch2','mSearch2',$modx->getOption('msearch2_core_path',null,$modx->getOption('core_path').'components/msearch2/').'model/msearch2/',$scriptProperties);

$text = '
Today we updated and streamlined the MODX Commercial Support and the MODX Development Services pages on modx.com as part of being clear and transparent on the business side of MODX. And, we`ve got a brand new Creative Perspective (part of an ongoing series) featuring community provacateur, Oliver Haase-Lobinger.

Установлено, что 13 апреля около 16:00 подозреваемый, находясь на рабочем месте, на протяжении около 10 минут совершал публичную демонстрацию видеоматериалов порнографического содержания посредством телевизионного вещания. После этого молодой человек скрылся, — цитируется на сайте сообщение Следственного комитета.
';

echo $words = $mSearch2->getBaseForms($text);
echo '<br/><br/>';
echo $words = $mSearch2->getAllForms($text);

die;
