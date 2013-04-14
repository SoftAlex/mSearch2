<?php
/**
 * The main manager controller for mSearch2.
 *
 * @package msearch2
 */

require_once dirname(__FILE__) . '/model/msearch2/msearch2.class.php';

abstract class mSearch2MainController extends modExtraManagerController {
	/** @var mSearch2 $mSearch2 */
	public $mSearch2;

	public function initialize() {
		$this->mSearch2 = new mSearch2($this->modx);
		
		$this->modx->regClientCSS($this->mSearch2->config['cssUrl'].'mgr/main.css');
		$this->modx->regClientStartupScript($this->mSearch2->config['jsUrl'].'mgr/msearch2.js');
		$this->modx->regClientStartupHTMLBlock('<script type="text/javascript">
		Ext.onReady(function() {
			mSearch2.config = '.$this->modx->toJSON($this->mSearch2->config).';
			mSearch2.config.connector_url = "'.$this->mSearch2->config['connectorUrl'].'";
		});
		</script>');
		
		parent::initialize();
	}

	public function getLanguageTopics() {
		return array('msearch2:default');
	}

	public function checkPermissions() { return true;}
}


class IndexManagerController extends mSearch2MainController {
	public static function getDefaultController() { return 'home'; }
}