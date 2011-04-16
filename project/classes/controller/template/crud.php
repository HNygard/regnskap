<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Template_Crud extends Controller_Template
{
	public function before()
	{
		if(
			(!isset($this->template) || $this->template == 'template') &&
			(
				$this->request->action() == 'create' ||
				$this->request->action() == 'view' ||
				$this->request->action() == 'edit' ||
				$this->request->action() == 'delete'
			)
		)
		{
			// No template is set, using default
			$this->template = 'crud/'.$this->request->action();
		}
		
		return parent::before();
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
