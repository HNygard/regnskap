<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Js extends Kohana_Controller_Template
{
	public function before()
	{
		$this->template = $this->request->controller().'/'.$this->request->action();
		
		return parent::before();
	}
	
	public function action_transactionfiles () {}
	public function action_banktransactions () {}
	public function action_jqueryblockUIjs231() {}
}
