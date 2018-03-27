<?php
/*
__to_be_replaced_with_disclaimer__
*/
include_once(dirname(__FILE__).'/../../autoprocess.php');
Class autoprocessCronjobModuleFrontController extends ModuleFrontController
{
	public $content_only = true;
	public $display_header = false;
	public $display_footer = false;

	public function init() {
	    parent::init();
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
	}

	public function initContent()
	{
		parent::initContent();

		$module = Module::getInstanceByName('autoprocess');
        if ($module->active)
        {
			$secure_key = Tools::getValue('secure_key');
			if ($secure_key && $secure_key == Configuration::get('PS_AUTOPROCESS_SECURE_KEY')){
				if ($trigger_execute_function = Tools::getValue('trigger_execute_function')){
					if (array_key_exists($trigger_execute_function, $module->getAvailableDirectCallToActionMethods())){
						$module->addToLog('AutoProcessController::initContent - '.$_SERVER['REQUEST_URI'].' OK', 1, null, null, null, true);
						echo $module->$trigger_execute_function();
					} else {
						$module->addToLog('AutoProcessController::initContent - Someone trying to trigger a function - here: '.$_SERVER['REQUEST_URI'], 4, null, null, null, false);
						echo "No access!";
					}
				} else {
					$module->addToLog('AutoProcessController::initContent - '.$_SERVER['REQUEST_URI'].' OK', 1, null, null, null, true);
					echo $module->executeCronJob();
				}
			} else {
				$module->addToLog('AutoProcessController::initContent - Someone is using wrong secure_key', 4, null, null, null, false);
				echo "No access!";
			}
        }
	}
}