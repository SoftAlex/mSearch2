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
	//public $initialized = array();
	public $phpMorphy = array();


	function __construct(modX &$modx,array $config = array()) {
		$this->modx =& $modx;

		$corePath = $this->modx->getOption('msearch2.core_path', $config, $this->modx->getOption('core_path').'components/msearch2/');
		$assetsUrl = $this->modx->getOption('msearch2.assets_url', $config, $this->modx->getOption('assets_url').'components/msearch2/');
		$connectorUrl = $assetsUrl.'connector.php';

		$this->config = array_merge(array(
			'assetsUrl' => $assetsUrl
			,'cssUrl' => $assetsUrl.'css/'
			,'jsUrl' => $assetsUrl.'js/'
			,'imagesUrl' => $assetsUrl.'images/'

			,'connectorUrl' => $connectorUrl

			,'corePath' => $corePath
			,'modelPath' => $corePath.'model/'
			,'templatesPath' => $corePath.'elements/templates/'
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
			,'text_cut_before' => 50
			,'text_cut_after' => 250
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
			foreach ($base_forms as $lang) {
				if (!empty($lang)) {
					foreach ($lang as $forms) {
						if (!$forms) {$forms = $bulk_words;}
						foreach ($forms as $form) {
							if (mb_strlen($form,'UTF-8') > $this->config['min_word_length']) {
								$result[$form] = 1;
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


	/**
	 * Search and return array with resources ids as a key and sum of weight as value
	 *
	 * @param $query
	 *
	 * @return array
	 */
	public function Search($query) {
		$string = preg_replace('/[^_-а-яёa-z0-9\s\.]+/iu', ' ', $this->modx->stripTags($query));
		$worms = $this->getBaseForms($string);

		$result = array();
		$q = $this->modx->newQuery('mseWord');
		$q->select('`resource`, SUM(`weight`) as `weight`');
		$q->groupby('`resource`');
		$q->where(array('word:IN' => $worms));
		$q->sortby('weight','DESC');
		if ($q->prepare() && $q->stmt->execute()) {
			while ($row = $q->stmt->fetch(PDO::FETCH_ASSOC)) {
				$result[$row['resource']] = $row['weight'];
			}
		}

		return $result;
	}


	/**
	 * Highlight of the string
	 * */
	function Highlight($text, $query) {
		$all_forms = $this->getAllForms($query);

		$text_cut = ''; $words = array();
		foreach ($all_forms as $forms) {
			foreach ($forms as $form) {
				// Cutting text on first occurrence
				if (empty($text_cut) && preg_match('/'.$form.'/imu', $text, $matches)) {
					$pos = mb_strpos($text, $matches[0], 0, 'UTF-8');
					if ($pos >= $this->config['text_cut_before']) {
						$text_cut = '... ';
						$pos -= $this->config['text_cut_before'];
					}
					else {
						$text_cut = '';
						$pos = 0;
					}
					$text_cut .= mb_substr($text, $pos, $this->config['text_cut_after'], 'UTF-8');
					if (mb_strlen($text,'UTF-8') > $this->config['text_cut_after']) {$text_cut .= ' ...';}

					break;
				}
			}
			$words = array_merge($words, $forms);
		}

		preg_match_all('/(?:\s|)('.implode('|',$words).')[^а-яёa-z0-9]/imu', $text_cut, $matches);
		$from = $to = array();
		foreach ($matches[0] as $v) {
			$string = trim($v);
			$from[$string] = $string;
			$to[$string] = '<span class="highlight">'.$string.'</span>';
		}

		return str_replace($from, $to, $text_cut);
	}

	/**
	 * Recursive implode
	 *
	 * @param $glue
	 * @param array $array
	 *
	 * @return string
	 */
	function implode_r($glue, array $array) {
		$result = array();
		foreach ($array as $v) {
			$result[] = is_array($v) ? $this->implode_r($glue, $v) : $v;
		}

		return implode($glue, $result);
	}

}