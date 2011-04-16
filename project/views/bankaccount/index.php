<?php

echo '<ul>';
foreach($bankaccounts as $bankaccount)
{
	echo '	<li>'.
			'<b>'.$bankaccount->num.':</b> '.
			html::anchor(
				'index.php/bankaccount/missingimports/'.$bankaccount->id,
				__('Missing imports')
			).
			', '.
			html::anchor(
				'index.php/bankaccount/transactions/'.$bankaccount->id,
				__('Transactions')
			).
		'</li>';
}
echo '</ul>';
