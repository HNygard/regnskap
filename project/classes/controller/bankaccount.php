<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Bankaccount extends Controller_Template
{
	public function action_index ()
	{
		$this->template2->title = __('Bank accounts');
		$query = DB::select()->order_by('num', 'DESC');
		$this->template->bankaccounts = Sprig::factory('bankaccount', array())->load($query, FALSE);
	}
	
	public function action_transactions ($bankaccount_id)
	{
		$bankaccount = Sprig::factory('bankaccount', array('id' => $bankaccount_id))->loadOrThrowException();
		$this->template2->title = __('Transactions on bank account').' '.$bankaccount->num;
		$query = DB::select()->order_by('payment_date', 'DESC');
		$this->template->bankaccount_transactions = Sprig::factory('bankaccount_transaction', array())->load($query, FALSE);
	}
}
