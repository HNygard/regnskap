<?php

if(!isset($bymonth))
	$bymonth = false;
if(!isset($bydate))
	$bydate = false;

if(!$bymonth)
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
	<form id="autoimport_creater">
	<fieldset><br />
		<select name="autoimport_account_id" id="autoimport_account_id" class="text ui-widget-content ui-corner-all">'.chr(10);
foreach($accounts as $account)
	echo '			<option value="'.$account->id.'">'.$account->name.'</option>'.chr(10);

echo '		</select>
		<label for="autoimport_account_id">'.__('Account').'</label><br />
		
		<div id="autoimport_dynfields">
		</div>
		
		<input type="checkbox" name="autoimport_bankaccount_checkbox" id="autoimport_bankaccount_id" value="1" class="text ui-widget-content ui-corner-all">
		<select name="autoimport_bankaccount_id" id="autoimport_bankaccount_txt" class="text ui-widget-content ui-corner-all">'.chr(10);
foreach($bankaccounts as $bankaccount_tmp)
	echo '			<option value="'.$bankaccount_tmp->id.'">'.$bankaccount_tmp->num.' ('.$bankaccount_tmp->type.')</option>'.chr(10);

echo '		</select>
		<label for="autoimport_bankaccount_checkbox">'.__('Only from this bankaccount?').'</label><br />
		
		<input type="text" name="autoimport_amount_max" id="autoimport_amount_max" value="" class="text ui-widget-content ui-corner-all" />
		<label for="autoimport_amount_max">'.__('Max amount').'</label><br />
		<input type="text" name="autoimport_amount_min" id="autoimport_amount_min" value="" class="text ui-widget-content ui-corner-all" />
		<label for="autoimport_amount_min">'.__('Min amount').'</label><br />
		<input type="text" name="autoimport_time_max" id="autoimport_time_max" value="" class="text ui-widget-content ui-corner-all" />
		<label for="autoimport_time_max">'.__('Max time').'</label><br />
		<input type="text" name="autoimport_time_min" id="autoimport_time_min" value="" class="text ui-widget-content ui-corner-all" />
		<label for="autoimport_time_min">'.__('Min time').'</label><br />
		<button id="autoimport_copy">'.__('Copy from current').'</button><br />
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

if($bydate)
{
	$link = 'index.php/'.
		Request::current()->controller().'/'.
		Request::current()->action().'/'.
		$bankaccount->id.'/'.
		$from.'/'.
		$to.'/';
}
elseif($bymonth)
{
	$link = 'index.php/'.
		Request::current()->controller().'/'.
		Request::current()->action().'/'.
		$year.'/'.
		$month.'/';// TODO: not only for months
}
else
{
	$link = 'index.php/'.
		Request::current()->controller().'/'.
		Request::current()->action().'/'.
		$bankaccount->id.'/';
}

echo '<img src="/regnskap/regnskap/webroot/images/tick.png" id="tickloader">';

$sum_after = 0;
echo '<table>'.chr(10);
echo
	'	<tr>'.chr(10).
	'		<th>'.HTML::anchor(order_by_link('id', $link, $order_by, $order_desc, $order_desc2), __('Id')).'</th>'.chr(10).
	'		<th>'.HTML::anchor(order_by_link('date', $link, $order_by, $order_desc, $order_desc2), __('Date')).'</th>'.chr(10).
	'		<th>'.HTML::anchor(order_by_link('amount', $link, $order_by, $order_desc, $order_desc2), __('Amount')).'</th>'.chr(10).
	'		<th>'.__('Data').'</th>'.chr(10).
	'		<th>'.__('Autoimport').'</th>'.chr(10).
	'		<th>'.__('After').'</th>'.chr(10).
	'		<th>'.__('Imported').'</th>'.chr(10).
	'	</tr>'.chr(10);
foreach($bankaccount_transactions as $bankaccount_transaction)
{
	$sum_after += $bankaccount_transaction->amount;
	echo
		'	<tr id="transaction_'.$bankaccount_transaction->id.'" class="transaction">'.chr(10).
		'		<td>'.$bankaccount_transaction->id.'</td>'.chr(10).
		'		<td class="time">'.date('d.m.Y', $bankaccount_transaction->date).'</td>'.chr(10).
		'		<td style="text-align: right;">'.html::money($bankaccount_transaction->amount).'</td>'.chr(10).
		'		<td style="display: none;" class="amount">'.$bankaccount_transaction->amount.'</td>'.chr(10).
		'		<td style="border: solid gray 1px;" class="type">';
	$tmp = array(); // Make nice output
	foreach($bankaccount_transaction->getInfoForDisplay() as $key => $value) {
		$tmp[] = $key.'=<span class="value key_'.$key.'">'.$value.'</span>';
	}
	echo implode($tmp, '<br />');
	echo '</td>'.chr(10).
		'		<td class="button">';
	echo __('Unknown');
	
	echo	'</td>'.chr(10).
		'		<td style="text-align: right;">'.html::money($sum_after).'</td>'.chr(10).
		'		<td style="text-align: center;">'.($bankaccount_transaction->imported?'X':'-').'</td>'.chr(10).
		'	</tr>'.chr(10);
}
echo '</table>'.chr(10);
