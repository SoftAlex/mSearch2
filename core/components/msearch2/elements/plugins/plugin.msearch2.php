<?php

$id = 0;

switch ($modx->event->name) {

	case 'OnDocFormSave':
	case 'OnResourceDelete':
	case 'OnResourceUndelete':
		/* @var modResource $modResource */
		if (!empty($resource) && $resource instanceof modResource) {
			$id = $resource->get('id');
		}
	break;

	case 'OnCommentSave':
	case 'OnCommentRemove':
	case 'OnCommentDelete':
		/* @var TicketComment $TicketComment */
		if (!empty($TicketComment) && $TicketComment instanceof TicketComment) {
			$id = $TicketComment->getOne('Thread')->get('resource');
		}
	break;

}


if (!empty($id)) {
	/* @var modProcessorResponse $response */
	$response = $modx->runProcessor('mgr/index/update', array('id' => $id), array('processors_path' => MODX_CORE_PATH . 'components/msearch2/processors/'));

	if ($response->isError()) {
		$modx->log(modX::LOG_LEVEL_ERROR, print_r($response->getAllErrors(), true));
	}
}