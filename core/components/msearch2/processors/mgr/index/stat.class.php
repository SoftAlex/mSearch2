<?php
/**
 * Returns stat of mSearch2 index
 *
 * @package msearch2
 * @subpackage processors
 */

class mseIndexStatProcessor extends modProcessor {

	public function process() {

		$array = array(
			'total' => $this->getTotal()
			,'indexed' => $this->getIndexed()
			,'words' => $this->getWords()
		);

		return $this->success('', $array);
	}


	public function getTotal() {
		$q = $this->modx->newQuery('modResource');
		$q->select('COUNT(`id`)');

		return ($q->prepare() && $q->stmt->execute()) ? $q->stmt->fetch(PDO::FETCH_COLUMN) : 0;
	}


	public function getIndexed() {
		$q = $this->modx->newQuery('mseIntro');
		$q->select('COUNT(`resource`)');

		return ($q->prepare() && $q->stmt->execute()) ? $q->stmt->fetch(PDO::FETCH_COLUMN) : 0;
	}


	public function getWords() {
		$q = $this->modx->newQuery('mseWord');
		$q->select('COUNT(DISTINCT `word`)');

		return ($q->prepare() && $q->stmt->execute()) ? $q->stmt->fetch(PDO::FETCH_COLUMN) : 0;
	}

}

return 'mseIndexStatProcessor';