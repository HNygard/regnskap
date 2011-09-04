<?php

set_time_limit(0);

function csv_to_array($array)
{
	$csv_array = array();
	foreach($array as $i => $linje) {
		if(trim($linje) != '')
			$csv_array[$i] = explode(";",trim($linje)); //Bet.dato;Beskrivelse;Rentedato;Ut/Inn;
	}
	return $csv_array;
}

class Controller_Import extends Controller_Template
{
	protected $srbank_main_folder = '../import/sr-bank';
	protected $srbank_pdf_main_folder = '../import/sr-bank_pdf';
	protected $generic_csv_main_folder = '../import/generic_csv';
	protected $kolumbus_main_folder = '../import/kolumbus';
	
	protected $importfiles_recursive = false;
	protected $importfiles_search_string;
	
	function action_index ()
	{
		$this->template2->title = __('Import');
	}
	
	public function before()
	{
		if(
			$this->request->action() == 'srbank' || 
			$this->request->action() == 'srbank_pdf' || 
			$this->request->action() == 'generic_csv' || 
			$this->request->action() == 'kolumbus'
		)
		{
			$this->use_template2 = false;
			$this->template = 'import/index';
		}
		
		return parent::before();
	}
	
	function action_srbank ()
	{
		$this->importfiles('srbank_csv', $this->srbank_main_folder);
	}
	
	function action_generic_csv ()
	{
		$this->importfiles('generic_csv', $this->generic_csv_main_folder);
	}
	
	function action_kolumbus ()
	{
		$search_string_kolumbus = 'Her finner du en oversikt med din registrerte saldo og de siste';
		
		$this->importfiles_recursive = true;
		$this->importfiles_search_string = $search_string_kolumbus;
		$this->importfiles ('kolumbus', $this->kolumbus_main_folder);
	}
	
	function importfiles_readfolder ($bankaccount_id, $folder)
	{
		// Make sure $folder ends with /
		if(substr($folder, -1, 1) != '/') {
			$folder .= '/';
		}
		
		$handle = opendir($folder);
		
		while (false !== ($file = readdir($handle)))
		{
			if($file == '..' || $file == '.') {
				continue;
			}
			
			// :? $fils is directory?
			if(is_dir($folder.$file)) {
				// -> $file is directory
				
				// :? Going through subdirectories of $folder?
				if($this->importfiles_recursive) {
					// -> Yes, lets read the subdirectory also
					$this->importfiles_readfolder($bankaccount_id, $folder.$file);
				} else {
					// -> No reading of subdirectories
					// Ignore
					continue;
				}
			}
			else {
				// -> $file is not a directory
				
				// :? Are we searching for strings in the file?
				if(isset($this->importfiles_search_string)) {
					// -> File must contain $this->importfiles_search_string
					
					// :? Search
					if(strpos(file_get_contents($folder.$file), $this->importfiles_search_string) !== FALSE)
					{
						// -> String found in file
						echo '<li>'.$folder.$file.'</li>';
						$this->importfiles_files_found[$bankaccount_id][] = $folder.$file;
					}
				}
				else {
					echo '<li>'.$folder.$file.'</li>';
					$this->importfiles_files_found[$bankaccount_id][] = $folder.$file;
				}
			}
		}
	}
	
	function importfiles ($import_type, $mainfolder)
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
		$this->importfiles_files_found = array();
		foreach($bankaccounts as $bankaccount)
		{
			$folder = $mainfolder.'/'.$bankaccount->num.'/';
			$this->importfiles_files_found[$bankaccount->id] = array();
			echo '<li><b>'.$bankaccount->num.'</b><ul>';
		
			// :? Check if folder exists and is a direcotry
			if (file_exists($folder) && is_dir($folder))
			{
				// -> Folder exist and is a directory
				
				// Read files in folder
				$this->importfiles_readfolder($bankaccount->id, $folder);
			}
			else
			{
				// -> Folder not found or is not a directory
				echo '<span style="color: red;">'.__('No folder for bank account :bankaccount_num (:folder) exists',
					array(':bankaccount_num' => $bankaccount->num, ':folder' => $folder)).'.</span>';
			}
			echo '</ul></li>';
		}
		echo '</ul>';
	
		echo '<ul>';
		foreach($this->importfiles_files_found as $bankaccount_id => $files)
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
				
				echo '<table>';
				try {
					if($import_type == 'srbank_csv')
						$importfile->importFromSRbank_CSVFile();
					elseif($import_type == 'generic_csv')
						$importfile->importFromGenericCSVFile();
					elseif($import_type == 'kolumbus')
						$importfile->importFromKolumbusReisekonto();
					else
						throw new Exception ('importtype not valid');
				} catch (Validation_Exception $e) {
					echo '</li><li style="color: red;">'.print_r($e->array->errors(), true);
					throw $e;
				} catch (Exception $e) {
					echo '</li><li style="color: red;">'.
						'FILE '.$e->getFile().'<br />'.
						'LINE '.$e->getLine().'<br />'.
						$e->getMessage();
				}
				echo '</table>';
				echo '</li></ul>';
				echo '</li>';
			}
		}
		echo '</ul>';
		exit;
	}
	
	function action_srbank_pdf ()
	{
		/*
		$files_found = array(
				'filepath',
				'filepath',
			);
		*/
		$files_found = array();
		$folder = $this->srbank_pdf_main_folder.'/';
		echo '<li><b>'.__('Files found').':</b><ul>';
	
		// Getting files from the folder:
		if (file_exists($folder) && $handle = opendir($folder))
		{
			while (false !== ($file = readdir($handle)))
			{
				if($file != '..' && $file != '.')
				{
					echo '<li>'.$folder.$file.'</li>';
					$files_found[] = $folder.$file;
				}
			}
		}
		else
		{
			// Folder not found
			echo '<span style="color: red;">'.__('Folder does not exist: :folder',
				array(':folder' => $folder)).'.</span>';
		}
		echo '</ul></li>';
		echo '</ul>';
	
		echo '<ul>';
		foreach($files_found as $file)
		{
			if($file == '../import/sr-bank_pdf/.gitignore')
				continue;
			
			echo '<li><b>'.$file.'</b> ';
			$importfile = Sprig::factory('bankaccount_importfile', 
				array(
					'filepath' => $file,
				));
			echo '<ul><li>';
			$importfile->load();
			if($importfile->loaded())
			{
				echo __('Already imported');
			}
			else
			{
				echo __('Not jet imported');
			}
			try {
echo '<table>';
				$importfile->importFromSRbank_PDFFile();
			} catch (Validate_Exception $e) {
				echo '</li><li style="color: red;">'.print_r($e, true).'<br /><b>Transactions: </b>';
			} catch (Exception $e) {
				echo '</li><li style="color: red;">'.
					'FILE '.$e->getFile().'<br />'.
					'LINE '.$e->getLine().'<br />'.
					$e->getMessage();
			}
echo '</table>';
			echo '</li></ul>';
			echo '</li>';
		}
		echo '</ul>';
		exit;
	}
}
