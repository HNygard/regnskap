<?php

echo html::anchor('index.php/bankaccount/', __('Back to bank account list'));

echo '<table>'.chr(10);
echo
	'	<tr>'.chr(10).
	'		<th>'.__('Id').'</th>'.chr(10).
	'		<th>'.__('Payed date').'</th>'.chr(10).
	'		<th>'.__('Amount').'</th>'.chr(10).
	'		<th>'.__('Description').'</th>'.chr(10).
	'	</tr>'.chr(10);
foreach($transactions as $transaction)
{
	echo
		'	<tr>'.chr(10).
		'		<td>'.$transaction->id.'</td>'.chr(10).
		'		<td>'.date('d.m.Y', $transaction->time).'</td>'.chr(10).
		'		<td style="text-align: right;">'.html::money($transaction->amount).'</td>'.chr(10).
		'		<td>'.$transaction->description.'</td>'.chr(10).
		'	</tr>'.chr(10);
}
echo '</table>'.chr(10);
