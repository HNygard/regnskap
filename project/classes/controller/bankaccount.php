<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Bankaccount extends Controller_Template
{
	public function action_index ()
	{
		$this->template2->title = __('Bank accounts');
		$query = DB::select()->order_by('num');
		$this->template->bankaccounts = Sprig::factory('bankaccount', array())->load($query, FALSE);
	}
	
	public function action_transactions ($bankaccount_id, $order_by = 'date', $order_desc = 'desc')
	{
		if(
			$order_by != 'date' && 
			$order_by != 'amount' && 
			$order_by != 'id')
		{
			$order_by = 'date';
		}
		if($order_desc != 'desc' && $order_desc != 'asc')
		{
			$order_desc = 'desc';
		}
		$this->template->order_by    = $order_by;
		$this->template->order_desc  = $order_desc;
		
		$bankaccount = Sprig::factory('bankaccount', array('id' => $bankaccount_id))->loadOrThrowException();
		$this->template2->title = __('Transactions on bank account').' '.$bankaccount->num;
		$query = DB::select()->order_by($order_by, $order_desc);
		$query->where('bankaccount_id', '=', $bankaccount->id);
		$this->template->bankaccount_transactions = Sprig::factory('bankaccount_transaction', array())->load($query, FALSE);
		
		$this->template->bankaccount = $bankaccount;
	}
	
	public function action_transactions_bydate ($bankaccount_id, $from, $to, $order_by = 'date', $order_desc = 'desc')
	{
		$from  = (int)$from;
		$to    = (int)$to;
		
		if(
			$order_by != 'date' && 
			$order_by != 'amount' && 
			$order_by != 'id')
		{
			$order_by = 'date';
		}
		if($order_desc != 'desc' && $order_desc != 'asc')
		{
			$order_desc = 'desc';
		}
		$this->template->order_by    = $order_by;
		$this->template->order_desc  = $order_desc;
		
		$bankaccount = Sprig::factory('bankaccount', array('id' => $bankaccount_id))->loadOrThrowException();
		$this->template2->title = __('Transactions on bank account').' '.$bankaccount->num;
		$query = DB::select()->order_by($order_by, $order_desc);
		$query->where('bankaccount_id', '=', $bankaccount->id);
		$query->where('date', '>=', $from);
		$query->where('date', '<',  $to);
		$this->template->bankaccount_transactions = Sprig::factory('bankaccount_transaction', array())->load($query, FALSE);
		
		$this->template->bankaccount  = $bankaccount;
		$this->template->from         = $from;
		$this->template->to           = $to;
	}
	
	public function action_transactionsnotimported ($bankaccount_id, $order_by = 'payment_date', $order_desc = 'desc')
	{
		if(
			$order_by != 'date' && 
			$order_by != 'amount' && 
			$order_by != 'id')
		{
			$order_by = 'date';
		}
		if($order_desc != 'desc' && $order_desc != 'asc')
		{
			$order_desc = 'desc';
		}
		$this->template->order_by    = $order_by;
		$this->template->order_desc  = $order_desc;
		
		$bankaccount = Sprig::factory('bankaccount', array('id' => $bankaccount_id))->loadOrThrowException();
		$this->template2->title = __('Not imported transactions on bank account').' '.$bankaccount->num;
		$query = DB::select()->order_by($order_by, $order_desc);
		$query->where('imported', '=', false);
		$query->where('bankaccount_id', '=', $bankaccount->id);
		$this->template->bankaccount_transactions = Sprig::factory('bankaccount_transaction', array())->load($query, FALSE);
		
		$this->template->bankaccount = $bankaccount;
	}
	
	public function action_transactionsnotimported_bymonth ($year, $month, $order_by = 'date', $order_desc = 'desc')
	{
		$year = (int)$year;
		$month = (int)$month;
		$this->template->year   = $year;
		$this->template->month  = $month;
		
		if(
			$order_by != 'date' && 
			$order_by != 'amount' && 
			$order_by != 'id')
		{
			$order_by = 'date';
		}
		if($order_desc != 'desc' && $order_desc != 'asc')
		{
			$order_desc = 'desc';
		}
		$this->template->order_by    = $order_by;
		$this->template->order_desc  = $order_desc;
		
		$this->template2->title = __('Not imported transactions on all bank accounts').' '.$month.'.'.$year;
		$query = DB::select()->order_by($order_by, $order_desc);
		$query->where('imported', '=', false);
		$query->where('date', '>=', mktime(0,0,0,$month,01,$year));
		$query->where('date', '<', mktime(0,0,0,$month+1,01,$year));
		$this->template->bankaccount_transactions = Sprig::factory('bankaccount_transaction', array())->load($query, FALSE);
		
		$query = DB::select()->order_by('num');
		$this->template->accounts = Sprig::factory('account', array())->load($query, FALSE);
	}
	
	public function action_missingimports ($bankaccount_id)
	{
		$bankaccount = Sprig::factory('bankaccount', array('id' => $bankaccount_id))->loadOrThrowException();
		$this->template2->title = __('Missing imports on bank account').' '.$bankaccount->num;
		
		$query = DB::select()->order_by('from');
		$query->where('bankaccount_id', '=', $bankaccount->id);
		$this->template->bankaccount_importfiles = Sprig::factory('bankaccount_importfile', array())->load($query, FALSE);
		$this->template->bankaccount_id = $bankaccount_id;
	}
	
	public function action_autoimport ($bankaccount_id)
	{
		$bankaccount = Sprig::factory('bankaccount', array('id' => $bankaccount_id))->loadOrThrowException();
		$this->template2->title = __('Autoimport transactions on bank account').' '.$bankaccount->num;
		$query = DB::select()
			->order_by('date', 'DESC')
			->where('imported', '=', false);
		$query->where('bankaccount_id', '=', $bankaccount->id);
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
