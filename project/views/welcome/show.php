<?php


// $transactions_query
if(!count($transactions_query)) {
	echo 'No transactions.';
}

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
	$month = (((int)substr($month, 4))-1);
	
	if($month == 0)
	{
		$year--;
		$month = 12;
	}
	if(strlen($month) == 1)
		$month = '0'.$month;
	
	return $year.$month;
}

// Printing months
echo '<table'.((!count($transactions_query))?' style="display: none;"':'').'>'.chr(10);
echo '	<tr>'.chr(10);
echo '		<th>&nbsp;</th>'.chr(10);
$i = 0;
for($month = $month_last; !is_null($month) && $month >= $month_first; $month = getNextMonth($month))
{
	$i ++;
	echo '		<th>'.HTML::anchor('index.php/transaction/showbydate/'.
					substr($month,0,4).'/'.substr($month,4,2)
			, substr($month, 4).'.'.substr($month, 0, 4)).'</th>'.chr(10);
	if($i == 12) {
		$i = 0;
		echo '		<th>&nbsp;</th>'.chr(10);
	}
}
echo '	</tr>'.chr(10).chr(10);

$accounts = DB::select()->from('accounts');
$accounts->where('id','!=','-1');
foreach($by_month as $account_id => $account_months)
{
	$accounts->or_where('id', '=', $account_id);
}
$query = $accounts->order_by('num')->execute();

$months = array();
foreach($query as $account)
{
	echo '	<tr>'.chr(10);
	echo '		<th>'.str_replace(' ', '&nbsp;', $account['name']).'</th>'.chr(10);
	$i = 0; $average = 0;
	for($month = $month_last; !is_null($month) && $month >= $month_first; $month = getNextMonth($month))
	{
		if(isset($by_month[$account['id']][$month]))
			$this_month = $by_month[$account['id']][$month];
		else
			$this_month = 0;
		
		$i++; $average += $this_month;
		
		echo '		<td align="right">'.HTML::anchor('index.php/transaction/showaccountbydate/'.
					$account['id'].'/'.
					substr($month,0,4).'/'.substr($month,4,2)
			, str_replace(' ', '&nbsp;', HTML::money($this_month))).'</td>'.chr(10);
		
		if($i == 12) {
			echo '		<td align="right">'.HTML::money($average/12).'</td>'.chr(10);
			$i = 0; $average = 0;
		}
		
		if(!isset($months[$month]))
			$months[$month] = 0;
		$months[$month] += $this_month;
	}
	echo '	</tr>'.chr(10).chr(10);
}

// Not imported - positiv
echo '	<tr>'.chr(10);
echo '		<th>'.__('Not imported').'</th>'.chr(10);
$i = 0; $average = 0;
foreach($months as $month => $this_month)
{
	// $motnhs[$month] += $this_month;
	$query = DB::select(array(DB::expr('SUM(amount)'), 'SUM'))->from('bankaccount_transactions');
	$query->where('imported', '=', false);
	$query->where('amount', '>=', 0);
	$query->where('date', '>=', mktime(0,0,0,substr($month,4,2),01,substr($month,0,4)));
	$query->where('date', '<', mktime(0,0,0,substr($month,4,2)+1,01,substr($month,0,4)));
	$result = $query->execute();
	foreach($result as $this_month)
	{
		$i++; $average += $this_month['SUM'];
		
		echo '		<td align="right">'.
				HTML::anchor('index.php/bankaccount/transactionsnotimported_bymonth/'.substr($month,0,4).'/'.substr($month,4,2),
					str_replace(' ', '&nbsp;', 
					HTML::money($this_month['SUM']))
				).
				'</td>'.chr(10);
		
		if($i == 12) {
			echo '		<td align="right">'.HTML::money($average/12).'</td>'.chr(10);
			$i = 0; $average = 0;
		}
		
		$months[$month] += $this_month['SUM'];
	}
}
echo '	</tr>'.chr(10);

// Not imported
echo '	<tr>'.chr(10);
echo '		<th>'.__('Not imported').'</th>'.chr(10);
$i = 0; $average = 0;
foreach($months as $month => $this_month)
{
	// $motnhs[$month] += $this_month;
	$query = DB::select(array(DB::expr('SUM(amount)'), 'SUM'))->from('bankaccount_transactions');
	$query->where('imported', '=', false);
	$query->where('amount', '<', 0);
	$query->where('date', '>=', mktime(0,0,0,substr($month,4,2),01,substr($month,0,4)));
	$query->where('date', '<', mktime(0,0,0,substr($month,4,2)+1,01,substr($month,0,4)));
	$result = $query->execute();
	foreach($result as $this_month)
	{
		$i++; $average += $this_month['SUM'];
		
		echo '		<td align="right">'.
				HTML::anchor('index.php/bankaccount/transactionsnotimported_bymonth/'.substr($month,0,4).'/'.substr($month,4,2),
					str_replace(' ', '&nbsp;', 
					HTML::money($this_month['SUM']))
				).
				'</td>'.chr(10);
		
		if($i == 12) {
			echo '		<td align="right">'.HTML::money($average/12).'</td>'.chr(10);
			$i = 0; $average = 0;
		}
		
		$months[$month] += $this_month['SUM'];
	}
}
echo '	</tr>'.chr(10);

// Sum
echo '	<tr>'.chr(10);
echo '		<th>'.__('Sum').'</th>'.chr(10);
foreach($months as $month => $this_month)
{
	echo '		<td align="right">'.str_replace(' ', '&nbsp;', HTML::money($this_month)).'</td>'.chr(10);
}
echo '	</tr>'.chr(10);

echo '</table>'.chr(10).chr(10);
