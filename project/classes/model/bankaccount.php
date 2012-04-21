<?php defined('SYSPATH') or die('No direct script access.');

class Model_Bankaccount extends Sprig {
	
	protected function _init()
	{
		$this->_fields += array(
			'id' => new Sprig_Field_Auto(array(
			)),
			'num' => new Sprig_Field_Char(array(
			)),
			'name' => new Sprig_Field_Char(array(
			)),
			'type' => new Sprig_Field_Char(array(
			)),
		);
	}
	
	public function getBalance() {
		foreach(DB::select(array('SUM("amount")', 'balance'))
			->from('bankaccount_transactions')
			->where('bankaccount_id', '=', $this->id)
			->execute() as $row) {
			return $row['balance'];
		}
	}
}
