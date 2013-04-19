<?php
/**
 * Update search index of one resource
 *
 * @package msearch2
 * @subpackage processors
 */

require_once 'create.class.php';

class mseIndexUpdateProcessor extends mseIndexCreateProcessor {

	public function process() {
		if (!$this->getProperty('id')) {
			return $this->failure('mse2_err_resource_ns');
		}

		return parent::process();
	}


	/**
	 * Prepares query before retrieving resources
	 *
	 * @param xPDOQuery $c
	 *
	 * @return xPDOQuery
	 */
	public function prepareQuery(xPDOQuery $c) {
		$c->where(array('searchable' => 1, 'id' => $this->getProperty('id')));

		return $c;
	}

}

return 'mseIndexUpdateProcessor';