<?php
/*
TODO3
__to_be_replaced_with_disclaimer__
*/

if (!defined('_PS_VERSION_'))
	exit;

class AutoProcess extends Module
{
	public function __construct()
	{
		$this->name = 'autoprocess';
		$this->tab = 'administration';
		$this->version = '1.0';
		$this->author = 'MichaelHjulskov';
		$this->email ="michael@hjulskov.dk";
		$this->need_instance = 0;
		$this->bootstrap = true;
		$this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
		$this->replacement_patterns = array(
			'/ - ([^:]+): /iu' 	=> ' ', // " - Vægt : "
			'/ : ([^:]+) - /iu' => ' ', // " : Vægt - "
			'/ Grain Free/' 	=> ' GF',
			'/ Medium Breed/' 	=> ' MB',
			'/ Medium/' 		=> ' M',
			'/ Small Breed/' 	=> ' SB',
			'/ Small/' 			=> ' S',
			'/ Large Breed/' 	=> ' LB',
			'/ Large/' 			=> ' L',
			'/ Små Bidder/' 	=> ' S',
			'/ Alm Bidder/' 	=> ' M',
			'/ Foderprøve/'		=> ' Prøve',
			'!\s+!' 			=> ' '
		);

		parent::__construct();

		if (Configuration::get('PS_AUTOPROCESS_SECURE_KEY') === false)
			Configuration::updateValue('PS_AUTOPROCESS_SECURE_KEY', Tools::strtoupper(Tools::passwdGen(16)));

		$this->standard_payment_modules = 'bankwire,cheque,cashondelivery,paypal,quickpay,epay,coinify';
		if (Configuration::get('PS_AUTOPROCESS_PAYMENT_MODULES') === false)
			Configuration::updateValue('PS_AUTOPROCESS_PAYMENT_MODULES', pSQL($this->standard_payment_modules));

		if (Configuration::get('PS_AUTOPROCESS_ADD_TO_LOG') === false)
			Configuration::updateValue('PS_AUTOPROCESS_ADD_TO_LOG', pSQL('on'));

		$this->displayName = $this->l('AutoProcess');
		$this->description = $this->l('Automation of daily routines');
	}

