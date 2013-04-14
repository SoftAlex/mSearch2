<?php
/**
 * Remove an Item.
 * 
 * @package msearch2
 * @subpackage processors
 */
class mSearch2ItemRemoveProcessor extends modObjectRemoveProcessor  {
	public $checkRemovePermission = true;
	public $objectType = 'mSearch2Item';
	public $classKey = 'mSearch2Item';
	public $languageTopics = array('msearch2');

}
return 'mSearch2ItemRemoveProcessor';