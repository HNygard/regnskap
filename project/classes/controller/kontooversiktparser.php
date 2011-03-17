<?php


class Controller_Kontooversiktparser extends Controller

{
	function action_srbank ()
{

function csv_to_array($array)
{
	$csv_array = array();
	foreach($array as $i => $linje) {
		if(trim($linje) != '')
			$csv_array[$i] = explode(";",trim($linje)); //Bet.dato;Beskrivelse;Rentedato;Ut/Inn;
	}
	return $csv_array;
}

$hovedmappe = '../import/sr-bank';

// Finner filer
$filer = array();
$Q = DB::query(Database::SELECT, "select * from `bankkontoer`")->execute();

echo '<b>Bankkontoer</b>:<br />';
echo '<ul>';
if(!$Q->count())
{
	echo '<span style="color: red;">Ingen bankkontoer opprettet!</span>';
}
foreach($Q->as_array() as $R)
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
				$Q_tidligeretransaksjoner = DB::query(Database::SELECT, "
					SELECT *
					FROM `banktransaksjoner`
					WHERE
						`betdato`      = '".utf8::clean($csv[0])."' AND
						`rentedato`    = '".utf8::clean($csv[2])."' AND
						`beskrivelse`  = '".utf8::clean($csv[1])."' AND
						`belop`        = '".utf8::clean($csv[3])."' AND
						`bankkonto_nr` = '".$bankkonto_nr."'")
					->execute(); // Sjekk for alle variablene
				//echo mysql_error();exit;
				if($Q_tidligeretransaksjoner->count())
					$filer[$bankkonto_nr][$i][2]++;
				else
				{
					// Legger inn i database
					$filer[$bankkonto_nr][$i][1]++;
				
					DB::query(Database::INSERT, "
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
						")->execute();
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

}
}
