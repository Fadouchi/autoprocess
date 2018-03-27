<?php
/*
__to_be_replaced_with_disclaimer__
*/

class AdminController extends AdminControllerCore
{
	// lets append display() method so we can save messages to be shown on next page
	public function display()
	{
		foreach (array('errors', 'warnings', 'informations', 'confirmations') as $type)
		{
			$cookiename = 'display_'.$type;
			if (isset($this->context->cookie->$cookiename)){
				$this->$type = (array)$this->context->cookie->$cookiename;
				//$this->context->smarty->assign(array($cookiename => $this->context->cookie->$cookiename,));
				$this->context->cookie->__unset($cookiename);
			}
		}
		parent::display();
	}
}
