<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Account extends Controller_Template_Crud
{
	public function action_index ()
	{
		$this->template2->title = __('Accounts');
		$query = DB::select()->order_by('num')->order_by('name');
		$this->template->accounts = Sprig::factory('account', array())->load($query, FALSE);
	}
	
	public function action_transactions ($account_id)
	{
		$account = Sprig::factory('account', array('id' => $account_id))->loadOrThrowException();
		$this->template2->title = __('Transactions on account').' '.$account->name;
		$query = DB::select()->order_by('time');
		$query->where('account_id', '=', $account->id);
		$this->template->transactions = Sprig::factory('transaction', array())->load($query, FALSE);
	}
}
