<?php

echo '<ul>';
foreach($bankaccounts as $bankaccount)
{
	echo '	<li>'.html::anchor(
			'index.php/bankaccount/transactions/'.$bankaccount->id,
			$bankaccount->num
		).'</li>';
}
echo '</ul>';
