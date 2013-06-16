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
	/* @var mse2FiltersHandler $filtersHandler */
	protected $filtersHandler = array();
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
			,'customPath' => $corePath.'custom/'

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
			,'min_word_length' => $this->modx->getOption('mse2_index_min_words_length', null, 3, true)
			,'exact_match_bonus' => $this->modx->getOption('mse2_search_exact_match_bonus', null, 5, true)
			,'all_words_bonus' => $this->modx->getOption('mse2_search_all_words_bonus', null, 5, true)
			,'introCutBefore' => 50
			,'introCutAfter' => 250
			,'filter_delimeter' => '/'
			,'method_delimeter' => ':'
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
	 *
	 * @return boolean
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


	/**
	 * Method loads custom classes from specified directory
	 *
	 * @var string $dir Directory for load classes
	 * @return void
	 */
	public function loadCustomClasses($dir) {
		$files = scandir($this->config['customPath'] . $dir);
		foreach ($files as $file) {
			if (preg_match('/.*?\.class\.php$/i', $file)) {
				include_once($this->config['customPath'] . $dir . '/' . $file);
			}
		}
	}


	/**
	 * Initializes phpMorphy for needed language
	 *
	 * @param $lang
	 *
	 * @return boolean
	 */
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
					return false;
				}
			}
		}

		return true;
	}


	/**
	 * Returns array with words for search
	 *
	 * @param string $text
	 *
	 * @return array
	 */
	public function getBulkWords($text = '') {
		$words = preg_split('#\s|[,.:;!?"\'\\\/()]#', $text, -1, PREG_SPLIT_NO_EMPTY);
		$bulk_words = array();
		foreach ($words as $v) {
			if (mb_strlen($v,'UTF-8') > $this->config['min_word_length']) {
				$word = mb_strtoupper($v, 'UTF-8');
				$bulk_words[$word] = $word;
			}
		}
		return $bulk_words;
	}


	/**
	 * Gets base form of the words
	 *
	 * @param array|string $text
	 *
	 * @return array|string
	 */
	function getBaseForms($text, $only_words = 1) {

		$result = array();
		if (is_array($text)) {
			foreach ($text as $v) {
				$result = array_merge($result, $this->getBaseForms($v));
			}
		}
		else {
			$text = str_ireplace('ё', 'е', $this->modx->stripTags($text));
			$text = preg_replace('#\[.*\]#isU', '', $text);

			$bulk_words = $this->getBulkWords($text);
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
					foreach ($lang as $word => $forms) {
						if (!$forms) {$forms = array($word);}
						foreach ($forms as $form) {
							if (mb_strlen($form,'UTF-8') > $this->config['min_word_length']) {
								$result[$form] = $word;
							}
						}
					}
				}
			}
			if ($only_words) {
				$result = array_keys($result);
			}
		}

		return $result;
	}


	/**
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

			$bulk_words = $this->getBulkWords($text);
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
								$result[$word] = isset($result[$word]) ? array_merge($result['$word'], $forms) : $forms;
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
		$words = $this->getBaseForms($string, 0);
		$bulk_words = array_unique(array_values($words));

		$result = $all_words = array();
		$q = $this->modx->newQuery('mseWord');
		$q->select('`resource`, `word`, `weight`');
		$q->where(array('word:IN' => array_keys($words)));
		if ($q->prepare() && $q->stmt->execute()) {
			while ($row = $q->stmt->fetch(PDO::FETCH_ASSOC)) {
				if (isset($result[$row['resource']])) {
					$result[$row['resource']] += $row['weight'];
				}
				else {
					$result[$row['resource']] = (int) $row['weight'];
				}

				if (isset($words[$row['word']])) {
					@$all_words[$row['resource']][$words[$row['word']]] = 1;
				}

			}
		}

		if (count($bulk_words) > 1) {
			// Exact match bonus
			$exact = $this->simpleSearch($query);
			foreach ($exact as $v) {
				if (isset($result[$v])) {
					$result[$v] += $this->config['exact_match_bonus'];
				}
				else {
					$result[$v] = $this->config['exact_match_bonus'];
				}
			}

			// All words bonus
			foreach ($all_words as $k => $v) {
				if (count($bulk_words) == count($v)) {
					$result[$k] += $this->config['all_words_bonus'];
				}
			}
		}

		arsort($result);
		return $result;
	}


	/**
	 * Search and return array with resources that matched for LIKE search
	 *
	 * @param $query
	 *
	 * @return array
	 */
	public function simpleSearch($query) {
		$string = preg_replace('/[^_-а-яёa-z0-9\s\.]+/iu', ' ', $this->modx->stripTags($query));

		$result = array();
		$q = $this->modx->newQuery('mseIntro');
		$q->select('`resource`');
		$q->where(array('intro:LIKE' => '%'.$string.'%'));
		if ($q->prepare() && $q->stmt->execute()) {
			$result = $q->stmt->fetchAll(PDO::FETCH_COLUMN);
		}

		return $result;
	}


	/**
	 * Highlight search string in given text string
	 *
	 * @param $text
	 * @param $query
	 * @param string $htag_open
	 * @param string $htag_close
	 *
	 * @return mixed
	 */
	function Highlight($text, $query, $htag_open = '<b>', $htag_close = '</b>') {
		$all_forms = array_merge(
			array($query => preg_split('#\s|[,.:;!?"\'\\\/()]#', $query, -1, PREG_SPLIT_NO_EMPTY))
			,$this->getAllForms($query)
		);

		$text_cut = ''; $words = array();
		foreach ($all_forms as $forms) {
			foreach ($forms as $form) {
				if (mb_strlen($form,'UTF-8') < $this->config['min_word_length']) {continue;}
				$words[] = $form;
				// Cutting text on first occurrence
				if (empty($text_cut) && preg_match('/\b'.$form.'\b/imu', $text, $matches)) {
					$pos = mb_strpos($text, $matches[0], 0, 'UTF-8');
					if ($pos >= $this->config['introCutBefore']) {
						$text_cut = '... ';
						$pos -= $this->config['introCutBefore'];
					}
					else {
						$text_cut = '';
						$pos = 0;
					}
					$text_cut .= mb_substr($text, $pos, $this->config['introCutAfter'], 'UTF-8');
					if (mb_strlen($text,'UTF-8') > $this->config['introCutAfter']) {$text_cut .= ' ...';}
				}
			}
		}

		preg_match_all('/\b('.implode('|',$words).')\b/imu', $text_cut, $matches);

		$from = $to = array();
		foreach ($matches[0] as $v) {
			$from[$v] = $v;
			$to[$v] = $htag_open.$v.$htag_close;
		}
		if (!empty($matches[1])) {
			foreach ($matches[1] as $v) {
				$from[$v] = $v;
				$to[$v] = $htag_open.$v.$htag_close;
			}
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


	/**
	 * @param array|string $ids
	 *
	 * @return array
	 */
	public function getFilters($ids) {
		/*
		if ($filters = $this->modx->cacheManager->get('msearch2/fltr_' . md5($ids))) {
			return $filters;
		}
		*/

		if (!is_object($this->filtersHandler)) {
			require_once 'filters.class.php';
			$filters_class = $this->modx->getOption('mse2_filters_handler_class', null, 'mse2FiltersHandler', true);
			if ($filters_class != 'mse2FiltersHandler') {$this->loadCustomClasses('filters');}
			if (!class_exists($filters_class)) {$filters_class = 'mse2FiltersHandler';}

			$this->filtersHandler = new $filters_class($this, $this->config);
			if (!($this->filtersHandler instanceof mse2FiltersHandler)) {
				$this->modx->log(modX::LOG_LEVEL_ERROR, '[mSearch2] Could not initialize filters handler class: "'.$filters_class.'"');
				return false;
			}
		}

		if (!is_array($ids)) {
			$ids = array_map('trim', explode(',', $ids));
		}
		if (empty($ids)) {return 'mSearch2 error: No ids given!';}

		$tmp_filters = array_map('trim', explode(',', $this->config['filters']));
		$filters = $order = array();

		// Preparing filters
		foreach ($tmp_filters as $v) {
			$v = strtolower($v);
			if (strpos($v, $this->config['filter_delimeter']) !== false) {
				@list($table, $filter) = explode($this->config['filter_delimeter'], $v);
			}
			else {
				$table = 'resource';
				$filter = $v;
			}

			$tmp = explode($this->config['method_delimeter'], $filter);
			$filters[$table][$tmp[0]] = array();
			$order[$table.$this->config['filter_delimeter'].$tmp[0]] = !empty($tmp[1]) ? $tmp[1] : 'default';
		}

		// Retrieving filters
		foreach ($filters as $table => &$fields) {
			$method = 'get'.ucfirst($table).'Values';
			if (method_exists($this->filtersHandler, $method)) {
				$fields = call_user_func_array(array($this->filtersHandler, $method), array(array_keys($fields), $ids));
			}
			else {
				$this->modx->log(modX::LOG_LEVEL_ERROR, '[mSearch2] Method "'.$method.'" not exists in class "'.get_class($this->filtersHandler).'". Could not retrieve filters from "'.$table.'"');
			}
		}

		// Building filters
		foreach ($order as $filter => &$value) {
			list($table, $filter) = explode($this->config['filter_delimeter'], $filter);
			$values = $filters[$table][$filter];

			$method = 'build'.ucfirst($value).'Filter';
			if (method_exists($this->filtersHandler, $method)) {
				$value = call_user_func_array(array($this->filtersHandler, $method), array($values));
			}
			else {
				$this->modx->log(modX::LOG_LEVEL_ERROR, '[mSearch2] Method "'.$method.'" not exists in class "'.get_class($this->filtersHandler).'". Could not build filter "'.$table.$this->config['filter_delimeter'].$filter.'"');
				$value = $values;
			}
		}

		//$this->modx->cacheManager->set('msearch2/fltr_' . md5($ids), $filters, 1800);

		return $order;
	}
}