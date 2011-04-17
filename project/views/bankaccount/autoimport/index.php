<?php

echo html::anchor('index.php/bankaccount_autoimport/create', __('Create new autoimport'));

echo '<ul>';
foreach($bankaccount_autoimports as $bankaccount_autoimport)
{
	$account = Sprig::factory('account', 
		array('id' => $bankaccount_autoimport->account_id))->load();
	echo '	<li>'.
			'<b>'.$bankaccount_autoimport->id.':</b> '.
			
			html::anchor(
				'index.php/bankaccount_autoimport/edit/'.$bankaccount_autoimport->id,
				__('Edit')
			).
			', '.
			html::anchor(
				'index.php/bankaccount_autoimport/delete/'.$bankaccount_autoimport->id,
				__('Delete')
			).
			' - ';
	if($account->loaded())
		echo '<b>'.$account->name.':</b> ';
	echo
			$bankaccount_autoimport->type.' - '.
			$bankaccount_autoimport->text.
		'</li>';
}
echo '</ul>';
