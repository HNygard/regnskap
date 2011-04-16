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

class Controller_Import extends Controller
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
			echo html::msg_error(__('No bank accounts created'));
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
}
