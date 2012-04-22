<?php

$sum_balance = 0;
echo '<table class="prettytable">';
foreach($bankaccounts as $bankaccount)
{
	$balance = $bankaccount->getBalance();
	$sum_balance += $balance;
	echo '	<tr>'.
			'<th style="text-align: left;">'.$bankaccount->name.'</th>'.
			'<td>'.$bankaccount->num.'</td>'.
			'<td>'.$bankaccount->type.'</td>'.
			'<td style="text-align: right;">'.html::money($balance).'</td>'.
			'<td>'.
			html::anchor(
				'index.php/bankaccount/missingimports/'.$bankaccount->id,
				__('Missing imports')
			).
			', '.
			html::anchor(
				'index.php/bankaccount/transactions/'.$bankaccount->id,
				__('Transactions')
			).
			', '.
			html::anchor(
				'index.php/bankaccount/transactionsnotimported/'.$bankaccount->id,
				__('Not imported transactions')
			).
			', '.
			html::anchor(
				'index.php/bankaccount/autoimport/'.$bankaccount->id,
				__('Automaticlly import transactions')
			).
			'</td>'.
		'</tr>';
}
echo '	<tr>'.
		'<th style="text-align: left;">SUM</th>'.
		'<td>&nbsp;</td>'.
		'<td>&nbsp;</td>'.
		'<td style="text-align: right;">'.html::money($sum_balance).'</td>'.
		'<td>'.
			html::anchor(
				'index.php/bankaccount/autoimport/',
				__('Automaticlly import transactions').' '.__('on all bank accounts')
			).'</td>'.
	'</tr>';
echo '</table>';
