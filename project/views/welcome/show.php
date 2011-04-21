<?php


// $transactions_query

$by_month     = array();
$month_first  = null;
$month_last   = null;
foreach($transactions_query as $transaction)
{
	$month = date('Ym', $transaction['time']);
	
	if(is_null($month_first) || $month_first > $month)
		$month_first = $month;
	if(is_null($month_last) || $month_last < $month)
		$month_last = $month;
	
	if(!isset($by_month[$transaction['account_id']]))
		$by_month[$transaction['account_id']] = array();
	
	if(!isset($by_month[$transaction['account_id']][$month]))
		$by_month[$transaction['account_id']][$month] = 0;
	
	$by_month[$transaction['account_id']][$month] += $transaction['amount'];
}

function getNextMonth($month)
{
	$year = substr($month, 0, 4);
	$month = (((int)substr($month, 4))+1);
	
	if($month == 13)
	{
		$year++;
		$month = 01;
	}
	if(strlen($month) == 1)
		$month = '0'.$month;
	
	return $year.$month;
}

// Printing months
echo '<table>'.chr(10);
echo '	<tr>'.chr(10);
echo '		<th>&nbsp;</th>'.chr(10);
for($month = $month_first; $month <= $month_last; $month = getNextMonth($month))
{
	echo '		<th>'.substr($month, 4).'.'.substr($month, 0, 4).'</th>'.chr(10);
}
echo '	</tr>'.chr(10).chr(10);

$accounts = DB::select()->from('accounts');
$accounts->where('id','!=','-1');
foreach($by_month as $account_id => $account_months)
{
	$accounts->or_where('id', '=', $account_id);
}
$query = $accounts->order_by('num')->execute();

foreach($query as $account)
{
	echo '	<tr>'.chr(10);
	echo '		<th>'.str_replace(' ', '&nbsp;', $account['name']).'</th>'.chr(10);
	for($month = $month_first; $month <= $month_last; $month = getNextMonth($month))
	{
		if(isset($by_month[$account['id']][$month]))
			$this_month = $by_month[$account['id']][$month];
		else
			$this_month = 0;
		
		echo '		<td align="right">'.str_replace(' ', '&nbsp;', HTML::money($this_month)).'</td>'.chr(10);
	}
	echo '	</tr>'.chr(10).chr(10);
}

echo '</table>'.chr(10).chr(10);
