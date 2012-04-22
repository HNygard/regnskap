<?php

echo html::anchor('index.php/transaction/create', __('Create new transaction'));
error_reporting(E_ALL);
ini_set('display_errors', true);

$bankaccounts = Sprig::factory('bankaccount', array())->load(DB::select(), FALSE);
$bankaccount_names = array();
$bankaccount_names[null] = '';
foreach($bankaccounts as $bankaccount_tmp) {
	$bankaccount_names[$bankaccount_tmp->id] = $bankaccount_tmp->num .' - '. $bankaccount_tmp->name;
}


$sum = 0;
echo '<table class="prettytable">';
foreach($transactions as $transaction)
{
	$transaction->account->load();
	$transaction->bankaccount_transaction->load();
	$bankaccount_transaction_data = array(); // Make nice output
	foreach($transaction->bankaccount_transaction->getInfoForDisplay() as $key => $value) {
		$bankaccount_transaction_data[] = $key.'=<span class="value key_'.$key.'">'.$value.'</span>';
	}
	
	echo '	<tr>'.
			'<td>'.$transaction->id.'</td>'.
			'<td><b>'.$transaction->account->name.'</b></td>'.
			'<td>'.date('d.m.Y', $transaction->time).'</td>'.
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
'<td>'.
	implode($bankaccount_transaction_data, '<br />').'<br />'.
	// Print bank account name
	'<b>'.$bankaccount_names[$transaction->bankaccount_transaction->bankaccount_id].'</b>'.
'</td>'.
		'</tr>'.chr(10);
	$sum += $transaction->amount;
}
echo '	<tr>'.
		'		<th>SUM</th>'.
		'		<th>'.HTML::money($sum).'</th>'.
	'	</tr>'.chr(10);
echo '</table>';
