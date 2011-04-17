<?php

echo html::anchor('index.php/transaction/create', __('Create new transaction'));

echo '<ul>';
foreach($transactions as $transaction)
{
	echo '	<li>'.
			'('.date('d.m.Y', $transaction->time).') <b>'.$transaction->amount.':</b> '.
			
			html::anchor(
				'index.php/transaction/transactions/'.$transaction->id,
				__('Transactions')
			).
			', '.
			html::anchor(
				'index.php/transaction/edit/'.$transaction->id,
				__('Edit')
			).
			', '.
			html::anchor(
				'index.php/transaction/delete/'.$transaction->id,
				__('Delete')
			).
		'</li>';
}
echo '</ul>';
