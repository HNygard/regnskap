<?php

echo html::anchor('index.php/bankaccount_autoimport/create', __('Create new autoimport'));

echo '<ul>';
foreach($bankaccount_autoimports as $bankaccount_autoimport)
{
	echo '	<li>'.
			'<b>'.$bankaccount_autoimport->id.':</b> '.
			
			html::anchor(
				'index.php/bankaccount_autoimport/edit/'.$bankaccount_autoimport->id,
				__('Edit')
			).
			', '.
			html::anchor(
				'index.php/bankaccount_autoimport/delete/'.$bankaccount_autoimport->id,
				__('Delete')
			).
		'</li>';
}
echo '</ul>';
