<?php defined('SYSPATH') or die('No direct script access.');

class Model_Bankaccount_Autoimport extends Sprig {
	
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
			'type' => new Sprig_Field_Char(array(
			)),
			'text' => new Sprig_Field_Char(array(
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
}
