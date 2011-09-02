<?php

class Controller_dbfix extends Controller {

	public function action_dbfix () {



echo 'DBfix';

$b = DB::select('*')
	->from('bankaccount_autoimports')
	->as_object('Model_Bankaccount_Autoimport')->execute();

foreach($b as $a)
{
	echo '<h1>'.$a->id.'</h1>';
	var_dump($a->as_array());
	$arr = array(
			'srbank_type' => $a->type,
			'srbank_description' => $a->text
		);
	var_dump($arr);
	$a->updateInfo($arr);
}

}
}
