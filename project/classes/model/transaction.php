<?php defined('SYSPATH') or die('No direct script access.');

class Model_Transaction extends Sprig {
	
	protected function _init()
	{
		$this->_fields += array(
			'id' => new Sprig_Field_Auto(array(
				'editable'        => false,
			)),
			'time' => new Sprig_Field_Timestamp(array(
			)),
			'account' => new Sprig_Field_BelongsTo(array(
				'editable'        => false,
				'model'           => 'Account',
			)),
			'account_id' => new Sprig_Field_Integer(array(
			)),
			'amount' => new Sprig_Field_Float(array(
			)),
			'description' => new Sprig_Field_Char(array(
			)),
			'created' => new Sprig_Field_Timestamp(array(
				'auto_now_create' => true,
				'editable'        => false,
			)),
			
			'bankaccount_transaction' => new Sprig_Field_BelongsTo(array(
				'editable'        => false,
				'model'           => 'Bankaccount_Transaction',
			)),
			'bankaccount_transaction_id' => new Sprig_Field_Integer(array(
				'editable'        => false,
			)),
			'imported_automatically' => new Sprig_Field_Boolean(array(
				'editable'        => false,
			)),
		);
	}
}
