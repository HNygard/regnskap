<?php defined('SYSPATH') or die('No direct script access.');

class Model_Account extends Sprig {
	
	protected function _init()
	{
		$this->_fields += array(
			'id' => new Sprig_Field_Auto(array(
			)),
			'num' => new Sprig_Field_Integer(array(
			)),
			'name' => new Sprig_Field_Char(array(
			)),
			'sum_from' => new Sprig_Field_Integer(array(
				'empty' => true,
			)),
		);
	}
}
