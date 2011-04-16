<?php defined('SYSPATH') or die('No direct script access.');

class Model_Bankaccount extends Sprig {
	
	protected function _init()
	{
		$this->_fields += array(
			'id' => new Sprig_Field_Auto(array(
			)),
			'num' => new Sprig_Field_Char(array(
			)),
		);
	}
}
