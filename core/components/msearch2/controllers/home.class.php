<?php
/**
 * The home manager controller for mSearch2.
 *
 * @package msearch2
 */
class mSearch2HomeManagerController extends mSearch2MainController {
	/* @var mSearch2 $mSearch2 */
	public $mSearch2;


	public function process(array $scriptProperties = array()) {}
	

	public function getPageTitle() { return $this->modx->lexicon('msearch2'); }
	

	public function loadCustomCssJs() {
		$this->modx->regClientStartupScript($this->mSearch2->config['jsUrl'].'mgr/widgets/index.form.js');
		$this->modx->regClientStartupScript($this->mSearch2->config['jsUrl'].'mgr/widgets/search.grid.js');
		$this->modx->regClientStartupScript($this->mSearch2->config['jsUrl'].'mgr/widgets/home.panel.js');
	 	$this->modx->regClientStartupScript($this->mSearch2->config['jsUrl'].'mgr/sections/home.js');
	}
	

	public function getTemplateFile() {
		return $this->mSearch2->config['templatesPath'].'home.tpl';
	}
}