<?php

echo '<table class="prettytable">';
foreach($bankaccounts as $bankaccount)
{
	echo '	<tr>'.
			'<th>'.$bankaccount->num.'</th>'.
			'<td>'.$bankaccount->type.'</td>'.
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
echo '</table>';
