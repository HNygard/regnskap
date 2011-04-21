<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Bankaccount extends Controller_Template
{
	public function action_index ()
	{
		$this->template2->title = __('Bank accounts');
		$query = DB::select()->order_by('num');
		$this->template->bankaccounts = Sprig::factory('bankaccount', array())->load($query, FALSE);
	}
	
	public function action_transactions ($bankaccount_id)
	{
		$bankaccount = Sprig::factory('bankaccount', array('id' => $bankaccount_id))->loadOrThrowException();
		$this->template2->title = __('Transactions on bank account').' '.$bankaccount->num;
		$query = DB::select()->order_by('payment_date', 'DESC');
		$this->template->bankaccount_transactions = Sprig::factory('bankaccount_transaction', array())->load($query, FALSE);
		
		$query = DB::select()->order_by('num');
		$this->template->accounts = Sprig::factory('account', array())->load($query, FALSE);
		
		$this->template->bankaccount = $bankaccount;
	}
	
	public function action_transactionsnotimported ($bankaccount_id)
	{
		$bankaccount = Sprig::factory('bankaccount', array('id' => $bankaccount_id))->loadOrThrowException();
		$this->template2->title = __('Not imported transactions on bank account').' '.$bankaccount->num;
		$query = DB::select()->order_by('payment_date', 'DESC');
		$query->where('imported', '=', false);
		$this->template->bankaccount_transactions = Sprig::factory('bankaccount_transaction', array())->load($query, FALSE);
		
		$query = DB::select()->order_by('num');
		$this->template->accounts = Sprig::factory('account', array())->load($query, FALSE);
		
		$this->template->bankaccount = $bankaccount;
	}
	
	public function action_missingimports ($bankaccount_id)
	{
		$bankaccount = Sprig::factory('bankaccount', array('id' => $bankaccount_id))->loadOrThrowException();
		$this->template2->title = __('Missing imports on bank account').' '.$bankaccount->num;
		
		$query = DB::select()->order_by('from');
		$this->template->bankaccount_importfiles = Sprig::factory('bankaccount_importfile', array())->load($query, FALSE);
	}
	
	public function action_autoimport ($bankaccount_id)
	{
		$bankaccount = Sprig::factory('bankaccount', array('id' => $bankaccount_id))->loadOrThrowException();
		$this->template2->title = __('Autoimport transactions on bank account').' '.$bankaccount->num;
		$query = DB::select()
			->order_by('payment_date', 'DESC')
			->where('imported', '=', false);
		$this->template->bankaccount_transactions = Sprig::factory('bankaccount_transaction', array())->load($query, FALSE);
		
		echo '<h1>'.$bankaccount->num.'</h1>';
		foreach($this->template->bankaccount_transactions as $bankaccount_transaction)
		{
			echo $bankaccount_transaction->id.' - ';
			if($bankaccount_transaction->autoimport())
				echo 'ok';
			else
				echo 'failed';
			echo '<br>';
		}
		exit;
	}
	
}
