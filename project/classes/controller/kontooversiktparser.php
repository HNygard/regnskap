<?php

function csv_to_array($array)
{
	$csv_array = array();
	foreach($array as $i => $linje) {
		if(trim($linje) != '')
			$csv_array[$i] = explode(";",trim($linje)); //Bet.dato;Beskrivelse;Rentedato;Ut/Inn;
	}
	return $csv_array;
}

class Controller_Kontooversiktparser extends Controller

{
	protected $srbank_main_folder = '../import/sr-bank';
	function action_srbank ()
{
	//$Q = DB::query(Database::SELECT, "select * from `bankkontoer`")->execute();
	$bankaccounts = Sprig::factory('bankaccount', array())->load(NULL, FALSE);
	
	echo '<b>'.html::anchor('index.php/bankaccount', __('Bank accounts')).'</b>:<br />';
	echo '<ul>';
	if(!$bankaccounts->count())
	{
		echo '<div class="error">'.__('No bank accounts created').'</div>';
	}
	
	/*
	$files_found = array(
			accountnum => array(
				'filepath',
				'filepath',
			)
		);
	*/
	$files_found = array();
	foreach($bankaccounts as $bankaccount)
	{
		$folder = $this->srbank_main_folder.'/'.$bankaccount->num.'/';
		$files_found[$bankaccount->id] = array();
		echo '<li><b>'.$bankaccount->num.'</b><ul>';
		
		// Getting files from the folder:
		if (file_exists($folder) && $handle = opendir($folder))
		{
			while (false !== ($file = readdir($handle)))
			{
				if($file != '..' && $file != '.')
				{
					echo '<li>'.$folder.$file.'</li>';
					$files_found[$bankaccount->id][] = $folder.$file;
				}
			}
		}
		else
		{
			// Folder not found
			echo '<span style="color: red;">'.__('No folder for bank account :bankaccount_num (:folder) exists',
				array(':bankaccount_num' => $bankaccount->num, ':folder' => $folder)).'.</span>';
		}
		echo '</ul></li>';
	}
	echo '</ul>';
	
	echo '<ul>';
	foreach($files_found as $bankaccount_id => $files)
	{
		foreach($files as $file)
		{
			echo '<li><b>'.$file.'</b> ';
			$importfile = Sprig::factory('bankaccount_importfile', 
				array(
					'filepath' => $file,
					'bankaccount_id' => $bankaccount_id,
				));
			$importfile->load();
			if($importfile->loaded())
			{
				echo __('Already imported');
			}
			else
			{
				echo __('Not jet imported');
			}
			echo '<ul><li>';
				$importfile->importFromFile();
			echo '</li></ul>';
			echo '</li>';
		}
	}
	echo '</ul>';
}

	function action_srbank_old ()
{
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

/*

$transaksjoner = array(
		$bt->serializeTransaksjon() => Banktransaksjon $bt,
		$bt2->serializeTransaksjon() => Banktransaksjon $bt2,
		etc
	);

$filer = array(
		
	);
 */

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
								'".utf8::clean($csv[0])."', 
								'".utf8::clean($csv[2])."', 
								'".utf8::clean($csv[1])."', 
								'".utf8::clean($csv[3])."', 
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
