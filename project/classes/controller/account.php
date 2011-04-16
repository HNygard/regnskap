<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Account extends Controller_Template
{
	public function action_index ()
	{
		$this->template2->title = __('Accounts');
		$query = DB::select()->order_by('num');
		$this->template->accounts = Sprig::factory('account', array())->load($query, FALSE);
	}
	
	public function action_transactions ($account_id)
	{
		$account = Sprig::factory('account', array('id' => $account_id))->loadOrThrowException();
		$this->template2->title = __('Transactions on account').' '.$account->name;
		$query = DB::select()->order_by('time');
		$this->template->transactions = Sprig::factory('transaction', array())->load($query, FALSE);
	}
	
	public function action_create ()
	{
		$this->action_edit (-1);
	}
	
	public function action_edit ($id)
	{
		if($id == -1)
		{
			$this->template2->title = __('Create').' '.__($this->request->controller());
			$object = Sprig::factory($this->request->controller(), array());
		}
		else
		{
			$this->template2->title = __('Edit').' '.__($this->request->controller());
			$object = Sprig::factory($this->request->controller(), 
				array('id' => $id))->loadOrThrowException();
		}

		if($_POST)
		{
			$object->valuesFromHtmlForm($_POST);
			if($id == -1)
			{
				$object->create();
			}
			else
			{
				$object->update();
			}
			$this->request->redirect($this->request->controller());
		}
		
		$this->template->object = $object;
	}
	
	public function action_delete ($id, $confirm = false)
	{
		$object = Sprig::factory($this->request->controller(), 
			array('id' => $id))->loadOrThrowException();
		
		// TODO: self checking method on Sprig-models
		
		if($confirm == 'true')
		{
			$object->delete();
			$this->request->redirect($this->request->controller());
		}
		
		$this->template2->title = __('Delete').' '.__($this->request->controller());
	}
}
