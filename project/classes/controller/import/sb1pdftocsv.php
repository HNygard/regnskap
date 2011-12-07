<?php


class Controller_Import_SB1pdftocsv extends Controller_Import
{
	protected $srbank_pdf_main_folder = '../import/sr-bank_pdf';
	protected $srbank_pdftocsv_export_folder = '../export/sr-bank_pdftocsv/';
	
	public function before()
	{
		$this->use_template2 = false;
		$this->template = 'import/index';
		
		return parent::before();
	}
	
	function action_index ()
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
	
		asort($this->importfiles_files_found);
		echo '<ul>';
		foreach($this->importfiles_files_found as $file)
		{
			echo '<li><b>'.html::anchor('index.php/Import_SB1pdftocsv/convertfile/'.base64_encode($file), $file).'</b> ';
			echo '</li>';
		}
		echo '</ul>';
		exit;
	}
	
	function action_convertfile ($file) {
		$file  = base64_decode($file);
		if(!file_exists($file)) {
			echo 'File does not exist.';
			exit;
		}
		
		echo '<ul><li>';
		try {
			$statementparser = new sb1parser();
			$statementparser->importPDF(file_get_contents($file, FILE_BINARY));
	
			foreach($statementparser->getAccounts() as $account)
			{
				$csvfile = $this->srbank_pdftocsv_export_folder.
					'statement '.$account['account_num'].' '.date('Y-m', $account['accountstatement_end']).'.csv';
				file_put_contents($csvfile, sb1parser::getCSV($account));
				echo '<li>';
				echo '<b>'.__('Bankaccount').':</b> '.$account['account_num'].', '.
					__('From').': '.date('d-m-Y', $account['accountstatement_start']).', '.
					__('To').': '.date('d-m-Y', $account['accountstatement_end']).'</li><li>';
				echo __('Transactions').': '.count($account['transactions']);
				echo '<br />';
				echo 'Saved to <b>'.$csvfile.'</b>';
				echo '</li>';
			}
		} catch (Validate_Exception $e) {
			echo '</li><li style="color: red;">'.print_r($e, true).'<br /><b>Transactions: </b>';
		} catch (Exception $e) {
			echo '</li><li style="color: red;">'.
				'FILE '.$e->getFile().'<br />'.
				'LINE '.$e->getLine().'<br />'.
				$e->getMessage();
		}
		echo '</li></ul>';
		exit;
	}
}
