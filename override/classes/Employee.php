<?php
/*
__to_be_replaced_with_disclaimer__
*/

class Employee extends EmployeeCore
{
	public static function getEmployees()
	{
		return Db::getInstance()->executeS('
			SELECT `id_employee`, `firstname`, `lastname`, `email`
			FROM `'._DB_PREFIX_.'employee`
			WHERE `active` = 1
			ORDER BY `lastname` ASC
		');
	}
}
