<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Bankaccount_Autoimport extends Controller_Template_Crud
{
	public function action_index ()
	{
		$this->template2->title = __('Autoimports');
		$query = DB::select()->order_by('text');
		$this->template->bankaccount_autoimports = Sprig::factory('bankaccount_autoimport', array())->load($query, FALSE);
	}
}
