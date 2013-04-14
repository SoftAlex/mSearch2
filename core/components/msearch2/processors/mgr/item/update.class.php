<?php
/**
 * Update an Item
 * 
 * @package msearch2
 * @subpackage processors
 */
class mSearch2ItemUpdateProcessor extends modObjectUpdateProcessor {
	public $objectType = 'mSearch2Item';
	public $classKey = 'mSearch2Item';
	public $languageTopics = array('msearch2');
	public $permission = 'update_document';
}

return 'mSearch2ItemUpdateProcessor';