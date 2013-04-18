<?php
/**
 * Build the setup options form.
 */
$exists = false;
$output = null;
switch ($options[xPDOTransport::PACKAGE_ACTION]) {
	case xPDOTransport::ACTION_INSTALL:

	case xPDOTransport::ACTION_UPGRADE:
		$exists =
			$modx->getObject('transport.modTransportPackage', array('package_name' => 'pdoTools'))
			&&
			file_exists(MODX_CORE_PATH . 'components/msearch2/phpmorphy/dicts/.installed');
		break;

	case xPDOTransport::ACTION_UNINSTALL: break;
}

if (!$exists) {
	switch ($modx->getOption('manager_language')) {
		case 'ru':
			$output = 'Этот компонент требует <b>pdoTools</b> для быстрой работы сниппетов, и словари <b>phpMorphy</b> для поиска по морфологии.<br/><br/>Могу я автоматически скачать и установить их?';
			break;
		default:
			$output = 'This component requires <b>pdoTools</b> for fast work of snippets, and <b>phpMorphy</b> dictionaries for morphological search.<br/><br/>Can i automaticly download and install them?';
	}

}

return $output;