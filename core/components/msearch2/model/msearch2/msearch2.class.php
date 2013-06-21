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
	public $filtersHandler = array();
	public $phpMorphy = array();
	public $initialized = array();


	function __construct(modX &$modx,array $config = array()) {
		$this->modx =& $modx;

		$corePath = $this->modx->getOption('msearch2.core_path', $config, $this->modx->getOption('core_path').'components/msearch2/');
		$assetsUrl = $this->modx->getOption('msearch2.assets_url', $config, $this->modx->getOption('assets_url').'components/msearch2/');
		$actionUrl = $this->modx->getOption('minishop2.action_url', $config, $assetsUrl.'action.php');
		$connectorUrl = $assetsUrl.'connector.php';

		$this->config = array_merge(array(
			'assetsUrl' => $assetsUrl
			,'cssUrl' => $assetsUrl.'css/'
			,'jsUrl' => $assetsUrl.'js/'
			,'imagesUrl' => $assetsUrl.'images/'
			,'customPath' => $corePath.'custom/'

			,'connectorUrl' => $connectorUrl
			,'actionUrl' => $actionUrl

			,'corePath' => $corePath
			,'modelPath' => $corePath.'model/'
			,'templatesPath' => $corePath.'elements/templates/'
			,'processorsPath' => $corePath.'processors/'

			,'cacheTime' => 1800
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
			,'filter_delimeter' => '|'
			,'method_delimeter' => ':'
			,'split_words' => $this->modx->getOption('mse2_search_split_words', null, '#\s#', true)
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
	public function initialize($ctx = 'web', $scriptProperties = array()) {
		switch ($ctx) {
			case 'mgr':
				if (!$this->modx->loadClass('msearch2.request.mSearch2ControllerRequest', $this->config['modelPath'], true, true)) {
					return 'Could not load controller request handler.';
				}
				$this->request = new mSearch2ControllerRequest($this);
				return $this->request->handleRequest();
			break;
			default:
				$this->config = array_merge($this->config, $scriptProperties);
				$this->config['ctx'] = $ctx;
				if (!empty($this->initialized[$ctx])) {
					return true;
				}

				if (!defined('MODX_API_MODE') || !MODX_API_MODE) {
					$config = $this->makePlaceholders($this->config);
					if ($css = $this->modx->getOption('mse2_frontend_css')) {
						$this->modx->regClientCSS(str_replace($config['pl'], $config['vl'], $css));
					}
					if ($js = trim($this->modx->getOption('mse2_frontend_js'))) {
						$this->modx->regClientStartupScript(str_replace('					', '', '
						<script type="text/javascript">
						mSearch2Config = {
							cssUrl: "'.$this->config['cssUrl'].'web/"
							,jsUrl: "'.$this->config['jsUrl'].'web/"
							,actionUrl: "'.$this->config['actionUrl'].'"
							,pageId: '.$this->modx->resource->id.'
						};
						</script>
					'), true);
						if (!empty($js) && preg_match('/\.js$/i', $js)) {
							$this->modx->regClientScript(str_replace('							', '', '
							<script type="text/javascript">
							if(typeof jQuery == "undefined") {
								document.write("<script src=\"'.$this->config['jsUrl'].'web/lib/jquery.min.js\" type=\"text/javascript\"><\/script>");
							}
							</script>
							'), true);
							$this->modx->regClientScript(str_replace($config['pl'], $config['vl'], $js));
						}
					}
				}

				$this->initialized[$ctx] = true;
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
		$words = preg_split($this->config['split_words'], $text, -1, PREG_SPLIT_NO_EMPTY);
		$bulk_words = array();
		foreach ($words as $v) {
			if (mb_strlen($v,'UTF-8') >= $this->config['min_word_length']) {
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
							if (mb_strlen($form,'UTF-8') >= $this->config['min_word_length']) {
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
		$string = preg_replace('/[^_-а-яёa-z0-9\s\.\/]+/iu', ' ', $this->modx->stripTags($query));
		$words = $this->getBaseForms($string, 0);
		$bulk_words = array_unique(array_values($words));

		$result = $all_words = $found_words = array();
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
					@$found_words[$words[$row['word']]] = 1;
				}

			}
		}

		if (count($bulk_words) > 1) {
			$exact = $this->simpleSearch($query);
			// Exact match bonus
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

		//$not_found = array_diff($bulk_words, array_keys($found_words));
		//foreach ($not_found as $word) {
		foreach ($bulk_words as $word) {
			$found = $this->simpleSearch($word);

			foreach ($found as $v) {
				if (!isset($result[$v])) {
					$result[$v] = floor($this->config['exact_match_bonus'] / 2);
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
		$string = preg_replace('/[^_-а-яёa-z0-9\s\.\/]+/iu', ' ', $this->modx->stripTags($query));

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
	function Highlight($text, $query, $htag_open = '<b>', $htag_close = '</b>', $strict = true) {
		$tmp = array_merge(
			array($query => preg_split($this->config['split_words'], $query, -1, PREG_SPLIT_NO_EMPTY))
			,$this->getAllForms($query)
		);

		$words = array_keys($tmp);
		foreach ($tmp as $v) {
			$words = array_merge($words, array_values($v));
		}

		$text_cut = '';
		foreach ($words as $key => $word) {
			if (mb_strlen($word,'UTF-8') < $this->config['min_word_length']) {
				unset($words[$key]);
				continue;
			}
			$word = preg_quote($word, '/');
			$words[$key] = $word;

			// Cutting text on first occurrence
			$pcre = $strict ? '/\b'.$word.'\b/imu' : '/'.$word.'/imu';
			if (empty($text_cut) && preg_match($pcre, $text, $matches)) {
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

		if (empty($text_cut) && $strict) {
			return $this->Highlight($text, $query, $htag_open, $htag_close, false);
		}

		$pcre = $strict ? '/\b('.implode('|',$words).')\b/imu' : '/('.implode('|',$words).')/imu';
		preg_match_all($pcre, $text_cut, $matches);

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
		else if ($strict) {
			return $this->Highlight($text, $query, $htag_open, $htag_close, false);
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
	public function getFilters($ids, $build = true) {
		// prepare ids
		if (!is_array($ids)) {
			$ids = array_map('trim', explode(',', $ids));
		}
		if (empty($ids)) {return false;}

		// Return results from cache
		if ($build && $prepared = $this->modx->cacheManager->get('msearch2/prep_' . md5(implode(',',$ids) . $this->config['filters']))) {
			return $prepared;
		}
		else if ($filters = $this->modx->cacheManager->get('msearch2/fltr_' . md5(implode(',',$ids) . $this->config['filters']))) {
			return $filters;
		}

		$this->loadHandler();

		// Preparing filters
		$filters = $built = array();
		$tmp_filters = array_map('trim', explode(',', $this->config['filters']));
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
			$built[$table.$this->config['filter_delimeter'].$tmp[0]] = !empty($tmp[1]) ? $tmp[1] : 'default';
		}

		// Retrieving filters
		foreach ($filters as $table => &$fields) {
			$method = 'get'.ucfirst($table).'Values';
			$keys = array_keys($fields);
			if (method_exists($this->filtersHandler, $method)) {
				$fields = call_user_func_array(array($this->filtersHandler, $method), array(array_keys($fields), $ids));

				foreach ($keys as $key) {
					if (!isset($fields[$key])) {
						$fields[$key] = array();
					}
				}

			}
			else {
				$this->modx->log(modX::LOG_LEVEL_ERROR, '[mSearch2] Method "'.$method.'" not exists in class "'.get_class($this->filtersHandler).'". Could not retrieve filters from "'.$table.'"');
			}
		}

		$this->modx->cacheManager->set('msearch2/fltr_' . md5(implode(',',$ids)), $filters, $this->config['cacheTime']);
		// Building filters
		if ($build) {
			foreach ($built as $filter => &$value) {
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
			$this->modx->cacheManager->set('msearch2/prep_' . md5(implode(',',$ids) . $this->config['filters']), $built, $this->config['cacheTime']);
			return $built;
		}
		else {
			return $filters;
		}
	}


	/**
	 * Fiters resources by given params
	 *
	 * @param $request
	 *
	 * @return array
	 */
	public function Filter($ids, array $request) {
		if (!is_array($ids)) {
			$ids = explode(',', $ids);
		}
		$filters = $this->getFilters($ids, false);

		$methods = array();
		$tmp_filters = array_map('trim', explode(',', $this->config['filters']));
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
			$methods[$table.$this->config['filter_delimeter'].$tmp[0]] = !empty($tmp[1]) ? $tmp[1] : 'default';
		}

		foreach ($request as $filter => $requested) {
			if (!preg_match('/(.*?)'.preg_quote($this->config['filter_delimeter'],'/').'(.*?)/', $filter)) {continue;}
			$method = !empty($methods[$filter]) ? 'filter' . ucfirst($methods[$filter]) : 'filterDefault';

			list($table, $filter) = explode($this->config['filter_delimeter'], $filter);
			$values = $filters[$table][$filter];
			$requested = explode(',', $requested);

			if (method_exists($this->filtersHandler, $method)) {
				$ids = call_user_func_array(array($this->filtersHandler, $method), array($requested, $values, $ids));
			}
			else {
				//$this->modx->log(modX::LOG_LEVEL_ERROR, '[mSearch2] Method "'.$method.'" not exists in class "'.get_class($this->filtersHandler).'". Could not build filter "'.$table.$this->config['filter_delimeter'].$filter.'"');
				$ids = call_user_func_array(array($this->filtersHandler, 'filterDefault'), array($requested, $values, $ids));
			}
		}

		return $ids;
	}


	public function getSuggestions($ids, array $request, array $current = array()) {
		if (!is_array($ids)) {
			$ids = explode(',', $ids);
		}
		$filters = $this->getFilters($ids, false);

		$suggestions = array();
		foreach ($filters as $table => $fields) {
			foreach ($fields as $field => $values) {
				foreach ($values as $value => $resources) {
					$suggest = $request;
					$key = $table.$this->config['filter_delimeter'].$field;

					$added = 0;
					if (isset($request[$key])) {
						$tmp2 = explode(',', $request[$key]);
						if (!in_array($value, $tmp2)) {
							$suggest[$key] .= ',' . $value;
							$added = 1;
						}
						$res = $this->Filter($ids, $suggest);
						if ($added && !empty($res)) {
							$count = count(array_diff($res, $current));
							if (!empty($count)) {
								$count += count($current);
							}
						}
						else {
							$count = count($res);
						}
					}
					else {
						$suggest[$key] = $value;
						$res = $this->Filter($ids, $suggest);
						$count = count($res);
					}

					@$suggestions[$key][$value] = $count;
				}
			}
		}

		return $suggestions;
	}



	/**
	 * Method for transform array to placeholders
	 *
	 * @var array $array With keys and values
	 *
	 * @return array $array Two nested arrays With placeholders and values
	 */
	public function makePlaceholders(array $array = array(), $prefix = '') {
		$result = array(
			'pl' => array()
			,'vl' => array()
		);
		foreach ($array as $k => $v) {
			if (is_array($v)) {
				$result = array_merge_recursive($result, $this->makePlaceholders($v, $k.'.'));
			}
			else {
				$result['pl'][$prefix.$k] = '[[+'.$prefix.$k.']]';
				$result['vl'][$prefix.$k] = $v;
			}
		}
		return $result;
	}


	/**
	 * Returns string for insert into sorting properties of pdoTools snippet
	 *
	 * @param string
	 *
	 * @return string
	 */
	public function getSortFields($sort) {
		$this->loadHandler();
		return $this->filtersHandler->getSortFields($sort);
	}


	/**
	 * Loads custom filters handler class
	 *
	 * @return bool
	 */
	public function loadHandler() {
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

		return true;
	}
}