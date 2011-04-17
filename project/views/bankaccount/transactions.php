<?php

echo '<div id="dialog-form" title="'.__('Create autoimport').'">
	<span id="last_transaction_id" style="display: none;"></span>
	<form>
	<fieldset>
		<label for="autoimport_account_id">'.__('Account').'</label><br />
		<select name="autoimport_account_id" id="autoimport_account_id" class="text ui-widget-content ui-corner-all">';
foreach($accounts as $account)
	echo '			<option value="'.$account->id.'">'.$account->name.'</option>'.chr(10);

echo '		</select><br />
		<label for="autoimport_type">'.__('Type').'</label><br />
		<input type="text" name="autoimport_type" id="autoimport_type" value="" class="text ui-widget-content ui-corner-all" /><br />
		<label for="autoimport_text">'.__('Text').'</label><br />
		<input type="text" name="autoimport_text" id="autoimport_text" value="" class="text ui-widget-content ui-corner-all" />
	</fieldset>
	</form>
</div>'.chr(10).chr(10);

echo html::anchor('index.php/bankaccount/', __('Back to bank account list'));

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
		'		<td style="border: solid gray 1px;">';
	if(!is_null($bankaccount_transaction->srbank_date))
		echo date('d.m.Y', $bankaccount_transaction->srbank_date);
	else
		echo '&nbsp;';
	echo '</td>'.chr(10).
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
