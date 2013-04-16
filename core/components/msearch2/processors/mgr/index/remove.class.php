<?php
/**
 * Remove search index
 *
 * @package msearch2
 * @subpackage processors
 */

class mseIndexUpdateProcessor extends modProcessor {
	/** @var string $objectType The object "type", this will be used in various lexicon error strings */
	public $objectType = 'mseWord';
	/** @var string $classKey The class key of the Object to iterate */
	public $classKey = 'mseWord';
	/** @var array $languageTopics An array of language topics to load */
	public $languageTopics = array('msearch2:default');
	/** @var string $permission The Permission to use when checking against */
	public $permission = 'new_document';
	/** @var mSearch2 $mSearch2 */
	public $mSearch2;

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

	public function process() {
		$this->loadClass();

		$sql = "TRUNCATE TABLE {$this->modx->getTableName('mseWord')};";
		$sql .= "TRUNCATE TABLE {$this->modx->getTableName('mseIntro')};";

		$this->modx->exec($sql);

		return $this->success();
	}


	/**
	 * Loads mSearch2 class to processor
	 *
	 * @return bool
	 */
	public function loadClass() {
		if (!empty($this->modx->mSearch2) && !($this->modx->mSearch2 instanceof mSearch2)) {
			$this->mSearch2 = & $this->modx->mSearch2;
		}
		else {
			require_once MODX_CORE_PATH . 'components/msearch2/model/msearch2/msearch2.class.php';
			$this->mSearch2 = new mSearch2($this->modx, array());
		}

		return $this->mSearch2 instanceof mSearch2;
	}

}

return 'mseIndexUpdateProcessor';