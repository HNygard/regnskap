<?php


class Controller_Import_SB1pdftocsv extends Controller_Template
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
			echo '</li>';
		}
		echo '</ul>';
		exit;
	}
}
