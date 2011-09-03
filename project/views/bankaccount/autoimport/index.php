<?php

echo html::anchor('index.php/bankaccount_autoimport/create', __('Create new autoimport'));


echo '<table>'.chr(10);
echo
	'	<tr>'.chr(10).
	'		<th>'.__('Id').'</th>'.chr(10).
	'		<th>'.__('Options').'</th>'.chr(10).
	'		<th>'.__('Account name').'</th>'.chr(10).
	'		<th>'.__('Data').'</th>'.chr(10).
	'		<th>'.__('Bankaccount').'</th>'.chr(10).
	'		<th>'.__('Amount maximum').'</th>'.chr(10).
	'		<th>'.__('Amount minimum').'</th>'.chr(10).
	'		<th>'.__('Time maximum').'</th>'.chr(10).
	'		<th>'.__('Time minimum').'</th>'.chr(10).
	'	</tr>'.chr(10);
foreach($bankaccount_autoimports as $bankaccount_autoimport)
{
	$account = Sprig::factory('account', 
		array('id' => $bankaccount_autoimport->account_id))->load();
	if(!is_null($bankaccount_autoimport->bankaccount_id))
	{
		$bankaccount = Sprig::factory('bankaccount', 
			array('id' => $bankaccount_autoimport->bankaccount_id))->load();
		$bankaccount_num = $bankaccount->num;
	}
	else
		$bankaccount_num = '&nbsp;';
	echo '	<tr>'.
			'<td>'.$bankaccount_autoimport->id.'</td> '.
			
			'<td>'.
			html::anchor(
				'index.php/bankaccount_autoimport/edit/'.$bankaccount_autoimport->id,
				__('Edit')
			).
			', '.
			html::anchor(
				'index.php/bankaccount_autoimport/delete/'.$bankaccount_autoimport->id,
				__('Delete')
			).
			'</td>'.
			
			'<td>'.$account->name.'</td>'.
			
			'<td>';
	$tmp = array(); // Make nice output
	foreach($bankaccount_autoimport->getInfo() as $key => $value) {
		$tmp[] = $key.'=<span class="value key_'.$key.'">'.$value.'</span>';
	}
	echo implode($tmp, '<br />');
	echo '</td>'.
			'<td>'.$bankaccount_num.'</td>'.
			'<td>&nbsp;'.$bankaccount_autoimport->amount_max.'</td>'.
			'<td>&nbsp;'.$bankaccount_autoimport->amount_min.'</td>'.
			'<td>&nbsp;'.$bankaccount_autoimport->time_max.'</td>'.
			'<td>&nbsp;'.$bankaccount_autoimport->time_min.'</td>'.
		
		'</tr>'.chr(10);
}
echo '</table>';
