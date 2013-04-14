<?php
/**
 * Create an Item
 * 
 * @package msearch2
 * @subpackage processors
 */
class mSearch2ItemCreateProcessor extends modObjectCreateProcessor {
	public $objectType = 'mSearch2Item';
	public $classKey = 'mSearch2Item';
	public $languageTopics = array('msearch2');
	public $permission = 'new_document';
	
	public function beforeSet() {
		$alreadyExists = $this->modx->getObject('mSearch2Item',array(
			'name' => $this->getProperty('name'),
		));
		if ($alreadyExists) {
			$this->modx->error->addField('name',$this->modx->lexicon('msearch2_item_err_ae'));
		}
		return !$this->hasErrors();
	}
	
}

return 'mSearch2ItemCreateProcessor';