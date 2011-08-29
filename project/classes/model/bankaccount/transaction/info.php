<?php defined('SYSPATH') or die('No direct script access.');

class Model_Bankaccount_Transaction_Info extends Sprig {
	
	protected function _init()
	{
		$this->_fields += array(
			'id' => new Sprig_Field_Auto(array(
			)),
			'bankaccount_transaction_id' => new Sprig_Field_Integer(array(
			)),
			'key' => new Sprig_Field_Char(array(
				'empty'    => false,
				'null'     => false,
			)),
			'value' => new Sprig_Field_Char(array(
				'empty'    => true,
				'default'  => null,
				'null'     => false,
			)),
		);
	}
	
	public function __toString()
	{
		return $this->key.'='.$this->value;
	}
}
