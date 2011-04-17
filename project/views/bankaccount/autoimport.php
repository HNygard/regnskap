<?php

echo '<table>'.chr(10);
echo
	'	<tr>'.chr(10).
	'		<th>'.__('Id').'</th>'.chr(10).
	'		<th>'.__('Payed date').'</th>'.chr(10).
	'		<th>'.__('Amount').'</th>'.chr(10).
	'		<th>'.__('Description').'</th>'.chr(10).
	'		<th>'.__('Intrest date').'</th>'.chr(10).
	'		<th style="border: solid gray 1px;">'.__('Type').'</th>'.chr(10).
	'		<th style="border: solid gray 1px;">'.__('Date').'</th>'.chr(10).
	'		<th style="border: solid gray 1px;">'.__('Text').'</th>'.chr(10).
	'	</tr>'.chr(10);
foreach($bankaccount_transactions as $bankaccount_transaction)
{
	$bankaccount_transaction->analyse_srbank();
	echo
		'	<tr id="transaction_'.$bankaccount_transaction->id.'">'.chr(10).
		'		<td>'.$bankaccount_transaction->id.'</td>'.chr(10).
		'		<td>'.date('d.m.Y', $bankaccount_transaction->payment_date).'</td>'.chr(10).
		'		<td style="text-align: right;">'.html::money($bankaccount_transaction->amount).'</td>'.chr(10).
		'		<td>'.$bankaccount_transaction->description.'</td>'.chr(10).
		'		<td>'.date('d.m.Y', $bankaccount_transaction->intrest_date).'</td>'.chr(10).
		'		<td style="border: solid gray 1px;" class="type">'.$bankaccount_transaction->srbank_type.'</td>'.chr(10).
		'		<td style="border: solid gray 1px;">'.$bankaccount_transaction->srbank_date.'</td>'.chr(10).
		'		<td style="border: solid gray 1px;" class="text">'.$bankaccount_transaction->srbank_text.'</td>'.chr(10).
		'		<td class="button">';
	if($bankaccount_transaction->canAutoimport())
	{
		echo '<img src="'.URL::base().'/images/tick.png" class="canAutoimport">';
		$account = Sprig::factory('account', 
			array('id' => $bankaccount_transaction->autoimport_account_id))->load();
		if($account->loaded())
			echo ' - '.$account->name;
	}
	else
	{
		echo '<button class="canNotAutoimport" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only ui-state-hover" role="button" aria-disabled="false"><span class="ui-button-text">+</span></button>';
	}
	echo	'</td>'.chr(10).
		'	</tr>'.chr(10);
}
echo '</table>'.chr(10);
