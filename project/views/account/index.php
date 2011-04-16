<?php

echo html::anchor('index.php/account/create', __('Create new account'));

echo '<ul>';
foreach($accounts as $account)
{
	echo '	<li>'.
			'('.$account->num.') <b>'.$account->name.':</b> '.
			
			html::anchor(
				'index.php/account/transactions/'.$account->id,
				__('Transactions')
			).
			', '.
			html::anchor(
				'index.php/account/edit/'.$account->id,
				__('Edit')
			).
		'</li>';
}
echo '</ul>';
