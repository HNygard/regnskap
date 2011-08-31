<?php


echo '<table>'.chr(10);
echo
	'	<tr>'.chr(10).
	'		<th>'.__('From').'</th>'.chr(10).
	'		<th>'.__('To').'</th>'.chr(10).
	'		<th>'.__('File').'</th>'.chr(10).
	'	</tr>'.chr(10);

$last_to = mktime(0,0,0,1,1,2000);
foreach($bankaccount_importfiles as $bankaccount_importfile)
{
	if($last_to < $bankaccount_importfile->from)
	{
		echo
			'	<tr>'.chr(10).
			'		<td>'.date('d-m-Y', $last_to).'</td>'.chr(10).
			'		<td>'.date('d-m-Y', $bankaccount_importfile->from).'</td>'.chr(10).
			'		<td>'.__('Missing').'</td>'.chr(10).
			'	</tr>'.chr(10);
	}
	$last_to = mktime(0,0,0,date('m', $bankaccount_importfile->to), date('d', $bankaccount_importfile->to)+1, date('Y', $bankaccount_importfile->to));
	echo
		'	<tr>'.chr(10).
		'		<td>'.date('d-m-Y', $bankaccount_importfile->from).'</td>'.chr(10).
		'		<td>'.date('d-m-Y', $bankaccount_importfile->to).'</td>'.chr(10).
		'		<td>'.$bankaccount_importfile->filepath.'</td>'.chr(10).
		'	</tr>'.chr(10);
}

if($last_to < mktime(0,0,0,date('m'),date('d'),date('Y')))
{
	echo
		'	<tr>'.chr(10).
		'		<td>'.date('d-m-Y', $last_to).'</td>'.chr(10).
		'		<td>'.date('d-m-Y').'</td>'.chr(10).
		'		<td>'.__('Missing').'</td>'.chr(10).
		'	</tr>'.chr(10);
}

echo '</table>'.chr(10);
