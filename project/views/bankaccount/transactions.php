<?php

if(!isset($bydate))
	$bydate = false;

if(!$bydate)
{
	echo html::anchor('index.php/bankaccount/', __('Back to bank account list')).'<br />';

	if(isset($notimported) && $notimported)
	{
		echo html::anchor('index.php/bankaccount/transactions/'.$bankaccount->id, __('Show all transactions on account'));
	}
	else
	{
		echo html::anchor('index.php/bankaccount/transactionsnotimported/'.$bankaccount->id, __('Show not imported transactions only'));
	}
}

$query = DB::select()->order_by('num');
$accounts = Sprig::factory('account', array())->load($query, FALSE);

$query = DB::select()->order_by('num');
$bankaccounts = Sprig::factory('bankaccount', array())->load($query, FALSE);

echo '<div id="dialog-form" title="'.__('Create autoimport').'">
	<span id="last_transaction_id" style="display: none;"></span>
	<form>
	<fieldset>
		<label for="autoimport_account_id">'.__('Account').'</label><br />
		<select name="autoimport_account_id" id="autoimport_account_id" class="text ui-widget-content ui-corner-all">'.chr(10);
foreach($accounts as $account)
	echo '			<option value="'.$account->id.'">'.$account->name.'</option>'.chr(10);

echo '		</select><br />
		<label for="autoimport_type">'.__('Type').'</label><br />
		<input type="text" name="autoimport_type" id="autoimport_type" value="" class="text ui-widget-content ui-corner-all" /><br />
		<label for="autoimport_text">'.__('Text').'</label><br />
		<input type="text" name="autoimport_text" id="autoimport_text" value="" class="text ui-widget-content ui-corner-all" /><br /><br />
		<label for="autoimport_bankaccount_id">'.__('Only from this bankaccount?').'</label><br />
		<input type="checkbox" name="autoimport_bankaccount_id" id="autoimport_bankaccount_id" value="" class="text ui-widget-content ui-corner-all">
		<select name="autoimport_bankaccount_txt" id="autoimport_bankaccount_txt" class="text ui-widget-content ui-corner-all">'.chr(10);
foreach($bankaccounts as $bankaccount)
	echo '			<option value="'.$bankaccount->id.'">'.$bankaccount->num.' ('.$bankaccount->type.')</option>'.chr(10);

echo '		</select><br />
		
		<label for="autoimport_bankaccount_id">'.__('Max amount').'</label><br />
		<input type="text" name="autoimport_amount_max" id="autoimport_amount_max" value="" class="text ui-widget-content ui-corner-all" /><br />
		<label for="autoimport_bankaccount_id">'.__('Min amount').'</label><br />
		<input type="text" name="autoimport_amount_min" id="autoimport_amount_min" value="" class="text ui-widget-content ui-corner-all" /><br />
		<label for="autoimport_bankaccount_id">'.__('Max time').'</label><br />
		<input type="text" name="autoimport_time_max" id="autoimport_time_max" value="" class="text ui-widget-content ui-corner-all" /><br />
		<label for="autoimport_bankaccount_id">'.__('Min time').'</label><br />
		<input type="text" name="autoimport_time_min" id="autoimport_time_min" value="" class="text ui-widget-content ui-corner-all" /><br />
		<button id="autoimport_copy">'.__('Copy from current').'</button	><br />
	</fieldset>
	</form>
	<span style="display: none;" id="autoimport_copy_amount_max"></span>
	<span style="display: none;" id="autoimport_copy_amount_min"></span>
	<span style="display: none;" id="autoimport_copy_time_max"></span>
	<span style="display: none;" id="autoimport_copy_time_min"></span>
</div>'.chr(10).chr(10);

if($order_desc == 'desc')
{
	$order_desc2 = 'asc';
}
else
{
	$order_desc2 = 'desc';
}
function order_by_link($should_be, $link, $order_by, $order_desc, $order_desc2)
{
	if($order_by == $should_be)
		return $link.$should_be.'/'.$order_desc2;
	else
		return $link.$should_be.'/'.$order_desc;
}

if(!$bydate)
{
	$link = 'index.php/'.
		Request::current()->controller().'/'.
		Request::current()->action().'/'.
		$bankaccount->id.'/';
}
else
{
	$link = 'index.php/'.
		Request::current()->controller().'/'.
		Request::current()->action().'/'.
		$year.'/'.
		$month.'/';// TODO: not only for months
}

echo '<table>'.chr(10);
echo
	'	<tr>'.chr(10).
	'		<th>'.HTML::anchor(order_by_link('id', $link, $order_by, $order_desc, $order_desc2), __('Id')).'</th>'.chr(10).
	'		<th>'.HTML::anchor(order_by_link('payment_date', $link, $order_by, $order_desc, $order_desc2), __('Payed date')).'</th>'.chr(10).
	'		<th>'.HTML::anchor(order_by_link('amount', $link, $order_by, $order_desc, $order_desc2), __('Amount')).'</th>'.chr(10).
	'		<th>'.HTML::anchor(order_by_link('description', $link, $order_by, $order_desc, $order_desc2), __('Description')).'</th>'.chr(10).
	'		<th>'.__('Intrest date').'</th>'.chr(10).
	'		<th style="border: solid gray 1px;">'.__('Type').'</th>'.chr(10).
	'		<th style="border: solid gray 1px;">'.__('Type PDF').'</th>'.chr(10).
	'		<th style="border: solid gray 1px;">'.__('Type CSV').'</th>'.chr(10).
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
		'		<td style="display: none;" class="amount">'.$bankaccount_transaction->amount.'</td>'.chr(10).
		'		<td>'.$bankaccount_transaction->description.'</td>'.chr(10).
		'		<td>'.date('d.m.Y', $bankaccount_transaction->intrest_date).'</td>'.chr(10).
		'		<td style="border: solid gray 1px;" class="type">'.$bankaccount_transaction->srbank_type.'</td>'.chr(10).
		'		<td style="border: solid gray 1px;" class="type">'.$bankaccount_transaction->type_pdf.'</td>'.chr(10).
		'		<td style="border: solid gray 1px;" class="type">'.$bankaccount_transaction->type_csv.'</td>'.chr(10).
		'		<td style="border: solid gray 1px;" class="time">';
	if(!is_null($bankaccount_transaction->srbank_date))
		echo date('d.m.Y', $bankaccount_transaction->srbank_date);
	else
		echo date('d.m.Y', $bankaccount_transaction->payment_date);
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
