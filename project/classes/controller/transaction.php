<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Transaction extends Controller_Template_Crud
{
	public function action_index ()
	{
		$this->template2->title = __('Transactions');
		$query = DB::select()->order_by('time');
		$this->template->transactions = Sprig::factory('transaction', array())->load($query, FALSE);
	}
}
