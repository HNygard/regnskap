<?php defined('SYSPATH') or die('No direct script access.');

class Model_Bankaccount_Autoimport extends hasinfo {
	
	protected function _init()
	{
		$this->_fields += array(
			'id' => new Sprig_Field_Auto(array(
			)),
			'bankaccount_id' => new Sprig_Field_Integer(array(
				'empty' => true,
			)),
			'account_id' => new Sprig_Field_Integer(array(
			)),
			
			'amount_max' => new Sprig_Field_Float(array(
				'empty' => true,
				'default' => null,
				'null' => true,
			)),
			'amount_min' => new Sprig_Field_Float(array(
				'empty' => true,
				'default' => null,
				'null' => true,
			)),
			
			'time_max' => new Sprig_Field_Timestamp(array(
				'empty' => true,
			)),
			'time_min' => new Sprig_Field_Timestamp(array(
				'empty' => true,
			)),
		);
	}
	
	public function updateInfo($info)
	{
		// Unset info saved directly in object ($this)
		unset($info['id']);
		unset($info['bankaccount_id']);
		unset($info['account_id']);
		unset($info['amount_max']);
		unset($info['amount_min']);
		unset($info['time_max']);
		unset($info['time_min']);
		
		parent::updateInfo($info);
	}
}
