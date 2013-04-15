<?php
/**
 * The base class for mSearch2.
 *
 * @package msearch2
 */

class mSearch2 {
	/* @var modX $modx */
	public $modx;
	/* @var mSearch2ControllerRequest $request */
	protected $request;
	public $initialized = array();
	public $phpMorphy = array();


	function __construct(modX &$modx,array $config = array()) {
		$this->modx =& $modx;

		$corePath = $this->modx->getOption('msearch2_core_path', $config, $this->modx->getOption('core_path').'components/msearch2/');
		$assetsUrl = $this->modx->getOption('msearch2_assets_url', $config, $this->modx->getOption('assets_url').'components/msearch2/');
		$connectorUrl = $assetsUrl.'connector.php';

		$this->config = array_merge(array(
			'assetsUrl' => $assetsUrl
			,'cssUrl' => $assetsUrl.'css/'
			,'jsUrl' => $assetsUrl.'js/'
			,'imagesUrl' => $assetsUrl.'images/'

			,'connectorUrl' => $connectorUrl

			,'corePath' => $corePath
			,'modelPath' => $corePath.'model/'
			//,'chunksPath' => $corePath.'elements/chunks/'
			,'templatesPath' => $corePath.'elements/templates/'
			//,'snippetsPath' => $corePath.'elements/snippets/'
			,'processorsPath' => $corePath.'processors/'

			,'languages' => array(
				'ru_RU' => array(
					'storage' => 'file'
				)
				,'en_EN' => array(
					'storage' => 'file'
				)
			)
			,'min_word_length' => 3
		), $config);

		if (!is_array($this->config['languages'])) {
			$this->config['languages'] = $modx->fromJSON($this->config['languages']);
		}

		$this->modx->addPackage('msearch2', $this->config['modelPath']);
		$this->modx->lexicon->load('msearch2:default');
	}


	/**
	 * Initializes mSearch2 into different contexts.
	 *
	 * @access public
	 * @param string $ctx The context to load. Defaults to web.
	 */
	public function initialize($ctx = 'web') {
		switch ($ctx) {
			case 'mgr':
				if (!$this->modx->loadClass('msearch2.request.mSearch2ControllerRequest', $this->config['modelPath'], true, true)) {
					return 'Could not load controller request handler.';
				}
				$this->request = new mSearch2ControllerRequest($this);
				return $this->request->handleRequest();
			break;
			default:

		}

		return true;
	}


	/* Initializes phpMorphy for needed language
	 *
	 * @param $lang
	 * @return boolean
	 * */
	public function loadPhpMorphy() {
		require_once $this->config['corePath'] . 'phpmorphy/src/common.php';

		foreach ($this->config['languages'] as $lang => $options) {
			if (!empty($this->phpMorphy[$lang]) && $this->phpMorphy[$lang] instanceof phpMorphy) {
				return true;
			}
			else {
				try {
					$this->phpMorphy[$lang] = new phpMorphy(
						$this->config['corePath'] . 'phpmorphy/dicts/'
						,$lang
						,$options
					);
				} catch (phpMorphy_Exception $e) {
					$this->modx->log(modX::LOG_LEVEL_ERROR, '[mSearch2] Could not initialize phpMorphy for language .'.$lang.': .'.$e->getMessage());
				}
			}
		}
		return true;
	}


	/*
	 * Gets base form of the words
	 *
	 * @param array|string $text
	 *
	 * @return array|string
	 * */
	function getBaseForms($text) {
		$result = array();
		if (is_array($text)) {
			foreach ($text as $v) {
				$result = array_merge($result, $this->getBaseForms($v));
			}
		}
		else {
			$text = str_ireplace('ё', 'е', $this->modx->stripTags($text));
			$words = preg_replace('#\[.*\]#isU', '', $text);
			$words = preg_split('#\s|[,.:;!?"\'\\\/()]#', $words, -1, PREG_SPLIT_NO_EMPTY);

			$bulk_words = array();
			foreach ($words as $v) {
				if (mb_strlen($v,'UTF-8') > $this->config['min_word_length'])
					$bulk_words[] = mb_strtoupper($v, 'UTF-8');
			}

			$this->loadPhpMorphy();
			/* @var phpMorphy $phpMorphy */
			$base_forms = array();
			foreach ($this->phpMorphy as $phpMorphy) {
				$locale = $phpMorphy->getLocale();
				$base_forms[$locale] = $phpMorphy->getBaseForm($bulk_words);
			}

			$result = array();
			if (!empty($base_forms)) {
				foreach ($base_forms as $lang) {
					if (!empty($lang)) {
						foreach ($lang as $forms) {
							if (!empty($forms)) {
								foreach ($forms as $form) {
									if (mb_strlen($form,'UTF-8') > $this->config['min_word_length']) {
										$result[$form] = 1;
									}
								}
							}
						}
					}
				}
			}
			$result = array_keys($result);
		}

		return $result;
	}


	/*
	 * Gets all morphological forms of the words
	 *
	 * @param array|string $text
	 *
	 * @return array|string
	 */
	function getAllForms($text) {
		$result = array();
		if (is_array($text)) {
			foreach ($text as $v) {
				$result = array_merge($result, $this->getAllForms($v));
			}
		}
		else {
			$text = str_ireplace('ё', 'е', $this->modx->stripTags($text));
			$words = preg_split('#\s|[,.:;!?"\'\\\/()]#', $text, -1, PREG_SPLIT_NO_EMPTY);
			$bulk_words = array();
			foreach ($words as $v) {
				if (mb_strlen($v,'UTF-8') > $this->config['min_word_length']) {
					$bulk_words[] = mb_strtoupper($v, 'UTF-8');
				}
			}

			$this->loadPhpMorphy();
			/* @var phpMorphy $phpMorphy */
			$all_forms = array();
			foreach ($this->phpMorphy as $phpMorphy) {
				$locale = $phpMorphy->getLocale();
				$all_forms[$locale] = $phpMorphy->getAllForms($bulk_words);
			}

			$result = array();
			if (!empty($all_forms)) {
				foreach ($all_forms as $lang) {
					if (!empty($lang)) {
						foreach ($lang as $word => $forms) {
							if (!empty($forms)) {
								$result[$word] = array_key_exists($word, $result) ? array_merge($result['$word'], $forms) : $forms;
							}
						}
					}
				}
			}
		}

		return $result;
	}

}