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
	static public $transactionfiles_main_folder = '../import/images';
	
	protected $importfiles_recursive = false;
	protected $importfiles_search_string;
	protected $importfiles_without_bankaccount_id = false;
	protected $importfiles_readfolder_printlist = true;
	protected $importfiles_readfolder_incdirectories = true;
	
	static protected $importfiles_accountlist;
	
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
		$this->importfiles_recursive = true;
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
	
	static function transactionfiles_analyze($file) {
		// Format: Account - Y-m-d - xyz,ab kr - description
		// Format: Y-m-d - xyz,ab kr - description
		
		if(!isset(self::$importfiles_accountlist)) {
			// Getting accounts
			self::$importfiles_accountlist = array();
			$bankaccounts = Sprig::factory('bankaccount', array())->load(NULL, FALSE);
			foreach($bankaccounts as $bankaccount)
			{
				self::$importfiles_accountlist[$bankaccount->id] = $bankaccount->num;
			}
		}
		
		$return = array();
		$delimiter = ' - ';
		$file = basename($file); // Get just the file name
		
		// Getting account, if it exists in database and in filename
		// Format: Account - Y-m-d - xyz,ab kr - description
		foreach(self::$importfiles_accountlist as $id => $num) {
			if(substr($file, 0, strlen($num)) == $num) {
				// -> Account number is first in the filename
				$return['account'] = $num;
				$file = str_replace($num.$delimiter, '', $file); // Remove from filename
			}
		}
		
		// Getting date, if any
		// Format: Y-m-d - xyz,ab kr - description
		$split = explode($delimiter, $file, 3);
		if(isset($split[0]) && self::isYmd($split[0])) {
			$return['date'] = self::getYmd($split[0]);
			$file = str_replace($split[0].$delimiter, '', $file); // Remove from filename
		}
		elseif(isset($split[1]) && self::isYmd($split[1])) {
			$return['account'] = $split[0]; // Unknown account
			$return['date'] = self::getYmd($split[1]);
			$file = str_replace($split[1].$delimiter, '', $file); // Remove from filename
		}
		
		// Getting amount, if any
		// Format: xyz,ab kr - description
		$split = explode($delimiter, $file, 2);
		if(isset($split[0]) && self::isKr($split[0])) {
			$return['amount'] = (self::getKr($split[0]) / 100);
			$file = str_replace($split[0].$delimiter, '', $file);
		}
		
		
		// File ending
		$return['extention']   = pathinfo($file, PATHINFO_EXTENSION);
		$return['description'] = pathinfo($file, PATHINFO_FILENAME);
		
		return $return;
	}
	static function isYmd($string) {
		// Format: YYYY-mm-dd
		if(strlen($string) != strlen('YYYY-mm-dd')) {
			return false;
		}
		
		if(
			is_numeric($string{0}) && // Y
			is_numeric($string{1}) && // Y
			is_numeric($string{2}) && // Y
			is_numeric($string{3}) && // Y
			'-' ==     $string{4}  && // -
			is_numeric($string{5}) && // m
			is_numeric($string{6}) && // m
			'-' ==     $string{7}  && // -
			is_numeric($string{8}) && // d
			is_numeric($string{9})    // d
		) {
			return true;
		}
		
		return false;
	}
	static function getYmd($date) {
		// Format: YYYY-mm-dd
		$parts = explode('-', $date);
		if(count($parts) == 3)
		{
			return mktime (0, 0, 0, $parts[1], $parts[2], $parts[0]);
		}
	}
	static function isKr ($string) {
		if(strpos($string, ' kr') === FALSE || strpos($string, ',') === FALSE) {
			return false;
		}
		
		$string = str_replace(' kr', '', $string);
		$string = str_replace(',', '', $string);
		
		if(is_numeric($string)) {
			return true;
		}
		
		return false;
	}
	static function getKr ($string) {
		if(strpos($string, ' kr') === FALSE || strpos($string, ',') === FALSE) {
			return 0;
		}
		
		$string = str_replace(' kr', '', $string);
		$string = str_replace(',', '', $string);
		
		if(is_numeric($string)) {
			// Returns øre
			return $string;
		}
		
		return 0;
	}
	
	function action_transactionfiles ($action = '', $path = '')
	{
		if($action == 'renamejs') {
			if(
				!isset($_POST['folder']) ||
				!isset($_POST['filenameoriginal']) ||
				!isset($_POST['filename'])
			) {
				echo 'Parameter not found';
				exit;
			}
			
			// Cleaning
			// Folders are allowed to have / but not .
			$folder           = preg_replace("/[^A-Za-z0-9\(\)\_\,\-\/\s]/", "", $_POST['folder']);
			$filenameoriginal = preg_replace("/[^A-Za-z0-9\(\)\_\,\-\.\s]/", "", $_POST['filenameoriginal']);
			$filename         = preg_replace("/[^A-Za-z0-9\(\)\_\,\-\.\s]/", "", $_POST['filename']);
			$filenameoriginal = str_replace('..', '', $filenameoriginal);
			$filename         = str_replace('..', '', $filename);
			
			// Check if it exists
			$fileoriginal = self::$transactionfiles_main_folder.$folder.'/'.$filenameoriginal;
			if(!file_exists($fileoriginal)) {
				echo 'File not found.';
				echo chr(10).$fileoriginal;
				exit;
			}
			
			// Rename
			$newname = self::$transactionfiles_main_folder.$folder.'/'.$filename;
			$failure = false;
			try {
				rename($fileoriginal, $newname);
			}
			catch (Exception $e) {
				$failure = true;
			}
			if(!$failure) {
				echo 'ok';
				exit;
			}
			else {
				echo 'Rename failed';
				echo chr(10).$e->getMessage();
				exit;
			}
		}
		
		if($action != '') {
			if($path != '') {
				$action .= '/'.$path;
			}
			$path = preg_replace("/[^A-Za-z0-9\(\)\_\,\-\/\.\s]/", '', $action);
			$folder = self::$transactionfiles_main_folder.'/'.str_replace('..', '', $path);
		}
		else {
			$folder = self::$transactionfiles_main_folder;
		}
		
		$this->importfiles_recursive = false;
		$this->importfiles_readfolder_printlist = false;
		$this->importfiles_readfolder_incdirectories = true;
		$this->importfiles_files_found = array();
		$this->importfiles_folders_found = array();
		$this->importfiles_readfolder ('transactionfiles', $folder);
		
		$this->template2->title = __('View and rename files for transations');
		$this->template->importfiles_files_found   = $this->importfiles_files_found;
		$this->template->importfiles_folders_found = $this->importfiles_folders_found;
		$this->template->folder = $folder;
	}
	
	function importfiles_readfolder ($bankaccount_id, $folder)
	{
		// Make sure $folder ends with /
		if(substr($folder, -1, 1) != '/') {
			$folder .= '/';
		}
		
		if(!is_dir($folder)) {
			return;
		}
		
		$handle = opendir($folder);
		
		while (false !== ($file = readdir($handle)))
		{
			if($file == '..' || $file == '.' || $file == '.gitignore') {
				continue;
			}
			
			// :? $fils is directory?
			if(is_dir($folder.$file)) {
				// -> $file is directory
				
				if($this->importfiles_readfolder_incdirectories) {
					$this->importfiles_folders_found[] = $folder.$file;
				}
				
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
						if($this->importfiles_readfolder_printlist) {
							echo '<li>'.$folder.$file.'</li>';
						}
						if($this->importfiles_without_bankaccount_id)
							$this->importfiles_files_found[] = $folder.$file;
						else
							$this->importfiles_files_found[$bankaccount_id][] = $folder.$file;
					}
				}
				else {
					if($this->importfiles_readfolder_printlist) {
						echo '<li>'.$folder.$file.'</li>';
					}
					if($this->importfiles_without_bankaccount_id)
						$this->importfiles_files_found[] = $folder.$file;
					else
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
		$this->importfiles_files_found = array();
		$folder = $this->srbank_pdf_main_folder.'/';
		echo '<li><b>'.__('Files found').':</b><ul>';
	
		// Getting files from the folder:
		if (file_exists($folder) && $handle = opendir($folder))
		{
			$this->importfiles_without_bankaccount_id = true;
			$this->importfiles_recursive = true;
			$this->importfiles_readfolder(0, $folder);
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
		foreach($this->importfiles_files_found as $file)
		{
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
