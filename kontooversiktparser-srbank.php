<?php

require "conf/conf.php";

//require "class/csv-function.php";

function csv_to_array($array)
{
	$csv_array = array();
	foreach($array as $i => $linje) {
		if(trim($linje) != '')
			$csv_array[$i] = explode(";",trim($linje)); //Bet.dato;Beskrivelse;Rentedato;Ut/Inn;
	}
	return $csv_array;
}

$hovedmappe = 'import/sr-bank';

// Finner filer
$filer = array();
$Q = mysql_query("select * from `bankkontoer`");

echo '<b>Bankkontoer</b>:<br />';
echo '<ul>';
if(!mysql_num_rows($Q))
{
	echo '<span style="color: red;">Ingen bankkontoer opprettet!</span>';
}
while($R = mysql_fetch_assoc($Q))
{
	$mappe = $hovedmappe.'/'.$R['nr'].'/';
	$filer[$R['nr']] = array();
	echo '<li><b>'.$R['nr'].'</b><ul>';
	// Henter filer fra mappe:
	if (file_exists($mappe) && $handle = opendir($mappe)) {
		while (false !== ($file = readdir($handle))) {
			if($file != '..' && $file != '.')
			{
				echo '<li>'.$mappe.$file.'</li>';
				$filer[$R['nr']][][0] = $mappe.$file;
			}
		}
	}
	else
	{
		echo '<span style="color: red;">Mappen til kontonr '.$R['nr'].' ('.$mappe.') eksisterer ikke.</span>';
	}
	echo '</ul></li>';
	// TODO:
	/*foreach ($fil)
	{
		$Q_fil = mysql_query("");
		if(!mysql_num_rows($Q_fil))
			$filer[$R['id']][] = array('fil');
	}*/
}
echo '</ul>';

foreach ($filer as $bankkonto_nr => $filarray)
{
	foreach($filarray as $i => $filen)
	{
		// Parser filer
		$csv_array = csv_to_array(explode("\n", file_get_contents($filen[0])));
		
		$filer[$bankkonto_nr][$i][1] = 0; // Nye
		$filer[$bankkonto_nr][$i][2] = 0; // Allerede inne
		
		foreach($csv_array as $csv)
		{
			// Sjekker at det ikke er den første linjen
			if($csv[1] == 'Beskrivelse')
				continue;
			
			// Sjekker mot db
			//TODO: Muligens noe behandling av dato og andre data
			if(!empty($csv) && $csv[2] != '')
			{
				$Q_tidligeretransaksjoner = mysql_query("
					SELECT *
					FROM `banktransaksjoner`
					WHERE
						`betdato`      = '".$csv[0]."' AND
						`rentedato`    = '".$csv[2]."' AND
						`beskrivelse`  = '".$csv[1]."' AND
						`belop`        = '".$csv[3]."' AND
						`bankkonto_nr` = '".$bankkonto_nr."'"); // Sjekk for alle variablene
				//echo mysql_error();exit;
				if(mysql_num_rows($Q_tidligeretransaksjoner))
					$filer[$bankkonto_nr][$i][2]++;
				else
				{
					// Legger inn i database
					$filer[$bankkonto_nr][$i][1]++;
				
					mysql_query("
						INSERT INTO `banktransaksjoner` 
							(
								`id` ,
								`betdato` ,
								`rentedato` ,
								`beskrivelse` ,
								`belop` ,
								`bankkonto_nr`
							)
							VALUES (
								NULL , 
								'".$csv[0]."', 
								'".$csv[2]."', 
								'".$csv[1]."', 
								'".$csv[3]."', 
								'".$bankkonto_nr."'
							);
");
				}
			}
		}
	
	}
}

echo '<table>'.chr(10);
echo '	<tr>'.chr(10);
echo '		<td><b>Bankkontonr</b></td>'.chr(10);
echo '		<td><b>Fil</b></td>'.chr(10);
echo '		<td><b>Nye</b></td>'.chr(10);
echo '		<td><b>Allerede inne</b></td>'.chr(10);
echo '	</tr>'.chr(10).chr(10);
foreach ($filer as $bankkonto_nr => $filarray)
{
	foreach($filarray as $filen)
	{
		echo '	<tr>'.chr(10);
		echo '		<td>'.$bankkonto_nr.'</td>'.chr(10);
		echo '		<td>'.$filen[0].'</td>'.chr(10);
		echo '		<td>'.$filen[1].'</td>'.chr(10);
		echo '		<td>'.$filen[2].'</td>'.chr(10);
		echo '	</tr>'.chr(10).chr(10);
	}
}
echo '</table>'.chr(10);
