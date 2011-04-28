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
		try
		{
			$autoimport = Sprig::factory('bankaccount_autoimport', array(
					'account_id' => $account_id,
					'type' => $type,
					'text' => $text,
				))->create();
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
