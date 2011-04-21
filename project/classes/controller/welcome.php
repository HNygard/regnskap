<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Welcome extends Controller_Template
{
	public function action_index()
	{
		$this->action_show();
	}
	
	public function action_show()
	{	
		$this->template2->title = __('Overview');
		$this->template->transactions_query = DB::select()->from('transactions')->execute();
	}
}
