<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Transaction extends Controller_Template_Crud
{
	public function action_index ()
	{
		$this->template2->title = __('Transactions');
		$query = DB::select()->order_by('time');
		$this->template->transactions = Sprig::factory('transaction', array())->load($query, FALSE);
	}
	
	public function action_showbydate ($year, $month = null, $day = null)
	{
		$this->template2->title = __('Transactions');
		$query = $this->querybuilder ($year, $month, $day);
		$this->template->transactions = Sprig::factory('transaction', array())->load($query, FALSE);
	}
	
	public function action_showaccountbydate ($account_id, $year, $month = null, $day = null)
	{
		$this->template2->title = __('Transactions');
		$query = $this->querybuilder ($year, $month, $day, $account_id);
		$this->template->transactions = Sprig::factory('transaction', array())->load($query, FALSE);
	}

	protected function querybuilder ($year, $month, $day, $account_id = null)
	{
		$query = DB::select()->order_by('time');
		if(!is_null($day) && is_numeric($day) && is_numeric($month) && is_numeric($year))
		{
			// Day
			$query->where('time', '>=', mktime(0,0,0,$month,$day,$year));
			$query->where('time', '<', mktime(0,0,0,$month,$day+1,$year));
		}
		elseif(!is_null($month) && is_numeric($month) && is_numeric($year))
		{
			// Month
			$query->where('time', '>=', mktime(0,0,0,$month,01,$year));
			$query->where('time', '<', mktime(0,0,0,$month+1,01,$year));
		}
		elseif(is_numeric($year))
		{
			// Year
			$query->where('time', '>=', mktime(0,0,0,01,01,$year));
			$query->where('time', '<', mktime(0,0,0,01,01,$year+1));
		}
		if(!is_null($account_id) && is_numeric($account_id))
		{
			// Account
			$query->where('account_id', '=', $account_id);
		}
		return $query;
	}
}
