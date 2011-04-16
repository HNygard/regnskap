<?php defined('SYSPATH') or die('No direct script access.');

class Model_Bankaccount_Transaction extends Sprig {
	
	protected function _init()
	{
		$this->_fields += array(
			'id' => new Sprig_Field_Auto(array(
			)),
			'bankaccount_id' => new Sprig_Field_Integer(array(
			)),
			'payment_date' => new Sprig_Field_Timestamp(array(
			)),
			'intrest_date' => new Sprig_Field_Timestamp(array(
			)),
			'description' => new Sprig_Field_Char(array(
			)),
			'amount' => new Sprig_Field_Float(array(
			)),
		);
	}
	
	public function __toString()
	{
		return 'transaction_'.date('Y-m-d', $this->payment_date).': '.$this->amount;
	}
}
