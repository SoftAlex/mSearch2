<?php
/**
 * Get a list of Items
 *
 * @package msearch2
 * @subpackage processors
 */
class mSearch2ItemGetListProcessor extends modObjectGetListProcessor {
	public $objectType = 'mSearch2Item';
	public $classKey = 'mSearch2Item';
	public $defaultSortField = 'id';
	public $defaultSortDirection  = 'DESC';
	public $renderers = '';
	
	public function prepareQueryBeforeCount(xPDOQuery $c) {
		return $c;
	}

	public function prepareRow(xPDOObject $object) {
		$array = $object->toArray();
		return $array;
	}
	
}

return 'mSearch2ItemGetListProcessor';