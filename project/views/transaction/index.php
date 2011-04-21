<?php

echo html::anchor('index.php/transaction/create', __('Create new transaction'));

echo '<table class="prettytable">';
foreach($transactions as $transaction)
{
	echo '	<tr>'.
			'<td>'.date('d.m.Y', $transaction->time).')</td>'.
			'<td style="text-align: right"><b>'.HTML::money($transaction->amount).'</b></td>'.
			'<td>'.$transaction->description.'</td>'.
			'<td>'.
			html::anchor(
				'index.php/transaction/edit/'.$transaction->id,
				__('Edit')
			).
			', '.
			html::anchor(
				'index.php/transaction/delete/'.$transaction->id,
				__('Delete')
			).
			'</td>'.
		'</tr>'.chr(10);
}
echo '</table>';
