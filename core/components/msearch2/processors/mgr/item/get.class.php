<?php
/**
 * Get an Item
 * 
 * @package msearch2
 * @subpackage processors
 */
class mSearch2ItemGetProcessor extends modObjectGetProcessor {
	public $objectType = 'mSearch2Item';
	public $classKey = 'mSearch2Item';
	public $languageTopics = array('msearch2:default');
}

return 'mSearch2ItemGetProcessor';