<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Bankaccount_Autoimport extends Controller_Template_Crud
{
	public function action_index ()
	{
		$this->template2->title = __('Autoimports');
		$query = DB::select();
		$this->template->bankaccount_autoimports = Sprig::factory('bankaccount_autoimport', array())->load($query, FALSE);
	}
	
	public function before()
	{
		if($this->request->action() == 'createjs')
		{
			$this->use_template2 = false;
			$this->template = 'bankaccount/autoimport/createjs';
		}
		
		return parent::before();
	}
	public function action_createjs($account_id)
	{
		$data = array(
			'account_id'      => (int)$account_id,
			'amount_max'      => null,
			'amount_min'      => null,
			'time_max'        => null,
			'time_min'        => null,
			'bankaccount_id'  => null,
		);
		if(isset($_POST))
		{
			if(isset($_POST['autoimport_amount_max']) && $_POST['autoimport_amount_max'] != '' && 
				is_numeric($_POST['autoimport_amount_max']))
			{
				$data['amount_max'] = $_POST['autoimport_amount_max'];
			}
			if(isset($_POST['autoimport_amount_min']) && $_POST['autoimport_amount_min'] != '' && 
				is_numeric($_POST['autoimport_amount_min']))
			{
				$data['amount_min'] = $_POST['autoimport_amount_min'];
			}
			if(isset($_POST['autoimport_time_max']) && $_POST['autoimport_time_max'] != '')
			{
				$data['time_max'] = $_POST['autoimport_time_max'];
			}
			if(isset($_POST['autoimport_time_min']) && $_POST['autoimport_time_min'] != '')
			{
				$data['time_min'] = $_POST['autoimport_time_min'];
			}
			if(
				isset($_POST['autoimport_bankaccount_checkbox']) && $_POST['autoimport_bankaccount_checkbox'] == '1' &&
				isset($_POST['autoimport_bankaccount_id']) && is_numeric($_POST['autoimport_bankaccount_id'])
			)
			{
				$data['bankaccount_id'] = (int)$_POST['autoimport_bankaccount_id'];
			}
			if(isset($_POST['autoimport_dynfields']) && is_array($_POST['autoimport_dynfields']))
			{
				foreach($_POST['autoimport_dynfields'] as $key => $value)
				{
					$data[$key] = $value;
				}
			}
		}
		try
		{
			$autoimport = Sprig::factory('bankaccount_autoimport', $data)
				->create()
				->updateInfo($data);
		}
		catch (Validation_Exception $e)
		{
			foreach($e->array->errors() as $key=> $errors)
			{
				foreach($errors as $error)
				{
					if(is_array($error))
						echo $key.': '.implode(',', $error).chr(10);
					else
						echo $key.': '.$error.chr(10);
				}
			}
			exit;
		}
	}
}
