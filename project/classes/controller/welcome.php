<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Welcome extends Controller {

	public function action_index()
	{
		$this->response->body('hello, world!'.
		
		'<br><br>'.
		html::anchor('index.php/Kontooversiktparser/srbank','Til parser'));
	}

} // End Welcome