	public function install()
	{
		if (!parent::install())
			return false;

		// TODO2 - is it intelligent to delete tables? wouldnt it be better to CREATE TABLE IF NOT EXISTS
		// question: on refresh/reset will install be triggered??
		Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'autoprocess_filter`');
		Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'autoprocess_process`');

		if (!Db::getInstance()->execute('
			CREATE TABLE `'._DB_PREFIX_.'autoprocess_filter` (
				`id_filter` INT NOT NULL AUTO_INCREMENT,
				`name` varchar(150) default NULL,
				`current_states` text default NULL,
				`previous_states` text default NULL,
				`never_had_states` text default NULL,
				`from_date` date,
				`to_date` date,
				`min_num_orders` INT default NULL,
				`max_num_orders` INT default NULL,
				`delivery_countries` text default NULL,
				`payment_modules` text default NULL,
				`suppliers` text default NULL,
				`supplier_order_line` varchar (150) default NULL,
				`manufacturers` text default NULL,
				`manufacturer_order_line` varchar (150) default NULL,
				`order_age_more_or_less_than` varchar (150)  default NULL,
				`order_age_number` INT default NULL,
				`order_age_type` varchar(20) default NULL,
				`warehouses` text (150) default NULL,
				`warehouse_order_line` varchar (150) default NUll,
				`order_comment_option` varchar (150) default Null,
				`words_in_comment` text default Null,
			PRIMARY KEY (`id_filter`),
			INDEX `id_filter` (`id_filter`)
			)  ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;'))
			return false;

		if (!Db::getInstance()->execute('
			CREATE TABLE `'._DB_PREFIX_.'autoprocess_process` (
				`id_process` INT NOT NULL AUTO_INCREMENT,
				`name` varchar(150) default NULL,
				`unique_time_criteria_method` varchar(150) default NULL,
				`process_active` BOOL default NULL,
				`sort_by` INT default NULL,
				`global_link_active` BOOL default NULL,
				`filters` text (150) default NULL,
				`process_from` text (150) default NUll,
				`process_to` text (150) default NUll,
				`and_or_between_filters` varchar (150) default NUll,
				`id_employee` INT default NULL,
				`action_call_method` varchar(150) default NUll,
				`action_set_order_state_succeded` INT default NULL,
				`action_set_order_state_failed` INT default NULL,
				`action_alert_on_true` BOOL default NULL,
				`action_alert_on_false` BOOL default NULL,
			PRIMARY KEY (`id_process`),
			INDEX `process_active` (`process_active`)
			)  ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;'))
			return false;


		//Store the added files
		$files_added = array();
		// Copy controller file and tpl file.
		$files = $this->get_files_to_install();
		foreach ($files AS $file_name => $file_data){
			if (is_array($file_data) AND !empty($file_data['source']) AND !empty($file_data['target'])){
				if (!file_exists($file_data['target'] . $file_name) || $file_data['force_override_on_install']){
					if (!copy($file_data['source'] . $file_name, $file_data['target'] . $file_name)){
						$this->context->controller->errors[] = 'Could not copy the file ' . $file_data['source'] . $file_name . ' to the directory ' . $file_data['target'];
						$error=true;
						break; // Abort Installation.
					} else {
						$files_added[] = $file_data['target'] . $file_name;
					}
				} else {
					$this->context->controller->errors[] = 'File ' . $file_data['source'] . $file_name . ' already exist in directory ' . $file_data['target'] . ' - please backup/rename it temperarly for this module to install. afterward you should maybe combine contents from the two files manually, there might be some code in file from another module so take care of this.';
					$error=true;
					break; // Abort Installation.
				}
			}
		}
		if ($error){
			// Remove the copied files.
			foreach ($files_added AS $file_name){
				if (file_exists($file_name)){
					@unlink($file_name);
				}
			}
			return false;
		}

		@unlink(_PS_ROOT_DIR_ . '/cache/class_index.php');

		return true;
	}

	public function uninstall()
	{

		if (!parent::uninstall()
			|| !Configuration::deleteByName('PS_AUTOPROCESS_PAYMENT_MODULES')
			|| !Configuration::deleteByName('PS_AUTOPROCESS_SECURE_KEY')
			|| !Configuration::deleteByName('PS_AUTOPROCESS_ADD_TO_LOG')
			|| !Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'autoprocess_filter`')
			|| !Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'autoprocess_process`')
			)
			return false;

		// Remove controller and tpl files.
		$files = $this->get_files_to_install();
		foreach ($files AS $file_name => $file_data)
			if (is_array($file_data) AND !empty($file_data['source']) AND !empty($file_data['target']) AND $file_data['delete_on_uninstall'])
				if (file_exists($file_data['target'] . $file_name))
					@unlink($file_data['target'] . $file_name);

		@unlink(_PS_ROOT_DIR_ . '/cache/class_index.php');

		return true;
	}

	/**
	 * Returns a list of files to install
	 **/
	protected function get_files_to_install(){
		return array(
			'AutoProcessController.php' => array(
				'source' => _PS_MODULE_DIR_ . $this->name . '/upload/controllers/front/',
				'target' => _PS_ROOT_DIR_ . '/controllers/front/',
				'force_override_on_install' => true,
				'delete_on_uninstall' => true
			),
		);
	}
	protected function displayInformationsOnload($msg)
	{
		$this->context->cookie->__set('display_informations', $msg.((isset($this->context->cookie->display_informations)) ? '<br>'.$this->context->cookie->display_informations : ''));
	}
	protected function displayErrorOnload($msg)
	{
		$this->context->cookie->__set('display_errors', $msg.((isset($this->context->cookie->display_errors)) ? '<br>'.$this->context->cookie->display_errors : ''));
	}
	protected function displayWarningsOnload($msg)
	{
		$this->context->cookie->__set('display_warnings', $msg.((isset($this->context->cookie->display_warnings)) ? '<br>'.$this->context->cookie->display_warnings : ''));
	}
	protected function displayConfirmationsOnload($msg)
	{
		$this->context->cookie->__set('display_confirmations', $msg.((isset($this->context->cookie->display_confirmations)) ? '<br>'.$this->context->cookie->display_confirmations : ''));
	}

	/*
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
	*/

	public function generateCronURL($id_process=false, $simulate=false)
	{
		$params['secure_key'] = Configuration::get('PS_AUTOPROCESS_SECURE_KEY');
		if ($id_process)
			$params['id_process'] = $id_process;
		if ($simulate)
			$params['simulate'] = 1;
		return Context::getContext()->link->getModuleLink($this->name, 'cronjob', $params);
	}

	public function getAvailablePaymentMethodList()
	{
		$query = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT `id_module`, `name`
			FROM `'._DB_PREFIX_.'module`
			WHERE `name` IN("'.implode('", "', explode(',', Configuration::get('PS_AUTOPROCESS_PAYMENT_MODULES'))).'")'
		);
		$payment_list = array();
		foreach($query as $val){
			$payment_list[$val['id_module']] = $val['name'];
		}
		return $payment_list;
	}

	public function getAvailableCallToActionMethods()
	{
		return array(
			"do_nothing" => $this->l('Do nothing (always returns true)'),
			"withdraw_quickpay_payment" => $this->l('Withdraw Quickpay payment (if not allready done)'),
			//"withdraw_quickpay_payment_old_platform" => $this->l('Withdraw Quickpay payment (if not allready done)'),
			"send_order_to_warehouse_LM" => $this->l('Send order to LM Lagerhotel A/S for packaging and shipping'),
			"check_if_order_all_quantities_has_become_available" => $this->l('Return true if all products is available in stock now'),
			"check_if_any_tracking_numbers_exist_from_warehouse_LM" => $this->l('Return true if any available package numbers from LM Lagerhotel A/S'),
			//"get_and_insert_tracking_numbers_from_warehouse_LM" => $this->l('Get package numbers from LM Lagerhotel A/S and insert in DB tracking field'),
			"get_and_update_gls_tracking_numbers" => $this->l('Get all package numbers sent by GLS from LM Lagerhotel A/S and insert in DB tracking field (dont trigger this to often)'),
			"send_order_to_warehouse_RH" => $this->l('Send order to Leverandørnavn for dropship packaging and shipping'),
			"check_if_all_GLS_packages_from_LM_is_delivered" => $this->l('Return true if all GLS packages from LM Lagerhotel A/S is delivered (dont trigger this to often)'),
			//"withdraw_payment" => $this->l('Withdraw payment (if not allready done)'),
			//"send_order_to_warehouse_by_email" => $this->l('Send order to warehouse email, as dropshipping order'),
			// ADD YOUR OWN ACTIONS METHODS HERE
			//"send_order_to_mywarehouse" => $this->l('Send order to Mywarehouse for packaging and shipping'),
			//"send_order_matching_orderlines_to_warehouse_by_email" => $this->l('Send order details, by email, to the Warehouse that is related to product lines'),
			//"send_order_to_warehouse_by_email" => $this->l('Send order data to Mydropshipsupplier'),
			//"send_order_to_csv_file" => $this->l('create csv file or add to csv file'),
			//"trigger_mycronjob_url" => $this->l('trigger my cronjob url'),
		);
	}

	public function getAvailableUniqueTimeCriterias()
	{
		// when a cron link is triggered, this will have to be meet
		// if (is_work_day_in_denmark(date("c"))){we allow process to be trigered now}
		// in essence we can use this to keep process from being
		// triggered outside some working hours/days and on holy days etc.
		return array(
			"is_work_day_in_denmark" => $this->l('Is Work Days In Denmark'),
			// ADD YOUR OWN TIME CRITERIAS HERE
			// Here you can add your own unique time criterias such as these examples
			// "work_days_in_sweden" => $this->l('Work Days In Sweden'),
			// "work_days_in_norway" => $this->l('Work Days In Norway'),
			// "its_full_moon_now" => $this->l('Its Full Moon Now'),
			// "its_our_birthday" => $this->l('Its Our Birthday Today'),
			// "its_a_holy_day" => $this->l('Its a holy day'),
			// "its_not_a_holy_day" => $this->l('Its not a holy day'),
			// make sure to add a php Class method with the same name - expect date("c") as param
		);
	}

	public function getAvailableDirectCallToActionMethods()
	{
		return array(
			//"do_nothing" => $this->l('Do nothing (always returns true)'),
			"compare_stock_quanties_with_warehouse_LM" => $this->l('Compare stock quantities with warehouse LM'),
			"check_stock_quanties_soldout" => $this->l('Check stock quantities and send report on out of stock'),
		);
	}


	public function renderFormAddProcess()
	{
		$this->smartyAssignGeneral();
		$this->context->smarty->assign(array(
			'id_process' => false,
			'name' => $this->l('Process name'),
			'process_active' => 0,
			'sort_by' => 0,
			'global_link_active' => 1,
			'cron_url'=> false,
			'simulate_url'=> false,
			'process_from' => false,
			'process_to' => false,
			'and_or_between_filters' => 'AND',
			'filters' => false,
			'unique_time_criteria_method' => false,
			'id_employee' => false,
			'action_call_method' => false,
			'action_set_order_state_succeded' => false,
			'action_set_order_state_failed' => false,
			'action_alert_on_true' => 0,
			'action_alert_on_false' => 0,
		));
		return $this->display(__FILE__, 'views/templates/admin/process.tpl');
	}

	// delete process
	public function deleteProcess($id_process)
	{
		if ($id_process){
			Db::getInstance()->execute('
				DELETE FROM `'._DB_PREFIX_.'autoprocess_process`
				WHERE `id_process` = '.$id_process.' LIMIT 1'
			);
			$this->displayConfirmationsOnload($this->l('Process deleted.'));
		} else {
			$this->displayErrorOnload($this->l('Process not found'));
		}

	}

	public function submitProcess()
	{
		$name = $this->l('Process name');
		if (Tools::getValue('name'))
			$name = Tools::getValue('name');

		$process_active = 0;
		if (Tools::getValue('process_active'))
			$process_active = 1;

		$global_link_active = 0;
		if (Tools::getValue('global_link_active'))
			$global_link_active = 1;

		$filters = false;
		if (!empty(Tools::getValue('filters')))
			$filters = serialize(Tools::getValue('filters'));

		$and_or_between_filters = "AND";
		if (Tools::getValue('and_or_between_filters')=="OR")
			$and_or_between_filters = "OR";

		$week_days = array('monday','tuesday','wednesday','thursday','friday','saturday','sunday');
		foreach($week_days as $day){
			if (isset($_POST['weekdays'][$day])){
				$process_time_from[$day][] = sprintf("%02d:%02d", $_POST['from_hour_'.$day], $_POST['from_min_'.$day]);
				$process_time_to[$day][] = sprintf("%02d:%02d", $_POST['to_hour_'.$day], $_POST['to_min_'.$day]);
			}
		}
		$process_from = serialize($process_time_from);// TODO1 - what happens if $process_time_from isnt set at all? (lets just test)
		$process_to = serialize($process_time_to);// TODO1 - what happens if $process_time_to isnt set at all? (lets just test)

		$unique_time_criteria_method = false;
		if (Tools::getValue('unique_time_criteria_method'))
			$unique_time_criteria_method = Tools::getValue('unique_time_criteria_method');

		$sort_by = 0;
		if (Tools::getValue('sort_by'))
			$sort_by = Tools::getValue('sort_by');

		$action_set_order_state_succeded = false;
		if (Tools::getValue('action_set_order_state_succeded'))
			$action_set_order_state_succeded = (int)Tools::getValue('action_set_order_state_succeded');

		$action_set_order_state_failed = false;
		if (Tools::getValue('action_set_order_state_failed'))
			$action_set_order_state_failed = (int)Tools::getValue('action_set_order_state_failed');

		$action_alert_on_true = 0;
		if (Tools::getValue('action_alert_on_true'))
			$action_alert_on_true = 1;

		$action_alert_on_false = 0;
		if (Tools::getValue('action_alert_on_false'))
			$action_alert_on_false = 1;

		$action_call_method = false;
		if (Tools::getValue('action_call_method'))
			$action_call_method = Tools::getValue('action_call_method');

		$id_employee = false;
		if (Tools::getValue('id_employee'))
			$id_employee = Tools::getValue('id_employee');

		if (Tools::getValue('id_process')){
			if (!$process = Db::getInstance()->getRow('
				SELECT `id_process`
				FROM `'._DB_PREFIX_.'autoprocess_process`
				WHERE `id_process` = '.(int)Tools::getValue('id_process')
			)){
				$this->displayErrorOnload($this->l('Something went wrong - this process id doesnt exist'));
				Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules').'&configure='.$this->name);
			} else {
				$execute = Db::getInstance()->execute('
					UPDATE `'._DB_PREFIX_.'autoprocess_process`
					SET
						`name` = "'.pSQL($name).'",
						`process_active` = "'.pSQL($process_active).'",
						`global_link_active` = "'.pSQL($global_link_active).'",
						`filters` = "'.pSQL($filters).'",
						`and_or_between_filters` = "'.pSQL($and_or_between_filters).'",
						`process_from` = "'.pSQL($process_from).'",
						`process_to` = "'.pSQL($process_to).'",
						`sort_by` = "'.pSQL($sort_by).'",
						`id_employee` = "'.pSQL($id_employee).'",
						`action_call_method` = "'.pSQL($action_call_method).'",
						`action_set_order_state_succeded` = "'.pSQL($action_set_order_state_succeded).'",
						`action_set_order_state_failed` = "'.pSQL($action_set_order_state_failed).'",
						`action_alert_on_true` = "'.pSQL($action_alert_on_true).'",
						`action_alert_on_false` = "'.pSQL($action_alert_on_false).'",
						`unique_time_criteria_method` = "'.pSQL($unique_time_criteria_method).'"
					WHERE `id_process` = '.(int)Tools::getValue('id_process'));
				$id_process = (int)Tools::getValue('id_process');
			}
		} else {
			$execute = Db::getInstance()->execute('
				INSERT INTO `'._DB_PREFIX_.'autoprocess_process`
					(
						`name`,
						`process_active`,
						`global_link_active`,
						`filters`,
						`and_or_between_filters`,
						`process_from`,
						`process_to`,
						`sort_by`,
						`id_employee`,
						`action_call_method`,
						`action_set_order_state_succeded`,
						`action_set_order_state_failed`,
						`action_alert_on_true`,
						`action_alert_on_false`,
						`unique_time_criteria_method`
					)
				VALUES
					(
						"'.pSQL($name).'",
						"'.pSQL($process_active).'",
						"'.pSQL($global_link_active).'",
						"'.pSQL($filters).'",
						"'.pSQL($and_or_between_filters).'",
						"'.pSQL($process_from).'",
						"'.pSQL($process_to).'",
						"'.pSQL($sort_by).'",
						"'.pSQL($id_employee).'",
						"'.pSQL($action_call_method).'",
						"'.pSQL($action_set_order_state_succeded).'",
						"'.pSQL($action_set_order_state_failed).'",
						"'.pSQL($action_alert_on_true).'",
						"'.pSQL($action_alert_on_false).'",
						"'.pSQL($unique_time_criteria_method).'"
					)');
			$id_process = (int)Db::getInstance()->Insert_ID();
		}
		if (Db::getInstance()->getMsgError()){
			return $this->displayError($this->l(Db::getInstance()->getMsgError()));
		} else {
			$this->displayConfirmationsOnload($this->l($name.' was successfully saved'));
			// now lets refresh and show the main page
			if (Tools::isSubmit('SubmitProcessStay'))
				Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules').'&configure='.$this->name.'&edit_process=1&id_process='.$id_process);
			Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules').'&configure='.$this->name);
		}
	}

	public function renderFormEditFilter()
	{
		$id_filter = (int)Tools::getValue('id_filter');
		if (!$query = Db::getInstance()->getRow('
				SELECT *
				FROM `'._DB_PREFIX_.'autoprocess_filter`
				WHERE `id_filter` = '.$id_filter
		)){
			// now lets refresh and show the main page
			$this->displayErrorOnload($this->l('Error: filter id doesnt exist'));
			Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules').'&configure='.$this->name);
		}

		$from_date = '';
		if ($query['from_date'] != '0000-00-00')
			if (Validate::isDateFormat($query['from_date'])) // TODO3 - maybe we should return some error message if not validated
				$from_date = $query['from_date'];

		$to_date = '';
		if ($query['to_date'] != '0000-00-00')
			if (Validate::isDateFormat($query['to_date'])) // TODO3 - maybe we should return some error message if not validated
				$to_date = $query['to_date'];

		$this->smartyAssignGeneral();
		$this->context->smarty->assign(array(
			'id_filter' => $id_filter,
			'copy_filter' => Tools::isSubmit('copy_filter'),
			'name' => $query['name'].((Tools::isSubmit('copy_filter'))?' '.$this->l('Copy'):''),
			'current_states' => Tools::unSerialize($query['current_states']),
			'previous_states' => Tools::unSerialize($query['previous_states']),
			'never_had_states' =>Tools::unSerialize($query['never_had_states']),
			'delivery_countries' => Tools::unSerialize($query['delivery_countries']),
			'payment_modules' => Tools::unSerialize($query['payment_modules']),
			'from_date'=> $from_date,
			'to_date' => $to_date,
			'min_num_orders' => $query['min_num_orders'],
			'max_num_orders' => $query['max_num_orders'],
			'manufacturers' => Tools::unSerialize($query['manufacturers']),
			'manufacturer_order_line' => $query['manufacturer_order_line'],
			'suppliers' => Tools::unSerialize($query['suppliers']),
			'supplier_order_line' => $query['supplier_order_line'],
			'warehouses' => Tools::unSerialize($query['warehouses']),
			'warehouse_order_line' => $query['warehouse_order_line'],
			'order_age_type' => $query['order_age_type'],
			'order_age_more_or_less_than' => $query['order_age_more_or_less_than'],
			'order_age_number' => $query['order_age_number'],
			'order_comment_option' => $query['order_comment_option'],
			'words_in_comment' => $query['words_in_comment']
		));
		return $this->display(__FILE__, 'views/templates/admin/filter.tpl');
	}

	public function renderFormAddFilter()
	{
		$this->smartyAssignGeneral();
		$this->context->smarty->assign(array(
			'id_filter' => false,
			'name' => $this->l('Filter name'),
			'current_states' => false,
			'previous_states' => false,
			'never_had_states' =>false,
			'delivery_countries' => false,
			'payment_modules' => false,
			'from_date'=> false,
			'to_date' => false,
			'min_num_orders' => false,
			'max_num_orders' => false,
			'manufacturers' => false,
			'manufacturer_order_line' => false,
			'suppliers' => false,
			'supplier_order_line' => false,
			'warehouses' => false,
			'warehouse_order_line' => false,
			'order_age_type' => false,
			'order_age_more_or_less_than' => false,
			'order_age_number' => false,
			'order_comment_option' => false,
			'words_in_comment' => false
		));
		return $this->display(__FILE__, 'views/templates/admin/filter.tpl');
	}

	public function orderLineMethodValid($str)
	{
		if ($str == 'minimumOneLine' || $str == 'allLines')
			return $str;
		return false;
	}

	//process post data
	public function submitFilter()
	{
		$name = Tools::getValue('name');
		if (!$name)
			$name = '----';

		$current_states = false;
		if (!empty(Tools::getValue('current_states')))
			$current_states = serialize(Tools::getValue('current_states'));

		$previous_states = false;
		if (!empty(Tools::getValue('previous_states')))
			$previous_states = serialize(Tools::getValue('previous_states'));

		$never_had_states = false;
		if (!empty(Tools::getValue('never_had_states')))
			$never_had_states = serialize(Tools::getValue('never_had_states'));

		$delivery_countries = false;
		if (!empty(Tools::getValue('delivery_countries')))
			$delivery_countries = serialize(Tools::getValue('delivery_countries'));

		$payment_modules = false;
		if (!empty(Tools::getValue('payment_modules')))
			$payment_modules = serialize(Tools::getValue('payment_modules'));

		$supplier_order_line = $this->orderLineMethodValid(Tools::getValue('supplier_order_line'));
		$suppliers = false;
		if (!empty(Tools::getValue('suppliers')))
			$suppliers = serialize(Tools::getValue('suppliers'));

		$manufacturer_order_line = $this->orderLineMethodValid(Tools::getValue('manufacturer_order_line'));
		$manufacturers = false;
		if (!empty(Tools::getValue('manufacturers')))
			$manufacturers = serialize(Tools::getValue('manufacturers'));

		$warehouse_order_line = $this->orderLineMethodValid(Tools::getValue('warehouse_order_line'));
		$warehouses = false;
		if (!empty(Tools::getValue('warehouses')))
			$warehouses = serialize(Tools::getValue('warehouses'));

		$order_age_more_or_less_than = $order_age_number = $order_age_type = false;
		if ((int)Tools::getValue('order_age_number')
			&& (Tools::getValue('order_age_more_or_less_than') == 'less' || Tools::getValue('order_age_more_or_less_than') == 'more')
			&& (Tools::getValue('order_age_type') == 'DAY' || Tools::getValue('order_age_type') == 'HOUR' || Tools::getValue('order_age_type') == 'SECOND') )
		{
			$order_age_more_or_less_than = Tools::getValue('order_age_more_or_less_than');
			$order_age_number = (int)Tools::getValue('order_age_number');
			$order_age_type = Tools::getValue('order_age_type');
		}

		$min_num_orders = (int)Tools::getValue('min_num_orders');
		$max_num_orders = (int)Tools::getValue('max_num_orders');

		$order_comment_option = false;
		$words_in_comment = false;
		if (Tools::getValue('order_comment_option')){
			$order_comment_option = Tools::getValue('order_comment_option');
			if ($order_comment_option == 'contain' || $order_comment_option == 'notcontain')
				if (trim(Tools::getValue('words_in_comment')))
					$words_in_comment = trim(Tools::getValue('words_in_comment'));
				else
					$order_comment_option = false;
		}

		$from_date = 'null';
		if (Validate::isDateFormat(Tools::getValue('from_date'))) // TODO3 - maybe we should return some error message if not validated
			$from_date = Tools::getValue('from_date');

		$to_date = 'null';
		if (Validate::isDateFormat(Tools::getValue('to_date'))) // TODO3 - maybe we should return some error message if not validated
			$to_date = Tools::getValue('to_date');

		if (Tools::getValue('id_filter')){
			if (!$query = Db::getInstance()->getRow('
				SELECT `id_filter`
				FROM `'._DB_PREFIX_.'autoprocess_filter`
				WHERE `id_filter` = '.(int)Tools::getValue('id_filter')
			)){
				$this->displayErrorOnload($this->l('something went wrong - this filter id doesnt exist'));
				Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules').'&configure='.$this->name);
			} else {
				$execute = Db::getInstance()->execute('
					UPDATE `'._DB_PREFIX_.'autoprocess_filter`
					SET
						`name` = "'.pSQL($name).'",
						`current_states` = "'.pSQL($current_states).'",
						`previous_states` = "'.pSQL($previous_states).'",
						`never_had_states` = "'.pSQL($never_had_states).'",
						`from_date` = "'.pSQL($from_date).'",
						`to_date` = "'.pSQL($to_date).'",
						`min_num_orders` = "'.pSQL($min_num_orders).'",
						`max_num_orders` = "'.pSQL($max_num_orders).'",
						`delivery_countries` = "'.pSQL($delivery_countries).'",
						`payment_modules` = "'.pSQL($payment_modules).'",
						`suppliers` = "'.pSQL($suppliers).'",
						`supplier_order_line` = "'.pSQL($supplier_order_line).'",
						`manufacturers` = "'.pSQL($manufacturers).'",
						`manufacturer_order_line` = "'.pSQL($manufacturer_order_line).'",
						`warehouses` = "'.pSQL($warehouses).'",
						`warehouse_order_line` = "'.pSQL($warehouse_order_line).'",
						`order_age_more_or_less_than` = "'.pSQL($order_age_more_or_less_than).'",
						`order_age_type` = "'.pSQL($order_age_type).'",
						`order_age_number` = "'.pSQL($order_age_number).'",
						`order_comment_option` = "'.pSQL($order_comment_option).'",
						`words_in_comment` = "'.pSQL($words_in_comment).'"
					WHERE `id_filter` = '.(int)Tools::getValue('id_filter'));
				$id_filter = (int)Tools::getValue('id_filter');
			}
		} else {
			$execute = Db::getInstance()->execute('
				INSERT INTO `'._DB_PREFIX_.'autoprocess_filter`
					(
						`name`,
						`current_states`,
						`previous_states`,
						`never_had_states`,
						`from_date`,
						`to_date`,
						`min_num_orders`,
						`max_num_orders`,
						`delivery_countries`,
						`payment_modules`,
						`suppliers`,
						`supplier_order_line`,
						`manufacturers`,
						`manufacturer_order_line`,
						`warehouses`,
						`warehouse_order_line`,
						`order_age_more_or_less_than`,
						`order_age_type`,
						`order_age_number`,
						`order_comment_option`,
						`words_in_comment`
					)
				VALUES
					(
						"'.pSQL($name).'",
						"'.pSQL($current_states).'",
						"'.pSQL($previous_states).'",
						"'.pSQL($never_had_states).'",
						"'.pSQL($from_date).'",
						"'.pSQL($to_date).'",
						"'.pSQL($min_num_orders).'",
						"'.pSQL($max_num_orders).'",
						"'.pSQL($delivery_countries).'",
						"'.pSQL($payment_modules).'",
						"'.pSQL($suppliers).'",
						"'.pSQL($supplier_order_line).'",
						"'.pSQL($manufacturers).'",
						"'.pSQL($manufacturer_order_line).'",
						"'.pSQL($warehouses).'",
						"'.pSQL($warehouse_order_line).'",
						"'.pSQL($order_age_more_or_less_than).'",
						"'.pSQL($order_age_type).'",
						"'.pSQL($order_age_number).'",
						"'.pSQL($order_comment_option).'",
						"'.pSQL($words_in_comment).'"
					)');
			$id_filter = (int)Db::getInstance()->Insert_ID();
		}
		if (Db::getInstance()->getMsgError()){
			return $this->displayError($this->l(Db::getInstance()->getMsgError()));
		} else {
			$this->displayConfirmationsOnload($this->l($name.' was successfully saved'));
			// now lets refresh and show the main page
			if (Tools::isSubmit('SubmitFilterStay'))
				Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules').'&configure='.$this->name.'&edit_filter=1&id_filter='.$id_filter);
			Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules').'&configure='.$this->name);
		}
	}

	public function deleteFilter($filterId)
	{
		$flag=false;
		if ($filterId){
			$query  = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
				SELECT `filters` FROM `'._DB_PREFIX_.'autoprocess_process`'
			);
			// lets first chech if filter is in use - if it is we wont allow to delete
			foreach($query as $val){
				if (in_array($filterId, Tools::unSerialize($val['filters']))){
					$flag=true;
					break;
				}
			}

			if ($flag){
				$this->displayErrorOnload($this->l('This filter cant be deleted at them moment. Filter is currently assigned to some process. Please remove this filter from all processes and try again.'));
			} else {
				$filter_query = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('
					SELECT `id_filter`
					FROM `'._DB_PREFIX_.'autoprocess_filter`
					WHERE `id_filter` = '.$filterId
				);
				if ($filter_query){
					Db::getInstance()->execute('
						DELETE FROM `'._DB_PREFIX_.'autoprocess_filter`
						WHERE `id_filter` = '.$filterId.' LIMIT 1'
					);
					$this->displayConfirmationsOnload($this->l('Filter deleted'));
				} else {
					$this->displayErrorOnload($this->l('Filter not found'));
				}
			}
		} else {
			$this->displayErrorOnload($this->l('Filter not found'));
		}
	}

	public function renderFormEditProcess()
	{

		$id_process = (int)Tools::getValue('id_process');
		if (!$query = Db::getInstance()->getRow('
				SELECT *
				FROM `'._DB_PREFIX_.'autoprocess_process`
				WHERE `id_process` = '.$id_process
		)){
			$this->displayErrorOnload($this->l('Error: process id doesnt exist'));
			Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules').'&configure='.$this->name);
		}

		$this->smartyAssignGeneral();
		$this->context->smarty->assign(array(
			'id_process' => $id_process,
			'copy_process' => Tools::isSubmit('copy_process'),
			'name' => $query['name'].((Tools::isSubmit('copy_process'))?' '.$this->l('Copy'):''),
			'process_active' => $query['process_active'],
			'sort_by' => $query['sort_by'],
			'global_link_active' => $query['global_link_active'],
			'cron_url'=> $this->generateCronURL($id_process),
			'simulate_url'=> $this->generateCronURL($id_process, true),
			'process_from' => Tools::unSerialize($query['process_from']),
			'process_to' => Tools::unSerialize($query['process_to']),
			'and_or_between_filters' => $query['and_or_between_filters'],
			'filters' => Tools::unSerialize($query['filters']),
			'unique_time_criteria_method' => $query['unique_time_criteria_method'],
			'id_employee' => $query['id_employee'],
			'action_call_method' => $query['action_call_method'],
			'action_set_order_state_succeded' => $query['action_set_order_state_succeded'],
			'action_set_order_state_failed' => $query['action_set_order_state_failed'],
			'action_alert_on_true' => $query['action_alert_on_true'],
			'action_alert_on_false' => $query['action_alert_on_false'],
		));

		return $this->display(__FILE__, 'views/templates/admin/process.tpl');
	}

	public function toggleActiveProcess($id_process, $toggle_active_process=false)
	{
		if ($toggle_active_process=='off'||$toggle_active_process=='on'){
			$process_active = 0;
			if ($toggle_active_process=='on')$process_active = 1;
			$execute = Db::getInstance()->execute('
				UPDATE `'._DB_PREFIX_.'autoprocess_process`
				SET `process_active` = "'.$process_active.'"
				WHERE `id_process` = '.$id_process);
			if (Db::getInstance()->getMsgError()){
				$this->displayErrorOnload($this->l(Db::getInstance()->getMsgError()));
			} else {
				$this->displayConfirmationsOnload($this->l('Saved'));
			}
		}
	}

	public function toggleActiveGlobalLink($id_process, $toggle_global_link_active=false)
	{
		if ($toggle_global_link_active=='off'||$toggle_global_link_active=='on'){
			$global_link_active = 0;
			if ($toggle_global_link_active=='on')$global_link_active = 1;
			$execute = Db::getInstance()->execute('
				UPDATE `'._DB_PREFIX_.'autoprocess_process`
				SET `global_link_active` = "'.$global_link_active.'"
				WHERE `id_process` = '.$id_process);
			if (Db::getInstance()->getMsgError()){
				$this->displayErrorOnload($this->l(Db::getInstance()->getMsgError()));
			} else {
				$this->displayConfirmationsOnload($this->l('Saved'));
			}
		}
	}

	public function submitPaymentModules()
	{
		if (trim(Tools::getValue('payment_modules'))){
			Configuration::updateValue('PS_AUTOPROCESS_PAYMENT_MODULES', pSQL(trim(Tools::getValue('payment_modules'))));
			$this->displayConfirmationsOnload($this->l('Saved'));
		} else {
			$this->displayErrorOnload($this->l('Not saved'));
		}
	}

	public function submitAdToLog()
	{
		if (Tools::getValue('ad_to_log')=='on' || Tools::getValue('ad_to_log')=='off'){
			Configuration::updateValue('PS_AUTOPROCESS_ADD_TO_LOG', pSQL(trim(Tools::getValue('ad_to_log'))));
			$this->displayConfirmationsOnload($this->l('Saved'));
		} else {
			$this->displayErrorOnload($this->l('Not saved'));
		}
	}

	public function getContent()
	{

		$message = '';

		if (Tools::isSubmit('SubmitProcess') || Tools::isSubmit('SubmitProcessStay')){
			$message = $this->submitProcess();
			// now lets just show the main page
		}

		if (Tools::getValue('deleteProcess')){
			$this->deleteProcess((int)Tools::getValue('id_process'));
			Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules').'&configure='.$this->name);
		}

		if (Tools::isSubmit('SubmitFilter') || Tools::isSubmit('SubmitFilterStay')){
			$message = $this->submitFilter();
			// now lets just show the main page
		}

		if (Tools::getValue('delete_filter')){
			$this->deleteFilter((int)Tools::getValue('id_filter'));
			Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules').'&configure='.$this->name);
		}

		if (Tools::getValue('toggle_global_link_active')){
			$this->toggleActiveGlobalLink((int)Tools::getValue('id_process'),Tools::getValue('toggle_global_link_active'));
			Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules').'&configure='.$this->name);
		}

		if (Tools::getValue('toggle_active_process')){
			$this->toggleActiveProcess((int)Tools::getValue('id_process'),Tools::getValue('toggle_active_process'));
			Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules').'&configure='.$this->name);
		}

		if (Tools::isSubmit('submitPaymentModules')){
			$this->submitPaymentModules();
			Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules').'&configure='.$this->name);
		}

		if (Tools::isSubmit('submitAdToLog')){
			$this->submitAdToLog();
			Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules').'&configure='.$this->name);
		}

		if (Tools::getValue('add_new_process')){
			return $this->renderFormAddProcess();

		} else if (Tools::getValue('edit_process') || Tools::getValue('copy_process')){
			return $this->renderFormEditProcess();

		} else if (Tools::getValue('add_new_filter')){
			return $this->renderFormAddFilter();

		} else if (Tools::getValue('edit_filter') || Tools::getValue('copy_filter')){
			return $this->renderFormEditFilter();


		} else {
			// show the main page
			$this->smartyAssignGeneral();
			$this->context->smarty->assign(array(
				'message' => $message,
				'global_url'=> $this->generateCronURL(),
				'simulate_url'=> $this->generateCronURL(false, true),
				'ad_to_log' => Configuration::get('PS_AUTOPROCESS_ADD_TO_LOG'),
				'payment_modules' => Configuration::get('PS_AUTOPROCESS_PAYMENT_MODULES')
			));
			return $this->display(__FILE__, 'views/templates/admin/main.tpl');
		}
	}
	public function smartyAssignGeneral()
	{
		$process_list = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT * FROM `'._DB_PREFIX_.'autoprocess_process` ORDER BY `sort_by` ASC');
		foreach ($process_list as $key => $process) {
			$process_list[$key]['simulate_url'] = $this->generateCronURL($process['id_process'], true);
			$process_list[$key]['trigger_url'] = $this->generateCronURL($process['id_process']);
		}
		$this->context->smarty->assign(array(
			'weekdays' => array('monday','tuesday','wednesday','thursday','friday','saturday','sunday'),
			'hours' => array('00'=>'00','01'=>'01','02'=>'02','03'=>'03','04'=>'04','05'=>'05','06'=>'06','07'=>'07','08'=>'08','09'=>'09','10'=>'10','11'=>'11','12'=>'12','13'=>'13','14'=>'14','15'=>'15','16'=>'16','17'=>'17','18'=>'18','19'=>'19','20'=>'20','21'=>'21','22'=>'22','23'=>'23'),
			'minutes' => array('00'=>'00','10'=>'10','20'=>'20','30'=>'30','40'=>'40','50'=>'50'),
			'module_url' => $this->context->link->getAdminLink('AdminModules').'&configure='.$this->name,
			'uri' => $this->getPathUri(),
			'supplier_list' => Supplier::getSuppliers(false, (int)Context::getContext()->language->id, true),
			'payment_method_list' => $this->getAvailablePaymentMethodList(),
			'manufacturer_list' => Manufacturer::getManufacturers(false, (int)Context::getContext()->language->id),
			'warehouse_list' => Warehouse::getWarehouses(true),
			'status_list' => OrderState::getOrderStates((int)Context::getContext()->language->id),
			'country_list' =>  Country::getCountries((int)Context::getContext()->language->id, true),
			'unique_time_criteria_method_list'=> $this->getAvailableUniqueTimeCriterias(),
			'action_call_method_list'=> $this->getAvailableCallToActionMethods(),
			'employee_list'=> Employee::getEmployees(),
			'process_list' => $process_list,
			'filter_list' => Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT * FROM `'._DB_PREFIX_.'autoprocess_filter` ORDER BY `id_filter` ASC'),
			'direct_call_actions' => $this->getAvailableDirectCallToActionMethods()
		));
	}

	public function changeOrderState($id_order=false, $id_order_state=false, $id_employee=false)
	{
		if (
			$id_order && is_numeric($id_order) && $id_order > 0
			&& $id_order_state && is_numeric($id_order_state) && $id_order_state > 0
			&& $id_employee && is_numeric($id_employee) && $id_employee > 0
		){
			$order = new Order($id_order);
			if (Validate::isLoadedObject($order) && isset($order)){
				$order_state = new OrderState($id_order_state);
				if (Validate::isLoadedObject($order_state)){
					$employee = new Employee($id_employee);
					if (Validate::isLoadedObject($employee)){

						// TODO2 - maybe find a better way to do this.
						// but if i dont do this, it will fail on change status where stock is adjusted.
						Context::getContext()->employee = $employee;

						$current_order_state = $order->getCurrentOrderState();
						if ($current_order_state->id != $order_state->id){
							// Create new OrderHistory
							$history = new OrderHistory();
							$history->id_order = $order->id;
							$history->id_employee = $id_employee;

							$use_existings_payment = false;
							if (!$order->hasInvoice())
								$use_existings_payment = true;
							$history->changeIdOrderState((int)$order_state->id, $order, $use_existings_payment);

							$carrier = new Carrier($order->id_carrier, $order->id_lang);
							$templateVars = array();
							if ($history->id_order_state == Configuration::get('PS_OS_SHIPPING') && $order->shipping_number)
								$templateVars = array('{followup}' => str_replace('@', $order->shipping_number, $carrier->url));
							// Save all changes
							if ($history->addWithemail(true, $templateVars)){
								// synchronizes quantities if needed..
								if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')){
									foreach ($order->getProducts() as $product){
										if (StockAvailable::dependsOnStock($product['product_id']))
											StockAvailable::synchronize($product['product_id'], (int)$product['id_shop']);
									}
								}
								$this->addToLog('AutoProcess::changeOrderState - Order state changed', 1, null, 'Order', (int)$order->id, true, $id_employee);
								return true;
							}
							$this->addToLog('AutoProcess::changeOrderState - Order state changed but unable to send email', 1, null, 'Order', (int)$order->id, true, $id_employee);
							return true;
						} else {
							$this->addToLog(sprintf('AutoProcess::changeOrderState - order already assigned status %s.', $id_order, $order_state->name), 1, null, 'Order', (int)$order->id, true, $id_employee);
							// TODO2 - could discuss here whether to return true or false
							// my argument for returning true is that order now have the wished order state, so what harm can that do?
							// Harm scenario: it could create a loop if manager didnt think the consequences of his filters/proces settings
							return true;
						}
					} else {
						$this->addToLog(sprintf('AutoProcess::changeOrderState - id_employee %d doesnt exist', $id_employee), 1, null, 'Order', (int)$order->id, true, $id_employee);
						return false;
					}
				} else {
					$this->addToLog(sprintf('AutoProcess::changeOrderState - new id_status %d is invalid', $id_order_state), 1, null, 'Order', (int)$order->id, true, $id_employee);
					return false;
				}
			} else {
				$this->addToLog('AutoProcess::changeOrderState - order '.$id_order.' - not an object', 3, null, null, null, true, $id_employee);
				return false;
			}
		} else {
			$this->addToLog('AutoProcess::changeOrderState - missing some param. either id_order or id_order_state or id_employee', 3, null, null, null, true, $id_employee);
			return false;
		}
	}

	public function id_order_state_exist($id_order_state=false)
	{
		if (!$id_order_state)
			return false;
		// TODO2 use _PS_USE_SQL_SLAVE_ and when not to? go through rest of DB queries and set them correct too
		return (bool)Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('
			SELECT `id_order_state`
			FROM `'._DB_PREFIX_.'order_state`
			WHERE `id_order_state` = '.(int)$id_order_state.' AND `deleted` = 0');
	}

	public function getInitialMessageByCartId($id_cart)
	{
		// TODO2 - we gotta make sure that db table "ps_message" only contains order comments and not any
		// messages added later in the ordering handling
		// I want only to look for anything in the comment that was added when the order was initially created
		$message = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
			SELECT m.*
			FROM `'._DB_PREFIX_.'message` m
			WHERE m.`id_cart` = '.(int)$id_cart.'
			GROUP BY m.`id_message`
			ORDER BY m.`date_add` DESC'
		);
		return ((isset($message['message']) && $message['message'])? $message['message'] : false);
	}

	/**
	* Call a method dynamically
	*
	* @param string $method
	* @param array $args
	* @return mixed
	*/
	public function callMethod($method_string, $args)
	{
		if (!$this->isMethodCallable($method_string))
			return false;

		// TODO - make sure the lines below works!
		if (strstr($method_string, '::')) {
			// example could be "Tools::withdrawPayment" which will call Tools::withdrawPayment($id_order)
			list($classname, $methodname) = explode('::', $method_string);
			return call_user_func_array(array($classname, $methodname), $args);
		} else {
			// example could be $this->withdrawPayment which will call $this->withdrawPayment($id_order)
			//return $this->{$method_string}($id_order);// or maybe get_class($this)
			return call_user_func_array(array($this, $method_string), $args);
		}
	}
	/**
	* Check if available Call a method dynamically
	*
	* @param string $method
	* @param array $args
	* @return mixed
	*/
	public function isMethodCallable($method_string)
	{
		if (!$method_string)
			return false;

		return true;
		/* TODO1 - make this work! and when it works remove the return true above
		if (strstr($method_string, '::')) {
			list($classname, $methodname) = explode('::', $method_string);
			// example could be "Tools::withdrawPayment" which will call Tools::withdrawPayment($id_order)
			return (class_exists($classname) && method_exists($classname, $methodname)); // or maybe function_exists($classname::$methodname) or maybe whatever
		} else {
			// example could be $this->withdrawPayment which will call $this->withdrawPayment($id_order)
			return method_exists($this, $method_string);// or maybe get_class($this)
		}
		*/
	}
	public function addToLog($txt='', $score=1, $dd=null, $clas=null, $id=null, $gg=true, $id_employee=null)
	{
		if (Configuration::get('PS_AUTOPROCESS_ADD_TO_LOG')=='on')
			PrestaShopLogger::addLog($txt, $score, $dd, $clas, $id, $gg, $id_employee);
	}

	public function sendMailAlertToAdmin($id_order=false, $id_employee=false){
		if (!$id_order)
			return false;

		$email_from = Configuration::get('PS_SHOP_EMAIL'); // email from
		$email_to = Configuration::get('PS_SHOP_EMAIL'); // email to
		/*
		if ($id_employee){
			$employee = new Employee($id_employee);
			$email_to = $employee->email; // email to
		}
		*/

		$email_content = 'Hi Admin,'."\n\n".

			'Please take a look at order #'.(int)$id_order.' '."\n\n".

			$this->context->shop->getBaseURL().str_replace(array('/index.php','/admin'), array('/','admin'), Configuration::get('Admin_Index')).$this->context->link->getAdminLink('AdminOrders').'&vieworder&id_order='.(int)$id_order."\n\n".

			'Best regards'."\n\n".
			'The fancy system ;)';

		mb_internal_encoding("UTF-8");
		$headers = 'Mime-Version: 1.0'."\r\n".
					'Content-Type: text/plain;charset=UTF-8'."\r\n".
					'From: '.$email_from."\r\n".
					'Reply-To: '.$email_from."\r\n".
					'X-Mailer: PHP/'.phpversion();
		if (mb_send_mail($email_to, 'Take a look at order #'.(int)$id_order, $email_content, $headers)) {
			$this->addToLog('AutoProcess::sendMailAlertToAdmin - Notification sent via email', 1, null, null, null, true);
			return true;
		}
		return false;
	}

	public function executeCronJob()
	{

		$return_html = '*********** BEGIN ***********<br>';
		$days_array = array(1=>'monday',2=>'tuesday',3=>'wednesday',4=>'thursday',5=>'friday',6=>'saturday',7=>'sunday');

		$processes = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT *
			FROM `'._DB_PREFIX_.'autoprocess_process`
			WHERE '.(Tools::isSubmit('simulate') ? ' (`process_active` = 1 OR `process_active` = 0)' : '`process_active` = 1 ').
				(Tools::isSubmit('id_process') ? ' AND `id_process` = '.(int)Tools::getValue('id_process') : ' AND `global_link_active` = 1 ' ).'
			ORDER BY `sort_by` ASC ');
		foreach ($processes as $process){

			$error = false;
			$return_html .= '--------- Process "'.$process["name"].'" ---------<br>';

			$this->addToLog('AutoProcess::executeCronJob - Process '.$process["name"], 1, null, 'Process', $process['id_process'], true, $process['id_employee']);

			// lets check if action status is still available
			if ($process["action_set_order_state_succeded"] && !$this->id_order_state_exist($process["action_set_order_state_succeded"])){
				$this->toggleActiveProcess((int)$process['id_process'], 'off');
				$this->addToLog('AutoProcess::executeCronJob - ABORT Order status '.$process["action_set_order_state_succeded"].' dont exist. Process is deactivated', 3, null, 'Process', (int)Tools::getValue('id_process'), true, $process['id_employee']);
				$error = true;
				$return_html .= 'something went wrong - process '.$process["name"].' is deactivated - action Order status dont exist anymore. before you re-activate that process, first select a valid order status.<br>';
			}

			// lets check if action status is still available
			if ($process["action_set_order_state_failed"] && !$this->id_order_state_exist($process["action_set_order_state_failed"])){
				$this->toggleActiveProcess((int)$process['id_process'], 'off');
				$this->addToLog('AutoProcess::executeCronJob - ABORT Order status '.$process["action_set_order_state_failed"].' dont exist. Process is deactivated', 3, null, 'Process', (int)Tools::getValue('id_process'), true, $process['id_employee']);
				$error = true;
				$return_html .= 'something went wrong - process '.$process["name"].' is deactivated - action Order status dont exist anymore. before you re-activate that process, first select a valid order status.<br>';
			}

			// lets check if all filter order statuses are still available
			if ($process["filters"]){
				$filter_query = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
					SELECT *
					FROM `'._DB_PREFIX_.'autoprocess_filter`
					WHERE `id_filter` IN('.implode(',', Tools::unSerialize($process['filters'])).') ');
				foreach ($filter_query as $filter){
					if ($filter["current_states"] || $filter["previous_states"] || $filter["never_had_states"]){
						// TODO1 - will this actually work? Im not sure how unSerialize works
						$filters_to_check = array();
						if (!empty(Tools::unSerialize($filter['current_states'])))
							$filters_to_check = array_merge($filters_to_check, Tools::unSerialize($filter['current_states']));
						if (!empty(Tools::unSerialize($filter['previous_states'])))
							$filters_to_check = array_merge($filters_to_check, Tools::unSerialize($filter['previous_states']));
						if (!empty(Tools::unSerialize($filter['never_had_states'])))
							$filters_to_check = array_merge($filters_to_check, Tools::unSerialize($filter['never_had_states']));
						foreach ($filters_to_check as $id_order_state){
							if (!$this->id_order_state_exist($id_order_state)){
								$this->toggleActiveProcess((int)$process['id_process'], 'off');
								$this->addToLog('AutoProcess::executeCronJob - ABORT Order status '.$id_order_state.' (in filter '.$filter["name"].') dont exist. Process is deactivated', 3, null, 'Process', $process['id_process'], true, $process['id_employee']);
								$error = true;
								$return_html .= 'something went wrong - process '.$process["name"].' is deactivated - One of filters have an order status that dont exist anymore. before you re-activate that process, first you mus re-save all filters this process is using<br>';
							}
						}
					}
				}
			}

			// test whether a Class method exist
			// example : $this->is_work_day_in_denmark(date('c'))
			if ($process["unique_time_criteria_method"] && !$this->isMethodCallable($process["unique_time_criteria_method"])){
				$this->toggleActiveProcess((int)$process['id_process'], 'off');
				$this->addToLog('AutoProcess::executeCronJob - ABORT Process '.$process["name"].' error calling time Class method '.$process["unique_time_criteria_method"], 3, null, 'Process', $process['id_process'], true, $process['id_employee']);
				$error = true;
				$return_html .= 'something went wrong - process '.$process["name"].' is deactivated - error happened calling time Class method  ".$process["unique_time_criteria_method"]." - before you activate this process again first take a good look or ask a developer to inspect it<br>';
			}

			// test whether a Class method exist
			if ($process["action_call_method"] && !$this->isMethodCallable($process["action_call_method"])){
				$this->toggleActiveProcess((int)$process['id_process'], 'off');
				$this->addToLog('AutoProcess::executeCronJob - ABORT Process '.$process["name"].' error calling action Class method '.$process["action_call_method"], 3, null, 'Process', $process['id_process'], true, $process['id_employee']);
				$error = true;
				$return_html .= 'something went wrong - process '.$process["name"].' is deactivated - error happened calling action Class method '.$process["action_call_method"].' - before you activate this process again first take a good look or ask a developer to inspect it<br>';
			}

			if (!$process["action_call_method"] && !$process["action_set_order_state_succeded"]){
				$this->addToLog('AutoProcess::executeCronJob - aborted because process '.$process["name"].' has no actions', 3, null, 'Process', $process['id_process'], true, $process['id_employee']);
				$error = true;
				$return_html .= 'Process "'.$process["name"].'" has no action at all, so why waste energy on looping it?<br>';
			}

			// if any errors then lets just move on to the next process
			if ($error)
				continue;

			// is it allowed to run this process now? if it isnt, lets just move on to the next process
			//if ( $process["unique_time_criteria_method"] && !$this->{$process["unique_time_criteria_method"]}(date('c')) ){
			if ( $process["unique_time_criteria_method"] && !$this->callMethod($process["unique_time_criteria_method"], array(date('c'))) ){
				$this->addToLog('AutoProcess::executeCronJob - aborted by time Class method '.$process["unique_time_criteria_method"], 1, null, 'Process', $process['id_process'], true, $process['id_employee']);
				continue;
			}

			// TODO1 - make sure this is working correctly
			// is it allowed to run this process now? if it isnt, lets just move on to the next process
			if ($process['process_from'] && $process['process_to']){
				$process_from = Tools::unSerialize($process['process_from']);
				$process_to = Tools::unSerialize($process['process_to']);
				if (isset($process_from[$days_array[date('N')]][0]) && isset($process_to[$days_array[date('N')]][0])){
					list($process_from_hrs, $process_from_mins) = explode(':', $process_from[$days_array[date('N')]][0]);
					list($process_to_hrs, $process_to_mins) = explode(':', $process_to[$days_array[date('N')]][0]);
					if (date('His') < sprintf("%02d%02d00", $process_from_hrs, $process_from_mins) || date('His') > sprintf("%02d%02d00", $process_to_hrs, $process_to_mins)){
						// this days is one of the days that is allowed, but we are now outside the time linitation
						$this->addToLog('AutoProcess::executeCronJob - aborted by time limitation ', 1, null, 'Process', $process['id_process'], true, $process['id_employee']);
						continue;
					}
				} else {
					// this days is not one of the days that is allowed
					$this->addToLog('AutoProcess::executeCronJob - aborted by time limitation ', 1, null, 'Process', $process['id_process'], true, $process['id_employee']);
					continue;
				}
			}

			$matching_orders = array();
			$failed_orders = array();
			if (!empty(Tools::unSerialize($process["filters"]))){

				$filter_query = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
					SELECT *
					FROM `'._DB_PREFIX_.'autoprocess_filter`
					WHERE `id_filter` IN('.implode(',', Tools::unSerialize($process['filters'])).') ');
				$numFilters = count($filter_query);
				$i = 0;
				foreach ($filter_query as $filter){
					++$i;

					// TODO2 - maybe add this filter option in next version
					// payment method - dankort, visa etc
					//$order->payment

					$where_queries = $inner_joins = false;
					if (!empty($failed_orders)){
						$where_queries .=  ' AND o.`id_order` NOT IN ('.implode(',', $failed_orders).') ';
					}
					$current_states  = Tools::unSerialize($filter['current_states']);
					if (!empty($current_states)){
						$where_queries .=  ' AND o.`current_state` IN ('.implode(',', (array)implode(',', $current_states)).') ';
					}
					$previous_states = Tools::unSerialize($filter['previous_states']);
					if (!empty($previous_states)){
						$where_queries .= ' AND EXISTS (
								 SELECT 1 FROM `'._DB_PREFIX_.'order_history` oh1
								 WHERE oh1.`id_order` = o.`id_order`
								 AND oh1.`id_order_state` IN ('.implode(',', (array)implode(',', $previous_states)).') ) ';
					}
					$never_had_states = Tools::unSerialize($filter['never_had_states']);
					if (!empty($never_had_states))
						$where_queries .= ' AND NOT EXISTS (
								 SELECT 1 FROM `'._DB_PREFIX_.'order_history` oh2
								 WHERE oh2.`id_order` = o.`id_order`
								 AND oh2.`id_order_state` IN ('.implode(',', (array)implode(',', $never_had_states)).') ) ';

					if ($filter['order_age_more_or_less_than'] && $filter['order_age_number'] && $filter['order_age_type'])
						$where_queries .= ' AND o.`date_add` '.(($filter['order_age_more_or_less_than']=='less') ? '>' : '<').
												' DATE_SUB(NOW(), INTERVAL '.(int)$filter['order_age_number'].' '.$filter['order_age_type'].') ';

					// orders delivery address country
					$delivery_countries = Tools::unSerialize($filter['delivery_countries']);
					if (!empty($delivery_countries)){
						$inner_joins .= ' INNER JOIN `'._DB_PREFIX_.'address` a ON a.`id_address` = o.`id_address_delivery` ';// TODO1 will this work? should it be LEFT JOIN or?
						$where_queries .= ' AND a.`id_country` IN ('.implode(',', (array)implode(',', $delivery_countries)).') ';
					}

					$payment_modules = Tools::unSerialize($filter['payment_modules']);
					if (!empty($payment_modules)){
						$inner_joins .= ' INNER JOIN `'._DB_PREFIX_.'module` m ON o.`module` = m.`name` ';// TODO1 - should this be LEFT JOIN?
						$where_queries .= ' AND m.`id_module` IN ('.implode(',', (array)implode(',', $payment_modules)).') ';
					}

					// Lets find matching orders
					if (!($orders_query = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
						SELECT o.`id_order`, o.`id_cart`, o.`id_customer` '. // TODO should it be DISTINCT?
						'FROM `'._DB_PREFIX_.'orders` o '.
						$inner_joins.
						'WHERE o.`id_order` != "" '. // TODO1 hmmm
						(($filter['from_date'] != '0000-00-00') ? ' AND DATE(o.`date_add`) >= "'.pSQL($filter['from_date']).'" ' : '').
						(($filter['to_date'] != '0000-00-00') ? ' AND DATE(o.`date_add`) < "'.pSQL($filter['to_date']).'" ' : '').
						$where_queries
					))) // if no matching orders
						continue; // TODO1 i dont know if this is correct to use continue here

					foreach ($orders_query as $v){
						$nogo=false;

						// how many ordes does this customer have?
						if (($filter['min_num_orders']) || ($filter['max_num_orders'])){
							$num_orders = Order::getCustomerNbOrders($v['id_customer']);
							if (($filter['min_num_orders'] && $num_orders < $filter['min_num_orders']) || ($filter['max_num_orders'] && $filter['max_num_orders'] < $num_orders))
								$nogo=true;
						}

						$message = $this->getInitialMessageByCartId($v['id_cart']);// I dont trust that its finding correct comments - if a comment was added later, will it appear here?
						if ($filter['order_comment_option'] == 'isempty'){
							// "isempty" - find those orders WITHOUT a comment
							if ($message)// its not empty
								$nogo=true;

						} elseif ($filter['order_comment_option'] == 'isnotempty'){
							// "isnotempty" - find those orders WITH a comment
							if (!$message)// its empty
								$nogo=true;

						} elseif ($filter['order_comment_option'] == 'contain'){
							if (!$message){// its empty
								$nogo=true;
							} else {// its not empty
								$words_in_comment = $filter['words_in_comment'];
								if ($words_in_comment){
									$word_is_in_message = false;
									foreach(explode(',', $words_in_comment) as $val){
										if ($val && stristr($message, trim($val)) !== FALSE){// check if word is in message
											$word_is_in_message = true;
											break;
										}
									}
									if (!$word_is_in_message)
										$nogo=true;
								}
							}
						} elseif ($filter['order_comment_option'] == 'notcontain'){
							if ($message){// its not empty - so we check if its got the unwanted word(s)
								$words_in_comment = $filter['words_in_comment'];
								if ($words_in_comment){
									foreach(explode(',', $words_in_comment) as $val){
										if ($val && stristr($message, trim($val)) !== FALSE)// check if word is in message
											$nogo = true;
											break;
									}
								}
							}
						}


						////////////////////////////////////////////////////////
						//////////// orders product lines related //////////////
						////////////////////////////////////////////////////////

						// if OR -> then we dont check product lines - if match then we just return all product lines
						if ($process['and_or_between_filters']=='OR'){
							if (!$nogo)
								$matching_orders[$v['id_order']] = $v['id_order'];
							continue; // move on to next order - to see if that's (also / also not) matching
						}


						if (!$nogo){

							$order = new Order($v['id_order']);
							$products = $order->getProducts();

							if (!Validate::isLoadedObject($order)){
								$failed_orders[$v['id_order']] = True;
								continue;
							}

							/********* Warehouse *********/
							if (!empty(Tools::unSerialize($filter['warehouses'])) && ($method = $this->orderLineMethodValid($filter['warehouse_order_line']))){
								if ($method == 'allLines'){
									// all order product lines must match at least one of the selected warehouses
									// so in a way we can say that,
									// - all products should be from same warehouse, and
									// - all products should be from one of the selected warehouses
									$warehouse_list = $order->getWarehouseList();
									if (count($warehouse_list) != 1 || (!Db::getInstance()->getRow('
										SELECT 1
											FROM `'._DB_PREFIX_.'order_detail` od
											WHERE od.`id_order` = '.$v['id_order'].'
											AND od.`id_warehouse` IN ('.implode(',', (array)implode(',', Tools::unSerialize($filter['warehouses']))).') '
									))){
										$failed_orders[$v['id_order']] = True;
										continue;
									}
								} else {
									// one or more order product lines must match at least one of the selected warehouses
									// so in a way we can say that,
									// - one product should be from one of the selected warehouses
									if (!Db::getInstance()->getRow('
										SELECT 1
											FROM `'._DB_PREFIX_.'order_detail` od
											WHERE od.`id_order` = '.$v['id_order'].'
											AND od.`id_warehouse` IN ('.implode(',', (array)implode(',', Tools::unSerialize($filter['warehouses']))).') '
									)){
										$failed_orders[$v['id_order']] = True;
										continue;
									}
								}
							}

							/********* Supplier *********/
							// TODO2 - one could argue that supplier is dynamic and could easily be changed after order is received - this could create serious issues and conflicts
							if (!empty(Tools::unSerialize($filter['suppliers'])) && ($method = $this->orderLineMethodValid($filter['supplier_order_line']))){
								if ($method == 'allLines'){
									// all order product lines must match at least one of the selected suppliers
									// so in a way we can say that,
									// - all products should be from same supplier, and
									// - all products should be from one of the selected suppliers
									$latest_id_supplier = false;
									foreach ($products as $product){
										if (!$product['id_supplier'] || $latest_id_supplier && $product['id_supplier'] != $latest_id_supplier){
											// all is NOT same supplier
											$failed_orders[$v['id_order']] = True;
											break;
										}
										$latest_id_supplier = $product['id_supplier'];
									}
									if (isset($failed_orders[$v['id_order']]))
										continue;

									$one_is_ok = false;
									foreach ($products as $product)
										if (in_array($product['id_supplier'], (array)implode(',', Tools::unSerialize($filter['suppliers'])))){
											$one_is_ok = true;
											break;
										}
									if (!$one_is_ok){
										$failed_orders[$v['id_order']] = True;
										continue;
									}
								} else {
									// one or more order product lines must match at least one of the selected suppliers
									// so in a way we can say that,
									// - one product should be from one of the selected suppliers
									$one_is_ok = false;
									foreach ($products as $product)
										if (in_array($product['id_supplier'], (array)implode(',', Tools::unSerialize($filter['suppliers'])))){
											$one_is_ok = true;
											break;
										}
									if (!$one_is_ok){
										$failed_orders[$v['id_order']] = True;
										continue;
									}
								}
							}

							/********* Manufacturer *********/
							// TODO2 - one could argue that manufacturer is dynamic and could easily be changed after order is received - this could create serious issues and conflicts
							if (!empty(Tools::unSerialize($filter['manufacturers'])) && ($method = $this->orderLineMethodValid($filter['manufacturer_order_line']))){
								if ($method == 'allLines'){
									// all order product lines must match at least one of the selected manufacturers
									// so in a way we can say that,
									// - all products should be from same manufacturer, and
									// - all products should be from one of the selected manufacturers
									$latest_id_manufacturer = false;
									foreach ($products as $product){
										if (!$product['id_manufacturer'] || $latest_id_manufacturer && $product['id_manufacturer'] != $latest_id_manufacturer){
											// all is NOT same manufacturer
											$failed_orders[$v['id_order']] = True;
											break;
										}
										$latest_id_manufacturer = $product['id_manufacturer'];
									}
									if (isset($failed_orders[$v['id_order']]))
										continue;

									$one_is_ok = false;
									foreach ($products as $product)
										if (in_array($product['id_manufacturer'], (array)implode(',', Tools::unSerialize($filter['manufacturers'])))){
											$one_is_ok = true;
											break;
										}
									if (!$one_is_ok){
										$failed_orders[$v['id_order']] = True;
										continue;
									}
								} else {
									// one or more order product lines must match at least one of the selected manufacturers
									// so in a way we can say that,
									// - one product should be from one of the selected manufacturers
									$one_is_ok = false;
									foreach ($products as $product)
										if (in_array($product['id_manufacturer'], (array)implode(',', Tools::unSerialize($filter['manufacturers'])))){
											$one_is_ok = true;
											break;
										}
									if (!$one_is_ok){
										$failed_orders[$v['id_order']] = True;
										continue;
									}
								}
							}

							// using AND: finding orders that passed filter1 AND filter2 etc
							if (($i === $numFilters) && ($process['and_or_between_filters']=='AND') && !isset($failed_orders[$v['id_order']])){
								// its the last round in loop - all filters must have matched - so this order is a match :O)
								$matching_orders[$v['id_order']] = $v['id_order'];
							}
						}
						////////////////////////////////////////////////
					}
				}
			}

			// if no orders is left - move on to next process
			if (!count($matching_orders))
				continue;

			// make sure required files are included
			if ($process["action_call_method"])
				foreach ($this->method_required_files($process["action_call_method"]) as $file)
					require_once($file);

			// run through orders and trigger process actions (action_call_method and action_set_order_state_succeded)
			foreach ($matching_orders as $id_order){

				$return_html .= 'id_order = '.$id_order.' -> all product lines<br>';

				if (!Tools::isSubmit('simulate')){
					$result=true;
					if ($process["action_call_method"]){
						// result in a php call something like: $result = $this->withdraw_payment($id_order);
						if ($result = $this->callMethod($process["action_call_method"], array($id_order)))
							$this->addToLog('AutoProcess::executeCronJob - called action Class method '.$process["action_call_method"].'. answer:TRUE ', 1, null, 'Process', $process['id_process'], true, $process['id_employee']);
						else
							$this->addToLog('AutoProcess::executeCronJob - called action Class method '.$process["action_call_method"].'. answer:FALSE ', 1, null, 'Process', $process['id_process'], true, $process['id_employee']);
					}

					// change status if action_set_order_state_succeded is set
					if ($result && $process["action_set_order_state_succeded"]){
						// set new status and trigger normal Prestashop behaviour like sending email etc
						$this->addToLog('AutoProcess::executeCronJob - changeOrderState A '.$process["action_set_order_state_succeded"], 1, null, 'Process', $process['id_process'], true, $process['id_employee']);
						if ($this->changeOrderState($id_order, $process["action_set_order_state_succeded"], $process['id_employee'])){ // TODO2 what to do if order allready have this status? or if some error happens in changeOrderState?
							$this->addToLog('AutoProcess::executeCronJob - Status changed to '.$process["action_set_order_state_succeded"], 1, null, 'Process', $process['id_process'], true, $process['id_employee']);
							if ($process["action_alert_on_true"])
								$this->sendMailAlertToAdmin($id_order, $process['id_employee']);
						} else
							$this->addToLog('AutoProcess::executeCronJob - Status NOT changed to '.$process["action_set_order_state_succeded"], 1, null, 'Process', $process['id_process'], true, $process['id_employee']);
					} else if (!$result && $process["action_set_order_state_failed"]){
						$this->addToLog('AutoProcess::executeCronJob - changeOrderState B '.$process["action_set_order_state_failed"], 1, null, 'Process', $process['id_process'], true, $process['id_employee']);
						if ($this->changeOrderState($id_order, $process["action_set_order_state_failed"], $process['id_employee'])){ // TODO2 what to do if order allready have this status? or if some error happens in changeOrderState?
							$this->addToLog('AutoProcess::executeCronJob - Status changed to '.$process["action_set_order_state_failed"], 1, null, 'Process', $process['id_process'], true, $process['id_employee']);
							if ($process["action_alert_on_false"])
								$this->sendMailAlertToAdmin($id_order, $process['id_employee']);
						} else
							$this->addToLog('AutoProcess::executeCronJob - Status NOT changed to '.$process["action_set_order_state_failed"], 1, null, 'Process', $process['id_process'], true, $process['id_employee']);
					}
				}
			}
		}
		//return ((Tools::isSubmit('simulate') || Tools::isSubmit('debug')) ? $return_html : 'processed').'*********** END ***********';
		return $return_html.'*********** END ***********';
	}



	//////////////////////////////////////////////////////////////////////////////////////////////////
	////////////////////////////////  Unique time Class methods  /////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////////

	// TODO3 - add some caching as this Class method often is repeated with same param and same return
	public function getHolidaysInDenmark($year=false){
		$easterDate		= easter_date($year);
		$easterDay		= date('j', $easterDate);
		$easterMonth	= date('n', $easterDate);
		$easterYear		= date('Y', $easterDate);
		return array(
			// These days have a fixed date
			mktime(0, 0, 0, 1,  1,  $year),	// 1. jan Nytårsdag
			//mktime(0, 0, 0, 5,  1,  $year),	// 1. maj
			mktime(0, 0, 0, 6,  5,  $year),	// 5. juni Grundlovsdag
			mktime(0, 0, 0, 12, 24, $year),	// 24. dec Juleaftensdag
			mktime(0, 0, 0, 12, 25, $year),	// 25. dec 1. Juledag
			mktime(0, 0, 0, 12, 26, $year),	// 26. dec 2. Juledag
			mktime(0, 0, 0, 12, 31, $year),	// 31. dec Nytårsaften

			// These days have a date depending on easter
			mktime(0, 0, 0, $easterMonth, $easterDay - 7,	$easterYear),	// Palmesøndag
			mktime(0, 0, 0, $easterMonth, $easterDay - 3,	$easterYear),	// Skærtorsdag
			mktime(0, 0, 0, $easterMonth, $easterDay - 2,	$easterYear),	// Langfredag
			mktime(0, 0, 0, $easterMonth, $easterDay,		$easterYear),	// Påskedag
			mktime(0, 0, 0, $easterMonth, $easterDay + 1,	$easterYear),	// 2. Påskedag
			mktime(0, 0, 0, $easterMonth, $easterDay + 26,	$easterYear),	// St. Bededag
			mktime(0, 0, 0, $easterMonth, $easterDay + 39,	$easterYear),	// Kristi Himmelfart
			mktime(0, 0, 0, $easterMonth, $easterDay + 49,	$easterYear),	// Pinse
			mktime(0, 0, 0, $easterMonth, $easterDay + 50,	$easterYear),	// 2. Pinsedag
		);
	}
	public function is_work_day_in_denmark($date_to_check=false){

		if (!$date_to_check)
			$date_to_check=date('c');

		if (date('N', strtotime($date_to_check))>5)
			return false;

		$day	= date('j', strtotime($date_to_check));
		$month	= date('n', strtotime($date_to_check));
		$year	= date('Y', strtotime($date_to_check));

		if ( in_array(mktime(0, 0, 0, $month, $day,  $year), $this->getHolidaysInDenmark($year)) ){
			return false;
		} else {
			return true;
		}
	}

	//////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////  Unique action methods  ////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////////

	//files to require_once - eg. file with this Class method and maybe its dependencies.
	public function method_required_files($method_name=false){
		if ($method_name == "withdraw_quickpay_payment")
			return array(dirname(__FILE__)."/../quickpay/quickpay.php");
		if ($method_name == "someexample")
			return array("/incl/somedependencyfile.php", "/incl/myfile.php");
		// Add your own here...
		return array();
	}
	// sort of an abstract class to use for the basics
	public function basicActionMethod($id_order=false){
		if (!$id_order)
			return false;
		$order = new Order($id_order);
		if (!Validate::isLoadedObject($order))
			return false;
		return $order;
	}
	// Do nothing - just return true nomatter what
	public function do_nothing($id_order=false){
		return true;
	}

	// Withdraw quickpay payment - the new platform v10+
	public function withdraw_quickpay_payment($id_order=false){
		if (!($order = $this->basicActionMethod($id_order)))
			return false;

		$this->addToLog('AutoProcess::withdraw_quickpay_payment', 1, null, 'Order', (int)$order->id, true);

		// TODO3 maybe lookup is it already paid? + lookup available amount to withdraw

		if (!Db::getInstance()->getRow('
			SELECT `module`
			FROM '._DB_PREFIX_.'orders
			WHERE `id_order` = '.$id_order.'
			AND `module` = "quickpay" '))
		{
			$this->addToLog('AutoProcess::withdraw_quickpay_payment ABORTED - quickpay module not found in orders table ', 1, null, 'Order', (int)$order->id, true);
			return false;
		}

		$amount = number_format($order->total_paid_tax_incl * 100, 2, '.', '');
		$this->addToLog('AutoProcess::withdraw_quickpay_payment capture amount : '.$amount, 1, null, 'Order', (int)$order->id, true);

		$quickpay = new QuickPay();
		$quickpay->getSetup();
		if ($trans = Db::getInstance()->getRow('SELECT *
			FROM '._DB_PREFIX_.'quickpay_execution
			WHERE `id_cart` = '.$order->id_cart.'
			ORDER BY `id_cart` ASC'))
		{
			$vars = $quickpay->jsonDecode($trans['json']);
			if (isset($vars->operations)){
				if ($amount <= $vars->operations[0]->amount && $amount <= ($vars->operations[0]->amount - $vars->balance)){
					// TODO - any way to finalize transaction?
					$result = $quickpay->doCurl('payments/'.$trans['trans_id'].'/capture', array('amount='.$amount));
					if ($result){
						$this->addToLog('AutoProcess::withdraw_quickpay_payment Capture successful.', 1, null, 'Order', (int)$order->id, true);
						return true;
					}
					$this->addToLog('AutoProcess::withdraw_quickpay_payment Capture failed 1', 1, null, 'Order', (int)$order->id, true);
				} else {
					$this->addToLog('AutoProcess::withdraw_quickpay_payment balance='.$vars->balance.' amount='.$vars->operations[0]->amount, 1, null, 'Order', (int)$order->id, true);
				}
			}
		} else {
			$this->addToLog('AutoProcess::withdraw_quickpay_payment no quickpay_execution', 1, null, 'Order', (int)$order->id, true);
		}
		return false;
	}

	/*
	// Withdraw quickpay payment - the old platform before v10
	public function withdraw_quickpay_payment_old_platform($id_order=false){
		if (!($order = $this->basicActionMethod($id_order)))
			return false;

		$this->addToLog('AutoProcess::withdraw_quickpay_payment', 1, null, 'Order', (int)$order->id, true);

		// TODO3 maybe lookup is it already paid? + lookup available amount to withdraw
		if (Configuration::get('_QUICKPAY_API') == 1) {
			$protocol = 7;
			$merchant = Configuration::get('_QUICKPAY_MERCHANTID');
			$md5secret = Configuration::get('_QUICKPAY_MD5');

			if (!$trans = Db::getInstance()->getRow('
				SELECT `transaction`
				FROM `'._DB_PREFIX_.'quickpay_transactions`
				WHERE `id_cart` LIKE "___'.$order->id_cart.'.%"
				ORDER BY `id_cart` ASC'))
			{
				$trans = Db::getInstance()->getRow('
					SELECT `transaction`
					FROM `'._DB_PREFIX_.'quickpay_transactions`
					WHERE `id_cart` = '.$order->id_cart);
			}
			if ((!$trans) || (!$module = Db::getInstance()->getRow('
				SELECT `module`
				FROM '._DB_PREFIX_.'orders
				WHERE `id_order` = '.$id_order.'
				AND (`module` = "quickpay1" OR `module` = "quickpay") ')))
				return false;

			$final = 1;

			// TODO1 - temp rounding fix my local rounding issue - should be removed
			$capamount = number_format($order->total_paid_tax_incl * 100, 2, '.', '');
			$this->addToLog('AutoProcess::withdraw_quickpay_payment capture amount rounded*100 : '.$capamount, 1, null, 'Order', (int)$order->id, true);

			$md5check = md5($protocol.'capture'.$merchant.$capamount.$final.$trans['transaction'].$md5secret);
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "https://secure.quickpay.dk/api");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, 'protocol='.$protocol.'&finalize='.$final.'&transaction='.$trans['transaction'].'&amount='.$capamount.'&protocol='.$protocol.'&msgtype=capture&merchant='.$merchant.'&md5check='.$md5check.'');
			$data = curl_exec($ch);
			curl_close($ch);
			$xml = new SimpleXmlElement($data);
			if ($xml->qpstat == '000'){
				$this->addToLog('AutoProcess::withdraw_quickpay_payment '.$this->l('Capture successful.'), 1, null, 'Order', (int)$order->id, true);
				return true;
			}
			if($xml->qpstat != '000' && $xml->qpstat) {
				$this->addToLog('AutoProcess::withdraw_quickpay_payment '.$this->l('Capture failed with error code:').' '.$xml->qpstat.' '.$xml->qpstatmsg, 1, null, 'Order', (int)$order->id, true);
			}
		}
		return false;
	}
	*/

	public function send_order_to_warehouse_LM($id_order=false){
		if (!($order = $this->basicActionMethod($id_order)))
			return false;

		$settings_LM_id = 3; // ID of LM Warehouse
		$settings_LM_ftp_server = 'remote.lm-lagerhotel.dk'; // LM FTP host
		$settings_LM_ftp_user_name = 'xxxxx'; // LM FTP username
		$settings_LM_ftp_user_pass = 'xxxxx!'; // LM FTP password
		$settings_LM_outbound_folder = '/outbound/'; // LM FTP folder for outbound orders

		$order_ref = $order->id.'DFDK00';

		/*
		OBS Det virker ikke  ordentligt - id_carrier ændres hver gang man ændrer/gemmer carrier betingelser
		if ($order->id_carrier == 63 && count($order->getProducts()) < 6) // foderprøve forsendelser sendes med carrier id 63 og mærkes SMP (dog max 5 varelinjer)
			$order_ref .= 'SMP';
		else // andre forsendelser mærkes bare ALM
			$order_ref .= 'ALM';
		*/

		$order_ref .= 'ALM';

		// Add  random number to ref - as it must be unique
		$order_ref .= rand(10000,99999);

		$delivery = new Address($order->id_address_delivery);

		$this->addToLog('AutoProcess::send_order_to_warehouse_LM - Send order to warehouse LM ', 1, null, 'Order', (int)$order->id, true);

		$string_xml = false;
		foreach ($order->getProducts() as $product){
			if ($product['id_warehouse'] == $settings_LM_id
				&& !$product['is_virtual'])
			{
				if  ($product['warehouse_location'] = WarehouseProductLocation::getProductLocation($product['product_id'], $product['product_attribute_id'], $product['id_warehouse'])){
					$string_xml .= 	'  <ITEMLINE>'."\n".
									'    <CUSTPRODCODE>'.htmlspecialchars($product['warehouse_location'], ENT_QUOTES, 'UTF-8').'</CUSTPRODCODE>'."\n".
									'    <EANCODE>'.htmlspecialchars($product['warehouse_location'], ENT_QUOTES, 'UTF-8').'</EANCODE>'."\n".
									'    <VARIANT />'."\n".
									'    <EANCODEVARIANT />'."\n".
									'    <DESCR>'.htmlspecialchars($this->fix_product_name($product['product_name']), ENT_QUOTES, 'UTF-8').'</DESCR>'."\n".
									'    <DESCR2 />'."\n".
									'    <QUANT>'.$product['product_quantity'].'</QUANT>'."\n".
									'    <QUANTUOM>STK</QUANTUOM>'."\n".
									'    <NOOFPALLET />'."\n".
									'    <WEIGHT />'."\n".
									'    <TOTWEIGHT />'."\n".
									'    <WEIGHTUOM />'."\n".
									'    <VOL />'."\n".
									'    <TOTVOL />'."\n".
									'    <VOLUOM />'."\n".
									'    <LOADM />'."\n".
									'    <TOTLOADM />'."\n".
									'    <TRACKING />'."\n".
									'  </ITEMLINE>'."\n";
				} else {
					$this->addToLog('AutoProcess::send_order_to_warehouse_LM - '.$product['product_name'].' is missing warehouse_location', 3, null, 'Order', (int)$order->id, true);
					return false;
				}
			}
		}

		// TODO add a return false if any quiantity will become negative in warehouse? Need some api integration.

		if (!$string_xml)
			return false;

		/* fjernet gaver pga vi har så lave priser i forvejen
		if ($profitIs = $this->getProfit($order))
			if ($profitIs['color'] == 'green')
				$string_xml .= 	'  <ITEMLINE>'."\n".
								'    <CUSTPRODCODE>911911911</CUSTPRODCODE>'."\n".
								'    <EANCODE>911911911</EANCODE>'."\n".
								'    <VARIANT />'."\n".
								'    <EANCODEVARIANT />'."\n".
								'    <DESCR>'.htmlspecialchars('BONUS', ENT_QUOTES, 'UTF-8').'</DESCR>'."\n".
								'    <DESCR2 />'."\n".
								'    <QUANT>1</QUANT>'."\n".
								'    <QUANTUOM>STK</QUANTUOM>'."\n".
								'    <NOOFPALLET />'."\n".
								'    <WEIGHT />'."\n".
								'    <TOTWEIGHT />'."\n".
								'    <WEIGHTUOM />'."\n".
								'    <VOL />'."\n".
								'    <TOTVOL />'."\n".
								'    <VOLUOM />'."\n".
								'    <LOADM />'."\n".
								'    <TOTLOADM />'."\n".
								'    <TRACKING />'."\n".
								'  </ITEMLINE>'."\n";
		*/


		$string_xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>'."\n".
			'<NAVIPWMS>'."\n".
			'<IDENTIFICATION>'."\n".
			'<PROCESSTYPE>OUTBOUND</PROCESSTYPE>'."\n".
			'<EXTDOCNO />'."\n".
			'<ORIGIN />'."\n".
			'<CREADATE />'."\n".
			'<CREATIME />'."\n".
			'</IDENTIFICATION>'."\n".
			'<HEADER>'."\n".
			'<DATE />'."\n".
			'<CUSTNOEXT />'."\n".
			'<ORDTYPE />'."\n".
			'<CUSTREFDOC>'.$order_ref.'</CUSTREFDOC>'."\n".
			'<TRANSPTYPE />'."\n".
			'<SHIPMETHOD />'."\n".
			'<TRAILER />'."\n".
			'<VEHICLE />'."\n".
			'<DRIVER />'."\n".
			'<CONTAINER />'."\n".
			'<TOTWEIGHT />'."\n".
			'<TOTVOLM3 />'."\n".
			'<TOTLOADM />'."\n".
			'<WEIGHT />'."\n".
			'<WEIGHTUOM />'."\n".
			'<LOADDATE />'."\n".
			'<LOADHOURFROM />'."\n".
			'<LOADHOURTILL />'."\n".
			'<LOADMARGIN />'."\n".
			'<UNLOADDATE />'."\n".
			'<UNLOADHOURFROM />'."\n".
			'<UNLOADHOURTILL />'."\n".
			'<UNLOADMARGIN />'."\n".
			'<ADDRESSES>'."\n".
			'<ADDRESS>'."\n".
			'  <ADDRTYPE>UNLOADING</ADDRTYPE>'."\n".
			'  <ADDRCODE />'."\n".
			'  <ADDRNAME>'.(($delivery->company) ? htmlspecialchars($delivery->company, ENT_QUOTES, 'UTF-8') : htmlspecialchars($delivery->firstname.' '.$delivery->lastname, ENT_QUOTES, 'UTF-8')).'</ADDRNAME>'."\n".
			'  <ADDRESS>'.htmlspecialchars($delivery->address1, ENT_QUOTES, 'UTF-8').'</ADDRESS>'."\n".
			'  <ADDRESS2>'.htmlspecialchars($delivery->other, ENT_QUOTES, 'UTF-8').'</ADDRESS2>'."\n".
			'  <CNTRY>'.Country::getIsoById($delivery->id_country).'</CNTRY>'."\n".
			'  <COUNTY />'."\n".
			'  <CITY>'.htmlspecialchars($delivery->city, ENT_QUOTES, 'UTF-8').'</CITY>'."\n".
			'  <POSTC>'.$delivery->postcode.'</POSTC>'."\n".
			'  <REF>'.htmlspecialchars($delivery->other, ENT_QUOTES, 'UTF-8').'</REF>'."\n".
			'  <EMAIL />'."\n".
			'  <PHONE>'.(($delivery->phone) ? $delivery->phone : $delivery->phone_mobile).'</PHONE>'."\n".
			'  <SMSPHONE>'.(($delivery->phone) ? $delivery->phone : $delivery->phone_mobile).'</SMSPHONE>'."\n".
			'  <CONTACTNAME>'.htmlspecialchars($delivery->firstname.' '.$delivery->lastname, ENT_QUOTES, 'UTF-8').'</CONTACTNAME>'."\n".
			'  <CONTACTPHONE>'.(($delivery->phone) ? $delivery->phone : $delivery->phone_mobile).'</CONTACTPHONE>'."\n".
			'</ADDRESS>'."\n".
			'<ADDRESS>'."\n".
			'<ADDRTYPE>CARRIER</ADDRTYPE>'."\n".
			'<ADDRCODE>GLS</ADDRCODE>'."\n". // TODO1 add other carrier codes
			'<ADDRNAME />'."\n".
			'<ADDRESS />'."\n".
			'<ADDRESS2 />'."\n".
			'<CNTRY />'."\n".
			'<COUNTY />'."\n".
			'<CITY />'."\n".
			'<POSTC />'."\n".
			'<REF />'."\n".
			'</ADDRESS>'."\n".
			'</ADDRESSES>'."\n".
			'<DETAIL>'."\n".
			$string_xml.
			'</DETAIL>'."\n".
			'</HEADER>'."\n".
			'</NAVIPWMS>';

		// set up basic FTP connection
		$conn = ftp_connect($settings_LM_ftp_server);
		$login_result = ftp_login($conn, $settings_LM_ftp_user_name, $settings_LM_ftp_user_pass);
		// check connection
		if ((!$conn) || (!$login_result)) {
			$this->addToLog('AutoProcess::send_order_to_warehouse_LM - FTP connection has failed! Attempted to connect to '.$settings_LM_ftp_server.' for user '.$settings_LM_ftp_user_name, 3, null, 'Order', (int)$order->id, true);
			return false;
		} else {
			$this->addToLog('AutoProcess::send_order_to_warehouse_LM - FTP connection succeded! Connected to '.$settings_LM_ftp_server.', for user '.$settings_LM_ftp_user_name, 1, null, 'Order', (int)$order->id, true);
		}

		$filename = strtolower($order_ref).'.xml';

		// upload string as a file
		//$stream = fopen('text/plain;charset=UTF8,'.$string_xml,'r');
		//$stream = fopen('data://text/plain;base64,'.$string_xml,'r');
		$stream = fopen('data:text/plain;charset=UTF-8,'.$string_xml,'r');
		//$stream = fopen('data://text/plain,'.$string_xml,'r');
		if (ftp_fput($conn, $settings_LM_outbound_folder.$filename, $stream, FTP_ASCII)){ // or FTP_BINARY
			$this->addToLog('AutoProcess::send_order_to_warehouse_LM - successfully uploaded '.$filename, 1, null, 'Order', (int)$order->id, true);
			ftp_close($conn); // close the connection
			fclose($stream);
			//if (is_resource($stream))
			return true;
		}

		$this->addToLog('AutoProcess::send_order_to_warehouse_LM - problem while uploading '.$filename, 3, null, 'Order', (int)$order->id, true);
		ftp_close($conn); // close the connection
		fclose($stream);
		return false;
	}

	public function getProfit($order)
	{
		if (!Validate::isLoadedObject($order))
			return false;

		// $order->total_discounts_tax_excl; // sum rabatter inkl moms
		// $order->total_shipping_tax_excl; // sum fragt ex moms
		// $order->total_products; // sum varer ex moms
		// $order->total_paid_tax_excl; // sum varer+fragt-rabat ex moms

		$products_cost = 0;
		$total_weight = 0.0001;
		$order_details = $order->getOrderDetailList();
		foreach ($order_details as $order_detail)
		{
			$product = new Product($order_detail['product_id']);

			if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && $product->advanced_stock_management)
			{
				if ((int)$product->id_supplier > 0 && $product->active)
					$products_cost += ($order_detail['product_quantity'] * (float)ProductSupplier::getProductPrice((int)$product->id_supplier, $order_detail['product_id'], $order_detail['product_attribute_id'], true) );
				else
					$products_cost += ($order_detail['product_quantity'] * $order_detail['purchase_supplier_price']);
				$total_weight += ($order_detail['product_quantity'] * $order_detail['product_weight']);
			}
		}

		$num_boxes = ceil($total_weight/29);

		$shipping_cost = ceil($num_boxes * 64);

		$fees = ceil($order->total_paid_tax_excl * 0.01)+10;
		$profit_amount = round($order->total_paid_tax_excl - $products_cost - $shipping_cost - $fees);
		$profit_pct = -100;
		if ($order->total_paid_tax_excl > 0)
			$profit_pct = floor(($profit_amount*100)/$order->total_paid_tax_excl);

		// icon that indicate if its a acceptable profit or alarming
		// alarm, bell, warning, star-1, check, heart, fire
		$icon = 'check';
		$color = 'orange';
		if ($profit_amount>70 || $profit_pct>12){
			$color = 'green';
			$icon = 'heart';  // heart, check, star-1
		}
		if ($profit_amount<5 || $profit_pct<2){
			$icon = 'warning'; // fire, warning, bell, alarm
			$color = 'red';
		}

		return array(
			'profit_pct' 	=> $profit_pct,
			'profit_amount' => $profit_amount,
			'products_cost' => $products_cost,
			'total_weight' 	=> number_format($total_weight, 2, ',', ''),
			'num_boxes' 	=> $num_boxes,
			'shipping_cost' => $shipping_cost,
			'fees' 			=> $fees,
			'icon' 			=> $icon,
			'color' 		=> $color,
		);
	}

	public function combine_two_commaseparated_tracking_numbers($orijen=false, $new=false){
		if ($orijen && $new)
			return implode(',', array_unique(array_filter(array_merge(explode(',', $orijen), explode(',', $new)), 'strlen')));
		elseif ($orijen)
			return implode(',', array_filter(explode(',', $orijen), 'strlen'));
		elseif ($new)
			return implode(',', array_filter(explode(',', $new), 'strlen'));
		else
			return false;
	}
	/*
	public function get_and_insert_tracking_numbers_from_warehouse_LM($id_order=false){
		if (!($order = $this->basicActionMethod($id_order)))
			return false;

		// file line example: 154DFDK00ALM36747;082067201132;GLSNORMALUK

		$this->addToLog('AutoProcess::get_and_insert_tracking_numbers_from_warehouse_LM - Get package numbers from warehouse LM', 1, null, 'Order', (int)$order->id, true);

		//$LM_local_folder = '/tracking/onendofday/';
		$LM_local_folder = '/tracking/';
		$LM_local_folder_done = $LM_local_folder.'done/';

		$needle_order_ref = (int)$order->id.'DFDK00';
		$output = array();
		exec('grep -l "'.$needle_order_ref.'" '.$_SERVER['DOCUMENT_ROOT'].$LM_local_folder.'*.csv', $output);
		$tracking_numbers = false;
		$order_carrier = new OrderCarrier((int)$order->id);
		foreach($output as $filename){
			list($ref, $tracking_number, $shipping_method) = explode(';', file_get_contents($filename));

			if ($tracking_number && Validate::isLoadedObject($order_carrier))
				$tracking_numbers .= (($tracking_numbers) ? $tracking_numbers.',' : '').pSQL($tracking_number);

			// move file to folder where we keep a copy of all files
			rename($filename, str_replace($LM_local_folder, $LM_local_folder_done, $filename));
		}
		if ($tracking_numbers && $this->combine_two_commaseparated_tracking_numbers($order_carrier->tracking_number, $tracking_numbers)){
			$order_carrier->tracking_number = $this->combine_two_commaseparated_tracking_numbers($order_carrier->tracking_number, $tracking_numbers);
			if ($order_carrier->update())
				return true;
		}
		return false;
	}
	*/

	public function check_if_any_tracking_numbers_exist_from_warehouse_LM($id_order=false){
		if (!($order = $this->basicActionMethod($id_order)))
			return false;
		// file line example: 154DFDK00ALM36747;082067201132;GLSNORMALUK

		$this->addToLog('AutoProcess::check_if_any_tracking_numbers_exist_from_warehouse_LM - Get package numbers from warehouse LM', 1, null, 'Order', (int)$order->id, true);

		//$LM_local_folder = '/tracking/onsubmit/';
		$LM_local_folder = '/tracking/';
		$LM_local_folder_done = $LM_local_folder.'done/';
		$needle_order_ref = (int)$order->id.'DFDK00';
		$output = array();
		exec('grep -l "'.$needle_order_ref.'" '.$_SERVER['DOCUMENT_ROOT'].$LM_local_folder.'*.csv', $output);
		$tracking_numbers = false;
		$order_carrier = new OrderCarrier((int)$order->id);
		foreach($output as $filename){
			list($ref, $tracking_number, $shipping_method) = explode(';', file_get_contents($filename));

			if ($tracking_number && Validate::isLoadedObject($order_carrier))
				$tracking_numbers .= (($tracking_numbers) ? $tracking_numbers.',' : '').pSQL($tracking_number);

			// move file to folder where we keep a copy of all files
			rename($filename, str_replace($LM_local_folder, $LM_local_folder_done, $filename));
		}
		if ($tracking_numbers && $this->combine_two_commaseparated_tracking_numbers($order_carrier->tracking_number, $tracking_numbers)){
			$order_carrier->tracking_number = $this->combine_two_commaseparated_tracking_numbers($order_carrier->tracking_number, $tracking_numbers);
			if ($order_carrier->update())
				return true;
		}
		return false;
	}

	public function get_GLS_Soap_GetTuDetail_from_tracking_number($tracking_number=false){
		if (!$tracking_number)
			return false;
		$reference = array(
			'Credentials' => array('UserName' => '111111111', 'Password' => '111111111'),
			'RefValue' => $tracking_number
		);
		$client = new SoapClient("http://www.gls-group.eu/276-I-PORTAL-WEBSERVICE/services/Tracking/wsdl/Tracking.wsdl");
		return $client->GetTuDetail($reference);
	}
	public function get_GLS_Soap_GetTuList_from_ReferenceValue($ReferenceValue=false){
		if (!$ReferenceValue)
			return false;
		$reference = array(
			'Credentials' => array('UserName' => '111111111', 'Password' => '111111111'),
			'CustomRef' => $ReferenceValue
		);
		$client = new SoapClient("http://www.gls-group.eu/276-I-PORTAL-WEBSERVICE/services/Tracking/wsdl/Tracking.wsdl");
		return $client->GetTuList($reference);
	}
	public function get_GLS_ReferenceValue_from_a_tracking_number($tracking_number=false){
		if (!$result = $this->get_GLS_Soap_GetTuDetail_from_tracking_number($tracking_number))
			return false;
		if ($result->ExitCode->ErrorDscr == 'OK' && !$result->ExitCode->ErrorCode && $result->CustomerReference->ReferenceValue)
			return $result->CustomerReference->ReferenceValue;
		return false;
	}
	public function get_GLS_tracking_numbers_from_ReferenceValue($ReferenceValue=false){
		if (!$result = $this->get_GLS_Soap_GetTuList_from_ReferenceValue($ReferenceValue))
			return false;
		if ($result->ExitCode->ErrorDscr == 'OK' && !$result->ExitCode->ErrorCode)
			if (is_array($result->TUList)){
				$return = array();
				foreach ($result->TUList as $TUList)
					$return[] = $TUList->RefNo;
				return implode(',', $return);
			} else if (is_object($result->TUList))
				return $result->TUList->RefNo;
		return false;
	}
	public function check_if_all_with_GLS_ReferenceValue_is_delivered($ReferenceValue=false){
		if (!$result = $this->get_GLS_Soap_GetTuList_from_ReferenceValue($ReferenceValue))
			return false;
		if ($result->ExitCode->ErrorDscr == 'OK' && !$result->ExitCode->ErrorCode)
			if (is_array($result->TUList)){// more than one package
				$return = false;
				foreach ($result->TUList as $TUList)
					if ($TUList->CurrentStatus != 'Delivered')
						return false;
					if ($TUList->CurrentStatus == 'Delivered')
						$return = true;
				return $return;
			} else if (is_object($result->TUList))// only one package
				if ($result->TUList->CurrentStatus == 'Delivered')
					return true;
		return false;
	}
	/*public function check_if_GLS_tracking_number_is_delivered($tracking_number=false){
		if (!$tracking_number)
			return false;
		$result = $this->get_GLS_Soap_GetTuDetail_from_tracking_number($tracking_number);
		if ($result->ExitCode->ErrorDscr == 'OK' && !$result->ExitCode->ErrorCode)
			if (is_array($result->History))
				foreach ($result->History as $History)
					if (strpos($History->Desc, 'Leveret') !== false)
						return true;
			else if (is_object($result->History))
				if (strpos($result->History->Desc, 'Leveret') !== false)
					return true;
		return false;
	}
	public function check_if_GLS_package_is_trackable($id_order=false){
		if (!($order = $this->basicActionMethod($id_order)))
			return false;
		// file line example: 154DFDK00ALM36747;082067201132;GLSNORMALUK

		$this->addToLog('AutoProcess::check_if_GLS_package_is_trackable - return true if package(s) appears in GLS system', 1, null, 'Order', (int)$order->id, true);

		$result = $this->get_GLS_Soap_GetTuDetail_from_tracking_number($tracking_number);
		if ($result->ExitCode->ErrorDscr == 'OK' && !$result->ExitCode->ErrorCode)

		$reference = array(
			'Credentials' => array('UserName' => '111111111', 'Password' => '111111111'),
			'RefValue' => $tracking_number
		);
		$client = new SoapClient("http://www.gls-group.eu/276-I-PORTAL-WEBSERVICE/services/Tracking/wsdl/Tracking.wsdl");
		$result = $client->GetTuDetail($reference);
		if ($result->ExitCode->ErrorDscr == 'OK' && !$result->ExitCode->ErrorCode && $result->CustomerReference->ReferenceValue)
			return $result->CustomerReference->ReferenceValue;
		return false;
	}*/
	public function check_if_all_GLS_packages_from_LM_is_delivered($id_order=false){
		if (!($order = $this->basicActionMethod($id_order)))
			return false;

		$this->addToLog('AutoProcess::check_if_all_GLS_packages_from_LM_is_delivered - return true if all packages is delivered', 1, null, 'Order', (int)$order->id, true);

		/*
		$carrier = new Carrier((int)($order->id_carrier), (int)($order->id_lang));
		if ($carrier->url && $order->shipping_number)
			str_replace('@', $order->shipping_number, $carrier->url);

		$order_carrier = new OrderCarrier((int)$order->id);
		$order_carrier->tracking_number
		$order_carrier->url
		*/
		$shipping = $order->getShipping();
		foreach ($shipping as $line) {// avaliable: $line['tracking_number'] $line['url'] $line['id_carrier'] $line['id_order_carrier'] etc.
			if ($line['tracking_number']){
				$ReferenceValueIsChecked = array();
				$delivered = false;
				foreach (explode(',', $line['tracking_number']) as $tracking_number){
					if (!$ReferenceValue = $this->get_GLS_ReferenceValue_from_a_tracking_number($tracking_number))
						return false;
					if (!isset($ReferenceValueIsChecked[$ReferenceValue]))
						$ReferenceValueIsChecked[$ReferenceValue] = true;
						if (!$this->check_if_all_with_GLS_ReferenceValue_is_delivered($ReferenceValue))
							return false;
						$delivered = true;
				}
				return $delivered;
			}
		}
		return false;
	}

	public function get_and_update_gls_tracking_numbers($id_order=false){
		if (!($order = $this->basicActionMethod($id_order)))
			return false;

		$this->addToLog('AutoProcess::get_and_update_gls_tracking_numbers - Get all package numbers from gls (sent from warehouse LM)', 1, null, 'Order', (int)$order->id, true);

		$tracking_numbers = false;
		$shipping = $order->getShipping();
		foreach ($shipping as $line) {// avaliable: $line['tracking_number'] $line['url'] $line['id_carrier'] $line['id_order_carrier'] etc.
			if ($line['tracking_number']){
				$ReferenceValueIsChecked = array();
				foreach (explode(',', $line['tracking_number']) as $tracking_number){
					if (!$ReferenceValue = $this->get_GLS_ReferenceValue_from_a_tracking_number($tracking_number))
						return false;
					if (!isset($ReferenceValueIsChecked[$ReferenceValue]))
						$ReferenceValueIsChecked[$ReferenceValue] = true;
						if ($tracking_number = $this->get_GLS_tracking_numbers_from_ReferenceValue($ReferenceValue))
							$tracking_numbers .= (($tracking_numbers) ? $tracking_numbers.',' : '').$tracking_number;
				}
			}
		}

		$order_carrier = new OrderCarrier((int)$order->id);
		if ($tracking_numbers && Validate::isLoadedObject($order_carrier)){
			$order_carrier->tracking_number = $tracking_numbers;
			if ($order_carrier->update())
				return true;
		}
		return false;
	}


	public function send_order_to_warehouse_RH($id_order=false){
		if (!($order = $this->basicActionMethod($id_order)))
			return false;

		$customer = $order->getCustomer();
		if (!Validate::isLoadedObject($customer))
			return false;

		$settings_RH_id = 2; // ID of RightigHundemad
		$settings_RH_email_to = 'info@leverandornavn.dk'; // email to
		$settings_RH_email_from = $settings_RH_email_copy = Configuration::get('PS_SHOP_EMAIL'); // email from

		$order_ref = $order->id.'DFDK0';

		$delivery = new Address($order->id_address_delivery);

		$this->addToLog('AutoProcess::send_order_to_warehouse_RH - Begin send order to warehouse RH ', 1, null, 'Order', (int)$order->id, true);

		$string = false;
		foreach ($order->getProducts() as $product){
			if ($product['id_warehouse'] == $settings_RH_id
				&& !$product['is_virtual'])
			{
				if  ($product['warehouse_location'] = WarehouseProductLocation::getProductLocation($product['product_id'], $product['product_attribute_id'], $product['id_warehouse'])){
					$string .= ''.$product['product_quantity'].' x '.$product['warehouse_location'].' - '.$product['product_name'].''."\n";
					// $product['reference']
				} else {
					$this->addToLog('AutoProcess::send_order_to_warehouse_RH - '.$product['product_name'].' is missing warehouse_location', 3, null, 'Order', (int)$order->id, true);
					return false;
				}
			}
		}

		if (!$string)
			return false;

		$string = 'Kære Leverandørnavn'."\n\n".

			'Hermed ordre til levering hos vores kunde:'."\n\n".

			'Vor ref: '.$order->id.' (ID '.$order_ref.')'."\n\n".

			'Lovet afsendelse: '.Hook::exec('orderExpectedDeliveryDate', array('order' => $order))."\n".
			'Lovet levering: '.Hook::exec('orderExpectedShippingDate', array('order' => $order))."\n\n".

			'Leveringsadresse:'."\n".
			(($delivery->company) ? 'Firma: '.$delivery->company."\n" : '').
			$delivery->firstname.' '.$delivery->lastname."\n".
			$delivery->address1."\n".
			$delivery->postcode.' '.$delivery->city."\n".
			//$delivery->country."\n".
			"\n".
			(($delivery->other) ? 'Besked til chauffør: '.$delivery->other."\n\n" : '').

			(($delivery->phone) ? 'Kundens telefon: '.$delivery->phone."\n" : '').
			(($delivery->phone_mobile) ? 'Kundens telefon: '.$delivery->phone_mobile."\n" : '').
			//(($customer->email) ? 'Email: '.$customer->email."\n" : '').
			"\n".
			'Varer:'."\n".
			$string.
			"\n".
			'Bekræft venligst med et pakkenummer.'."\n\n".

			'Vores kundenummer: ???'."\n\n".

			'Med venlig hilsen Michael Hjulskov fra Dyrefoder.dk, direkte mobil 22116322'; // TODO2 - link to input tracking number

		mb_internal_encoding("UTF-8");
		$headers = 'Mime-Version: 1.0'."\r\n".
					'Content-Type: text/plain;charset=UTF-8'."\r\n".
					'From: '.$settings_RH_email_from."\r\n".
					'Reply-To: '.$settings_RH_email_from."\r\n".
					'Cc: '.$settings_RH_email_copy."\r\n".
					'X-Mailer: PHP/'.phpversion();
		if (mb_send_mail($settings_RH_email_to, 'Ordre fra Dyrefoder.dk - Vor ref '.$order->id.' (ID '.$order_ref.')', $string, $headers)) {
			$this->addToLog('AutoProcess::send_order_to_warehouse_RH - Order sent to Leverandørnavn dropship supplier ', 1, null, 'Order', (int)$order->id, true);
			return true;
		}
		$this->addToLog('AutoProcess::send_order_to_warehouse_RH - Failed sending order to Leverandørnavn!', 3, null, 'Order', (int)$order->id, true);
		return false;
	}


	public function check_if_order_all_quantities_has_become_available($id_order=false){
		if (!($order = $this->basicActionMethod($id_order)))
			return false;

		$this->addToLog('AutoProcess::check_if_order_all_quantities_has_become_available - Check if all products is available in stock now', 1, null, null, null, true);

		foreach ($order->getProducts() as $product){
			$physical_quantity_in_stock = (int)StockManager::getProductPhysicalQuantities($product['product_id'], $product['product_attribute_id'], array($product['id_warehouse']), false);
			$usable_quantity_in_stock = (int)StockManager::getProductPhysicalQuantities($product['product_id'], $product['product_attribute_id'], array($product['id_warehouse']), true);
			$quantity_available_in_stock = (int)StockAvailable::getQuantityAvailableByProduct($product['product_id'], $product['product_attribute_id'], array($product['id_warehouse']), $order->id_shop);
			if (($quantity_available_in_stock < 0 || $usable_quantity_in_stock < $product['product_quantity'] ) && !$product['is_virtual'])
				return false;
		}
		$this->addToLog('AutoProcess::check_if_order_all_quantities_has_become_available - Seems like all products is now available in stock', 1, null, 'Order', (int)$order->id, true);
		return true;
	}




	//////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////  Direct callable action methods  /////////////////////////////////////
	///////////////////////////  These methods can be called directly ////////////////////////////////
	///////////////////////////  from global cronjob link, by adding /////////////////////////////////
	///////////////////////////  &trigger_execute_function=function_name to url //////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////////


	public function fix_product_name($product_name){
		return preg_replace(array_keys($this->replacement_patterns), $this->replacement_patterns, $product_name);
		//return $product_name;
	}

	public function compare_stock_quanties_with_warehouse_LM(){

		$this->addToLog('AutoProcess::compare_stock_quanties_with_warehouse_LM - Compare stock quantities with warehouse LM', 1, null, null, null, true);

		$settings_LM_id = 3; // ID of LM Warehouse
		$settings_LM_ftp_server = 'remote.lm-lagerhotel.dk'; // LM FTP host
		$settings_LM_ftp_user_name = 'xxxx'; // LM FTP username
		$settings_LM_ftp_user_pass = 'xxxx!'; // LM FTP password
		$settings_LM_stockexport_folder = '/stockexport/'; // LM FTP folder for stockexport
		$settings_LM_stockexport_folder_done = '/stockexport_old_files/';

		// set up basic FTP connection
		$conn = ftp_connect($settings_LM_ftp_server);
		$login_result = ftp_login($conn, $settings_LM_ftp_user_name, $settings_LM_ftp_user_pass);
		// check connection
		if ((!$conn) || (!$login_result)) {
			$this->addToLog('AutoProcess::compare_stock_quanties_with_warehouse_LM - FTP connection has failed! Attempted to connect to '.$settings_LM_ftp_server.' for user '.$settings_LM_ftp_user_name, 3, null, null, null, true);
			return false;
		} else {
			$this->addToLog('AutoProcess::compare_stock_quanties_with_warehouse_LM - FTP connection succeded! Connected to '.$settings_LM_ftp_server.', for user '.$settings_LM_ftp_user_name, 1, null, null, null, true);
		}

		// get list of files
		ftp_pasv($conn, true);
		$files = ftp_nlist($conn, $settings_LM_stockexport_folder);
		if ($files){
			//var_dump($files);
			// get the most recent file
			$mostRecent = array(
				'time' => 0,
				'file' => null
			);
			foreach ($files as $file){
				// get the last modified time for the file
				$time = ftp_mdtm($conn, $file);
				if ($time > $mostRecent['time']) {
					// this file is the most recent so far
					$mostRecent['time'] = $time;
					$mostRecent['file'] = $file;
				}
			}
			//echo 'ftp://'.$settings_LM_ftp_user_name.':'.$settings_LM_ftp_user_pass.'@'.$settings_LM_ftp_server.$mostRecent['file'];
			//$xmlstring = file_get_contents('ftps://'.$settings_LM_ftp_user_name.':'.$settings_LM_ftp_user_pass.'@'.$settings_LM_ftp_server.$mostRecent['file']);
			$xmlstring = file_get_contents('ftp://'.$settings_LM_ftp_user_name.':'.$settings_LM_ftp_user_pass.'@'.$settings_LM_ftp_server.$mostRecent['file']);

			// tranlate xml to array
			$json = json_encode(simplexml_load_string($xmlstring));
			$products = json_decode($json,TRUE);

			$difference_to_report = $errors_to_report = $total_value_txt = $total_value_sum_excl_tax = $total_value_sum_incl_tax = false;
			if (is_array($products['ITEM'])){
				foreach ($products['ITEM'] as $item){
					if ($item['PRODUCTCODE'] == 'DUMMY' || $item['PRODUCTCODE'] == '911911911')
						continue;

					if ( Warehouse::getProductIdFromLocation($item['PRODUCTCODE'], $settings_LM_id) ){
						list($id_product, $id_product_attribute) = explode('_', Warehouse::getProductIdFromLocation($item['PRODUCTCODE'], $settings_LM_id)) ;
						$physical_quantity_in_stock = (int)StockManager::getProductPhysicalQuantities($id_product, $id_product_attribute, array($settings_LM_id), false);
						$usable_quantity_in_stock = (int)StockManager::getProductPhysicalQuantities($id_product, $id_product_attribute, array($settings_LM_id), true);
						$quantity_available_in_stock = (int)StockAvailable::getQuantityAvailableByProduct($id_product, $id_product_attribute, array($settings_LM_id), Shop::getCurrentShop());
						if ($product_name = Product::getProductName($id_product, $id_product_attribute)){
							if ($quantity_available_in_stock > $item['QUANINSTOCK'] || $physical_quantity_in_stock <> $item['QUANINSTOCK'] || $usable_quantity_in_stock <> $item['QUANINSTOCK'])
								$difference_to_report .= $item['PRODUCTCODE'].' '.$this->fix_product_name($product_name).' -> '.($quantity_available_in_stock-$item['QUANINSTOCK']).' stk i forskel ('.$quantity_available_in_stock.' VS '.$item['QUANINSTOCK'].' v/LM) (phys avail:'.$physical_quantity_in_stock.') (usable:'.$usable_quantity_in_stock.') '."\n";
							$price_excl_tax = Product::getPriceStatic(
								(int)$id_product,
								false, // $usetax = true
								((isset($id_product_attribute) && !empty($id_product_attribute)) ? (int)$id_product_attribute : null),
								2, // $decimals = 6,
								null, // $divisor = null
								false, // $only_reduc = false
								false // $usereduc = true
							);
							$price_incl_tax = Product::getPriceStatic(
								(int)$id_product,
								true, // $usetax = true
								((isset($id_product_attribute) && !empty($id_product_attribute)) ? (int)$id_product_attribute : null),
								2, // $decimals = 6,
								null, // $divisor = null
								false, // $only_reduc = false
								false // $usereduc = true
							);
							$price_sum_excl_tax = $price_excl_tax*(int)$item['QUANINSTOCK'];
							$total_value_txt .= (int)$item['QUANINSTOCK'].' x '.$item['PRODUCTCODE'].' '.$this->fix_product_name($product_name).' ('.$price_excl_tax.'/'.$price_incl_tax.') -> '.$price_sum_excl_tax.' ex moms'."\n";
							$total_value_sum_excl_tax += $price_sum_excl_tax;
							$total_value_sum_incl_tax += $price_incl_tax*(int)$item['QUANINSTOCK'];
						} else if ($quantity_available_in_stock <> $item['QUANINSTOCK'] && $product_name = Product::getProductName($id_product))
							$errors_to_report .= $item['PRODUCTCODE'].' '.$this->fix_product_name($product_name).' (variant '.$id_product_attribute.' udgået) -> ('.$item['QUANINSTOCK'].' v/LM)'."\n";
					} else if ($item['QUANINSTOCK'] <> 0){
						$errors_to_report .= $item['PRODUCTCODE'].' ('.$item['QUANINSTOCK'].' v/LM) findes ikke eller deaktiveret eller sat til andet lager i shoppen'."\n";
						// evt todo bruge : WarehouseProductLocation::getProductLocation($id_product, $id_product_attribute, $settings_LM_id)
					}
				}
			}

			// move this used file to folder where we keep a copy of all old xml files
			// and delete if any other files located in folder
			foreach ($files as $file){
				if ($file == $mostRecent['file'])
					ftp_rename($conn, $file, str_replace($settings_LM_stockexport_folder, $settings_LM_stockexport_folder_done, $file));
				else
					ftp_delete($conn, $file);
			}

			$email_to = $email_from = Configuration::get('PS_SHOP_EMAIL'); // email to
			if ($difference_to_report || $errors_to_report){
				$email_content = 'Kære Admin'."\n\n".

					'Hermed lager rapport der omhandler differencer mellem LM og vores lager:'."\n\n".

					'Tidspunkt:'.date('d/m/Y H:i:s')."\n\n".

					'Filens navn:'.$mostRecent['file']."\n\n".

					(($difference_to_report) ? 'Differencer:'."\n".$difference_to_report."\n\n" : '').
					(($errors_to_report) ? 'Fejl:'."\n".$errors_to_report."\n\n" : '').

					'Med venlig hilsen'."\n\n".
					'Det smarte system';

				mb_internal_encoding("UTF-8");
				$headers = 'Mime-Version: 1.0'."\r\n".
							'Content-Type: text/plain;charset=UTF-8'."\r\n".
							'From: '.$email_from."\r\n".
							'Reply-To: '.$email_from."\r\n".
							'X-Mailer: PHP/'.phpversion();
				if (mb_send_mail($email_to, 'Lager differecen rapport fra Dyrefoder.dk '.date('d/m/Y'), $email_content, $headers)) {
					$this->addToLog('AutoProcess::compare_stock_quanties_with_warehouse_LM - difference rapport sendt pr email', 1, null, null, null, true);
				}
			}

			if ($total_value_txt){
				$email_content = 'Kære Admin'."\n\n".

					'Hermed lagerværdi for Dyrefoder.dk ifølge LM Lagerhotel A/S uafhængige lagersystem:'."\n\n".

					'Tidspunkt:'.date('d/m/Y H:i:s')."\n\n".

					'Filens navn:'.$mostRecent['file']."\n\n".

					$total_value_txt."\n\n".

					'Samlet værdi ex moms:'.$total_value_sum_excl_tax."\n".
					'Samlet værdi incl moms:'.$total_value_sum_incl_tax."\n\n".

					'Med venlig hilsen'."\n\n".
					'Det smarte system';

				mb_internal_encoding("UTF-8");
				$headers = 'Mime-Version: 1.0'."\r\n".
							'Content-Type: text/plain;charset=UTF-8'."\r\n".
							'From: '.$email_from."\r\n".
							'Reply-To: '.$email_from."\r\n".
							'X-Mailer: PHP/'.phpversion();
				if (mb_send_mail($email_to, 'Lagerværdi rapport fra Dyrefoder.dk '.date('d/m/Y'), $email_content, $headers)) {
					$this->addToLog('AutoProcess::compare_stock_quanties_with_warehouse_LM - Lagerværdi rapport sendt pr email', 1, null, null, null, true);
				}
			}
		}
		ftp_close($conn);
		$this->addToLog('AutoProcess::compare_stock_quanties_with_warehouse_LM - done', 1, null, null, null, true);
		die('done');
	}

	public function check_stock_quanties_soldout()
	{
		$this->addToLog('AutoProcess::check_stock_quanties_soldout - Report if any products is sold out', 1, null, null, null, true);

		if (!Configuration::get('PS_STOCK_MANAGEMENT'))
			die('advanced stock management turned off');

		if (!($products_query = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT p.id_product, stock.id_product_attribute '.
			'FROM `'._DB_PREFIX_.'product` p '.
			Shop::addSqlAssociation('product', 'p').
			Product::sqlStock('p').
			'WHERE
				p.active
				AND p.id_product IN (
					SELECT DISTINCT(id_product)
					FROM `'._DB_PREFIX_.'product_attribute`)
				AND IFNULL(stock.quantity, 0) <= 0
			OR
				p.active
				AND p.id_product NOT IN (
					SELECT DISTINCT(id_product)
					FROM `'._DB_PREFIX_.'product_attribute`)
				AND IFNULL(stock.quantity, 0) <= 0 '
			//.'GROUP BY p.id_product'
		))) // if no matching orders
			die('none');

		$difference_to_report = false;
		foreach ($products_query as $product){
			$difference_to_report .= $this->fix_product_name(Product::getProductName($product['id_product'], $product['id_product_attribute']))."\n";
		}
		if ($difference_to_report){
			$email_to = $email_from = Configuration::get('PS_SHOP_EMAIL'); // email to
			$email_content = 'Kære Admin'."\n\n".

				'Hermed rapport med udsolgte varer:'."\n\n".

				$difference_to_report."\n\n".

				'Tidspunkt:'.date('d/m/Y H:M:S')."\n\n".

				'Med venlig hilsen'."\n\n".
				'Det smarte system';

			mb_internal_encoding("UTF-8");
			$headers = 'Mime-Version: 1.0'."\r\n".
						'Content-Type: text/plain;charset=UTF-8'."\r\n".
						'From: '.$email_from."\r\n".
						'Reply-To: '.$email_from."\r\n".
						'X-Mailer: PHP/'.phpversion();
			if (mb_send_mail($email_to, 'Rapport med udsolgte varer på Dyrefoder.dk '.date('d/m/Y'), $email_content, $headers)) {
				$this->addToLog('AutoProcess::check_stock_quanties_soldout - Rapport sendt pr email', 1, null, null, null, true);
			}
		}
		die('done');
	}

	/*
	$delivery = new Address($order->id_address_delivery);
	$data = array(
		'{delivery_block_txt}' => $this->_getFormatedAddress($delivery, "\n"),
		'{delivery_block_html}' => $this->_getFormatedAddress($delivery, '<br />', array(
			'firstname'	=> '<span style="font-weight:bold;">%s</span>',
			'lastname'	=> '<span style="font-weight:bold;">%s</span>'
		)),
		'{delivery_company}' => $delivery->company,
		'{delivery_firstname}' => $delivery->firstname,
		'{delivery_lastname}' => $delivery->lastname,
		'{delivery_address1}' => $delivery->address1,
		'{delivery_address2}' => $delivery->address2,
		'{delivery_city}' => $delivery->city,
		'{delivery_postal_code}' => $delivery->postcode,
		'{delivery_country}' => $delivery->country,
		'{delivery_phone}' => ($delivery->phone) ? $delivery->phone : $delivery->phone_mobile,
		'{delivery_other}' => $delivery->other,
		'{order_name}' => $order->getUniqReference(),
		'{date}' => Tools::displayDate(date('Y-m-d H:i:s'), null, 1),
		'{carrier}' => ($virtual_product || !isset($carrier->name)) ? Tools::displayError('No carrier') : $carrier->name,
		'{id_order}' => (int)($order->id),
		'{message}' => Tools::nl2br($msgText)
		'{products}' => $product_list_html,
		'{products_txt}' => $product_list_txt,
	);

	if (Validate::isEmail($this->context->customer->email))
		Mail::Send(
			(int)$order->id_lang,
			'dropship_order',
			sprintf(Mail::l('Dropship order from %s (ID %s)'), 'Dyrefoder.dk', (int)($order->id)),
			Mail::l('', (int)$order->id_lang),
			$data,
			$this->context->customer->email,
			$this->context->customer->firstname.' '.$this->context->customer->lastname,
			null,
			null,
			null,
			null,
			_PS_MAIL_DIR_,
			false,
			(int)$order->id_shop
		);
	*/
}
