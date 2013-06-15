<?php
/**
 * Create search index of all resources
 * 
 * @package msearch2
 * @subpackage processors
 */
class mseIndexCreateProcessor extends modProcessor {
	/** @var string $objectType The object "type", this will be used in various lexicon error strings */
	public $objectType = 'mseWord';
	/** @var string $classKey The class key of the Object to iterate */
	public $classKey = 'mseWord';
	/** @var array $languageTopics An array of language topics to load */
	public $languageTopics = array('msearch2:default');
	/** @var string $permission The Permission to use when checking against */
	public $permission = '';
	/** @var mSearch2 $mSearch2 */
	public $mSearch2;
	protected $fields = array();


	/**
	 * {@inheritDoc}
	 */
	public function checkPermissions() {
		return !empty($this->permission) ? $this->modx->hasPermission($this->permission) : true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getLanguageTopics() {
		return $this->languageTopics;
	}


	/**
	 * {@inheritDoc}
	 */
	public function process() {
		$fields = $this->modx->getOption('mse2_index_fields', null, 'content:3,description:2,introtext:2,pagetitle:3,longtitle:3', true);

		// Preparing fields for indexing
		$tmp = explode(',', preg_replace('/\s+/', '', $fields));
		foreach ($tmp as $v) {
			$tmp2 = explode(':', $v);
			$this->fields[$tmp2[0]] = !empty($tmp2[1]) ? $tmp2[1] : 1;
		}

		$collection = $this->getResources();
		if (!is_array($collection) && empty($collection)) {
			return $this->failure('mse2_err_no_resources_for_index');
		}

		$this->loadClass();
		if ($process_comments = $this->modx->getOption('mse2_index_comments', null, true, true) && class_exists('Ticket')) {
			$this->fields['resource_comments'] = $this->modx->getOption('mse2_index_comments_weight', null, 1, true);
		}
		else {$process_comments = false;}

		$i = 0;
		/* @var modResource|Ticket|msProduct $resource */
		foreach ($collection as $data) {
			if ($data['deleted']) {
				$this->unIndex($data['id']);
				continue;
			}

			$class_key = $data['class_key'];
			$resource = $this->modx->newObject($class_key);
			$resource->fromArray($data, '', true, true);

			$comments = '';
			if ($process_comments) {
				$q = $this->modx->newQuery('TicketComment', array('deleted' => 0, 'published' => 1));
				$q->innerJoin('TicketThread', 'Thread', '`TicketComment`.`thread`=`Thread`.`id` AND `Thread`.`deleted`=0');
				$q->innerJoin('modResource', 'Resource', '`Thread`.`resource`=`Resource`.`id` AND `Resource`.`id`='.$resource->get('id'));
				$q->select('text');
				if ($q->prepare() && $q->stmt->execute()) {
					while ($row = $q->stmt->fetch(PDO::FETCH_COLUMN)) {
						$comments .= $row.' ';
					}
				}
			}
			$resource->set('resource_comments', $comments);

			$this->Index($resource);
			$i++;
		}

		return $this->success('', array('indexed' => $i));
	}


	/**
	 * Prepares query and returns resource for indexing
	 *
	 * @return array|null
	 */
	public function getResources() {
		$limit = $this->getProperty('limit', 100);
		$offset = $this->getProperty('offset', 0);

		$select_fields = array_intersect(
			array_keys($this->modx->getFieldMeta('modResource'))
			,array_keys($this->fields)
		);
		$select_fields = array_unique(array_merge($select_fields, array('id','class_key','deleted')));

		$c = $this->modx->newQuery('modResource');
		$c->limit($limit, $offset);
		$c->sortby('id','ASC');
		$c->select($this->modx->getSelectColumns('modResource', 'modResource', '', $select_fields));
		$c = $this->prepareQuery($c);

		$collection = array();
		if ($c->prepare() && $c->stmt->execute()) {
			$collection = $c->stmt->fetchAll(PDO::FETCH_ASSOC);
		}
		else {
			$this->modx->log(modX::LOG_LEVEL_ERROR, '[mSearch2] Could not retrieve collection of resources: '.$c->stmt->errorInfo());
		}

		return $collection;
	}


	/**
	 * Prepares query before retrieving resources
	 *
	 * @param xPDOQuery $c
	 *
	 * @return xPDOQuery
	 */
	public function prepareQuery(xPDOQuery $c) {
		$c->where(array('searchable' => 1));

		return $c;
	}


	/**
	 * Create index of resource
	 *
	 * @param modResource $resource
	 */
	public function Index(modResource $resource) {
		$words = array(); $intro = '';

		foreach ($this->fields as $field => $weight) {
			$text = (strpos($field, 'tv_') !== false) ? $resource->getTVValue(substr($field, 3)) : $resource->get($field);

			$forms = $this->mSearch2->getBaseForms($text);
			$intro .= $this->modx->stripTags(is_array($text) ? $this->mSearch2->implode_r(' ', $text) : $text).' ';

			foreach ($forms as $form) {
				if (array_key_exists($form, $words)) {
					$words[$form] += $weight;
				}
				else {
					$words[$form] = $weight;
				}
			}
		}

		$tword = $this->modx->getTableName('mseWord');
		$tintro = $this->modx->getTableName('mseIntro');
		$resource_id = $resource->get('id');

		$intro = str_replace(array("\n","\r\n","\r"), ' ', $intro);
		$intro = preg_replace('/\s+/', ' ', str_replace(array('\'','"','«','»','`'), '', $intro));
		$sql = "INSERT INTO {$tintro} (`resource`, `intro`) VALUES ('$resource_id', '$intro') ON DUPLICATE KEY UPDATE `intro` = '$intro';";
		$sql .= "DELETE FROM {$tword} WHERE `resource` = '$resource_id';";
		$sql .= "INSERT INTO {$tword} (`resource`, `word`, `weight`) VALUES ";
		if (!empty($words)) {

			$rows = array();
			foreach ($words as $word => $weight) {
				$rows[] = "('$resource_id', '$word', '$weight')";
			}
			if (!empty($rows)) {
				$sql .= implode(',', $rows);
			}
		}
		$sql .= " ON DUPLICATE KEY UPDATE `resource` = '$resource_id';";
		/* @var PDOStatement $q */
		$q = $this->modx->prepare($sql);
		if (!$q->execute()) {
			$this->modx->log(modX::LOG_LEVEL_ERROR, '[mSearch2] Could not save search index of resource '.$resource_id.': '.print_r($q->errorInfo(),1));
		}
	}


	/**
	 * Remove index of resource
	 *
	 * @param integer $resource_id
	 */
	public function unIndex($resource_id) {
		$sql = "DELETE FROM {$this->modx->getTableName('mseWord')} WHERE `resource` = '$resource_id';";
		$sql .= "DELETE FROM {$this->modx->getTableName('mseIntro')} WHERE `resource` = '$resource_id';";

		$this->modx->exec($sql);
	}


	/**
	 * Loads mSearch2 class to processor
	 *
	 * @return bool
	 */
	public function loadClass() {
		if (!empty($this->modx->mSearch2) && $this->modx->mSearch2 instanceof mSearch2) {
			$this->mSearch2 = & $this->modx->mSearch2;
		}
		else {
			if (!class_exists('mSearch2')) {require_once MODX_CORE_PATH . 'components/msearch2/model/msearch2/msearch2.class.php';}
			$this->mSearch2 = new mSearch2($this->modx, array());
		}

		return $this->mSearch2 instanceof mSearch2;
	}

}

return 'mseIndexCreateProcessor';