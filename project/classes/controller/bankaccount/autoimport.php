<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Bankaccount_Autoimport extends Controller_Template_Crud
{
	public function action_index ()
	{
		$this->template2->title = __('Autoimports');
		$query = DB::select()->order_by('text');
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
	public function action_createjs($account_id, $type, $text)
	{
		$data = array(
			'account_id'      => (int)$account_id,
			'type'            => $type,
			'text'            => $text,
			'amount_max'      => null,
			'amount_min'      => null,
			'time_max'        => null,
			'time_min'        => null,
			'bankaccount_id'  => null,
		);
		if(isset($_POST))
		{
			if(isset($_POST['amount_max']) && $_POST['amount_max'] != '' && is_numeric($_POST['amount_max']))
			{
				$data['amount_max'] = $_POST['amount_max'];
			}
			if(isset($_POST['amount_min']) && $_POST['amount_min'] != '' && is_numeric($_POST['amount_min']))
			{
				$data['amount_min'] = $_POST['amount_min'];
			}
			if(isset($_POST['time_max']) && $_POST['time_max'] != '')
			{
				$data['time_max'] = $_POST['time_max'];
			}
			if(isset($_POST['time_min']) && $_POST['time_min'] != '')
			{
				$data['time_min'] = $_POST['time_min'];
			}
			if(isset($_POST['bankaccount_id']) && $_POST['bankaccount_id'] != '')
			{
				$data['bankaccount_id'] = (int)$_POST['bankaccount_id'];
			}
		}
		try
		{
			$autoimport = Sprig::factory('bankaccount_autoimport', $data)->create();
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
