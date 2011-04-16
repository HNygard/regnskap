<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Css extends Kohana_Controller_Template
{
	public function before()
	{
		$this->template = $this->request->controller().'/'.$this->request->action();
		
		return parent::before();
	}
	
	public function action_regnskap () {}
}
