<?php

$success= false;
switch ($options[xPDOTransport::PACKAGE_ACTION]) {
	case xPDOTransport::ACTION_INSTALL:
	case xPDOTransport::ACTION_UPGRADE:
		/* @var modX $modx */
		$modx = & $object->xpdo;
		/* Checking and installing required packages */
		$packages = array(
			'pdoTools' => array(
				'version_major' => 1
				,'version_minor' => 3
			)
		);

		foreach ($packages as $package => $options) {
			$query = array('package_name' => $package);
			if (!empty($options)) {$query = array_merge($query, $options);}
			if (!$modx->getObject('transport.modTransportPackage', $query)) {
				$modx->log(modX::LOG_LEVEL_INFO, 'Trying to install <b>'.$package.'</b>. Please wait...');

				$response = installPackage($package);
				if ($response['success']) {$level = modX::LOG_LEVEL_INFO;}
				else {$level = modX::LOG_LEVEL_ERROR;}

				$modx->log($level, $response['message']);
			}
		}

		$path = MODX_CORE_PATH . 'components/msearch2/';
		if (!file_exists($path .'/phpmorphy/dicts/.installed')) {
			//if (!file_exists($path)) {
				@mkdir($path);
				@mkdir($path.'phpmorphy/');
				@mkdir($path.'phpmorphy/dicts/');
			//}

			require MODX_CORE_PATH . 'xpdo/compression/pclzip.lib.php';
			$dicts = $path.'phpmorphy/dicts/';
			$src_ru = 'http://downloads.sourceforge.net/project/phpmorphy/phpmorphy-dictionaries/0.3.x/ru_RU/morphy-0.3.x-ru_RU-nojo-utf8.zip';
			$dst_ru = $dicts . 'dict_ru.zip';
			$src_en = 'http://downloads.sourceforge.net/project/phpmorphy/phpmorphy-dictionaries/0.3.x/en_EN/morphy-0.3.x-en_EN-windows-1250.zip';
			$dst_en = $dicts . 'dict_en.zip';

			$modx->log(modX::LOG_LEVEL_INFO, 'Trying to download <b>russian</b> dictionary. Please wait...');
			if (!file_exists($dst_ru)) {download($src_ru, $dst_ru);}
			$file = new PclZip($dst_ru);
			if ($ru = $file->extract(PCLZIP_OPT_PATH, $dicts)) {unlink($dst_ru);}
			else {$modx->log(xPDO::LOG_LEVEL_ERROR, 'Could not extract dictionaries from '.$dst_ru.' to '.$dicts.'. Error: '.$file->errorInfo());}


			$modx->log(modX::LOG_LEVEL_INFO, 'Trying to download <b>english</b> dictionary. Please wait...');
			if (!file_exists($dst_en)) {download($src_en, $dst_en);}
			$file = new PclZip($dst_en);
			if ($en = $file->extract(PCLZIP_OPT_PATH, $dicts)) {unlink($dst_en);}
			else {$modx->log(xPDO::LOG_LEVEL_ERROR, 'Could not extract dictionaries from '.$dst_en.' to '.$dicts.'. Error: '.$file->errorInfo());}

			if ($ru && $en) {
				file_put_contents($path . 'phpmorphy/dicts/.installed', date('Y-m-d H:i:s'));
			}
		}

		$success = true;
		break;

	case xPDOTransport::ACTION_UNINSTALL:
		$success= true;
		break;
}
return $success;



/*---------------------------------*/
function installPackage($packageName) {
	global $modx;

	/* @var modTransportProvider $provider */
	if (!$provider = $modx->getObject('transport.modTransportProvider', array('service_url:LIKE' => '%simpledream%'))) {
		$provider = $modx->getObject('transport.modTransportProvider', 1);
	}

	$provider->getClient();
	$modx->getVersionData();
	$productVersion = $modx->version['code_name'].'-'.$modx->version['full_version'];

	$response = $provider->request('package','GET',array(
		'supports' => $productVersion,
		'query' => $packageName
	));

	if(!empty($response)) {
		$foundPackages = simplexml_load_string($response->response);
		foreach($foundPackages as $foundPackage) {
			/* @var modTransportPackage $foundPackage */
			if($foundPackage->name == $packageName) {
				$sig = explode('-',$foundPackage->signature);
				$versionSignature = explode('.',$sig[1]);
				$url = $foundPackage->location;

				if (!download($url, $modx->getOption('core_path').'packages/'.$foundPackage->signature.'.transport.zip')) {
					return array(
						'success' => 0
						,'message' => 'Could not download package <b>'.$packageName.'</b>.'
					);
				}

				/* add in the package as an object so it can be upgraded */
				/** @var modTransportPackage $package */
				$package = $modx->newObject('transport.modTransportPackage');
				$package->set('signature',$foundPackage->signature);
				$package->fromArray(array(
					'created' => date('Y-m-d h:i:s'),
					'updated' => null,
					'state' => 1,
					'workspace' => 1,
					'provider' => 1,
					'source' => $foundPackage->signature.'.transport.zip',
					'package_name' => $sig[0],
					'version_major' => $versionSignature[0],
					'version_minor' => !empty($versionSignature[1]) ? $versionSignature[1] : 0,
					'version_patch' => !empty($versionSignature[2]) ? $versionSignature[2] : 0,
				));

				if (!empty($sig[2])) {
					$r = preg_split('/([0-9]+)/',$sig[2],-1,PREG_SPLIT_DELIM_CAPTURE);
					if (is_array($r) && !empty($r)) {
						$package->set('release',$r[0]);
						$package->set('release_index',(isset($r[1]) ? $r[1] : '0'));
					} else {
						$package->set('release',$sig[2]);
					}
				}

				if($package->save() && $package->install()) {
					return array(
						'success' => 1
						,'message' => '<b>'.$packageName.'</b> was successfully installed'
					);
				}
				else {
					return array(
						'success' => 0
						,'message' => 'Could not save package <b>'.$packageName.'</b>'
					);
				}
				break;
			}
		}
	}
	else {
		return array(
			'success' => 0
			,'message' => 'Could not find <b>'.$packageName.'</b> in MODX repository'
		);
	}
	return true;
}



function download($src, $dst) {
	if (ini_get('allow_url_fopen')) {
		$file = @file_get_contents($src);
	}
	else if (function_exists('curl_init')) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $src);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT,180);
		$safeMode = @ini_get('safe_mode');
		$openBasedir = @ini_get('open_basedir');
		if (empty($safeMode) && empty($openBasedir)) {
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		}

		$file = curl_exec($ch);
		curl_close($ch);
	}
	else {
		return false;
	}
	file_put_contents($dst, $file);

	return file_exists($dst);
}