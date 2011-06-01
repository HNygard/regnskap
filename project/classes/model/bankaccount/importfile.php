<?php defined('SYSPATH') or die('No direct script access.');

class Model_Bankaccount_Importfile extends Sprig {
	
	public $transactions = array();
	private $transactions_new = 0;
	private $transactions_not_imported = 0;
	private $transactions_already_imported = 0;
	
	protected function _init()
	{
		$this->_fields += array(
			'id' => new Sprig_Field_Auto(array(
			)),
			'bankaccount_id' => new Sprig_Field_Integer(array(
			)),
			'filepath' => new Sprig_Field_Char(array(
			)),
			'from' => new Sprig_Field_Timestamp(array(
			)),
			'to' => new Sprig_Field_Timestamp(array(
			)),
			'last_imported' => new Sprig_Field_Timestamp(array(
			)),
		);
	}
	
	public function create_transactions ($transactions)
	{
		//$this->transactions_new = 0; // Nye
		//$this->transactions_already_imported; // Allerede inne
		//$this->transactions_not_imported; // 
		
		$this->from  = null;
		$this->to    = null;
		foreach($transactions as $transaction_array)
		{
			$transaction_array['description'] = str_replace('ß', 'ø', $transaction_array['description']); // encoding fix
			$transaction_array['description'] = str_replace('¿', 'ø', $transaction_array['description']); // encoding fix
			
			/*
			 * We have a match if:
			 * - Description is the same in lower case
			 * - Date is the same
			 * - Amount is the same
			 * - bankaccount_id is the same
			 */
			
			$selector = $transaction_array;
			unset($selector['description']); // Not matching on description, doing it "manually"
			unset($selector['type_csv']); // Checking "manually"
			unset($selector['type_pdf']); // Checking "manually"
			
			$tmp = $transaction_array['description']; // Saving description
			unset($transaction_array['description']); // Not matching on description, doing it "manually"
			
			$transactions = Sprig::factory('bankaccount_transaction', $selector)->load(NULL, FALSE);
			
			$no_match = true;
			$type = '';
			foreach($transactions as $transaction)
			{
				// Trying to match
				$type = '';
				if(!is_null($transaction->type_pdf))
					$type = 'type_pdf';
				if(!is_null($transaction->type_csv))
				{
					if($type == '')
						$type = 'type_csv';
					else
						$type .= ',type_csv';
				}
			
				if(
					mb_strtolower($tmp) != mb_strtolower($transaction->description) ||
					(
						!is_null($transaction->getType()) &&
						(
							(
								isset($transaction_array['type_pdf']) && 
								$transaction_array['type_pdf'] != '' &&
								$transaction_array['type_pdf'] != $transaction->getType()
							) ||
							(
								isset($transaction_array['type_csv']) && 
								$transaction_array['type_csv'] != '' &&
								$transaction_array['type_csv'] != $transaction->getType()
							)
						)		
					)
				)
				{
					// Not matching
				}
				else
				{
					// Update?
					$updates = array();
					if(isset($transaction_array['type_csv']) &&
						is_null($transaction->type_csv) && !is_null($transaction_array['type_csv']))
						$updates['type_csv'] = $transaction_array['type_csv'];
					if(isset($transaction_array['type_pdf']) &&
						is_null($transaction->type_pdf) && !is_null($transaction_array['type_pdf']))
						$updates['type_pdf'] = $transaction_array['type_pdf'];
					// Checking if this description has a higher amount of lower case characters
					// => more lower case = better description. CSV puts most in upper case
					// TODO: 
				
					if(count($updates))
					{
						// TODO: report back to 
						$transaction->values($updates)->update();
						echo 'update'.print_r($updates, true).'<br>';
					}
					$this->transactions_already_imported++;
					//echo $transaction.' - '.__('Already in database');
					
					$no_match = false;
					break;
				}
			}
			
			if($no_match)
			{
				// No match found
				$transaction_array['description'] = $tmp;
				$transaction = Sprig::factory('bankaccount_transaction', $transaction_array);
				$transaction->create();
				$this->transactions_new++;
				
				echo '<tr><td>'.$type.'</td><td>'.$tmp.'</td><td>'.
					(mb_strtolower($tmp) == mb_strtolower($transaction->description)).
					'</td><td>'.$transaction->description.'</td></tr>';
			}
			
			if(is_null($this->from))
				$this->from = $transaction->payment_date;
			elseif($this->from > $transaction->payment_date)
				$this->from = $transaction->payment_date; // Older transaction
			
			if(is_null($this->to))
				$this->to = $transaction->payment_date;
			elseif($this->to < $transaction->payment_date)
				$this->to = $transaction->payment_date; // Newer transaction
		}
	}
	
	public function importFromSRbank_CSVFile()
	{
		if(!isset($this->filepath) || $this->filepath == '')
		{
			throw new Kohana_Exception('Filepath not set.');
		}
		
		// Parser filer
		$csv_array = csv_to_array(explode("\n", file_get_contents($this->filepath)));
		
		foreach($csv_array as $csv)
		{
			// Checking if this is the first line in the feil
			if($csv[1] == 'Beskrivelse') // Contains column 'Beskrivelse'
				continue;
			
			// Sjekker mot db
			//TODO: Muligens noe behandling av dato og andre data
			if(!empty($csv))
			{
				if(utf8::clean($csv[2]) == '')
				{
					//echo __('One with no intrest date, not importing.');
					//echo '<br />';
					$this->transactions_not_imported++;
					continue;
				}
				
				if(strlen($csv[0]) > 10) // 01.08.2008-00:00:00
					$csv[0] = substr($csv[0], 0, 10); // 01.08.2008
				
				if(strlen($csv[2]) > 10) // 01.08.2008-00:00:00
					$csv[2] = substr($csv[2], 0, 10); // 01.08.2008
				
				$this->transactions[] = array(
						'bankaccount_id' => $this->bankaccount_id,
						'payment_date'   => Model_Bankaccount_Importfile::
						                    convert_stringDate_to_intUnixtime(utf8::clean($csv[0])),
						'intrest_date'   => utf8::clean($csv[2]),
						'description'    => utf8_encode($csv[1]),
						'amount'         => str_replace(',', '.', utf8::clean($csv[3])),
					);
			}
		}
		
		$new_transactions = array();
		foreach($this->transactions as $transaction)
		{
			// Analyse according to the following types for transactions:
		
			// VARER: 15.10 SHOP AND OTHER INFO
			// VAREKJØP: 26.02 SHOP AND OTHER INFO
			// VISA VARE: 1234567890000000 15.10 NOK 1234,00 FIRMA
			// VISA SET: 1234567890000000 15.10 NOK 100,00 FIRMA
			// VISA ELEKTRON: *1234 15.10 SEK 1234,56 FIRMA KURS: 0,1234
			// KONTANTUTTAK: *1234 15.10 SEK 1500,00 BANK NAME KURS: 0,1234
		
			// LØNN: Tekst
			// SKATT: Fra: SKATTEKONTOR Betalt: 15.10.09
			// INNSKUDD AUTOMAT: 15.10 SR-Bank Navn og adresse
		
			// MINIBANK: 15.10 SR-Bank Navn og adresse
			// MINIBANK-UTTAK I FREMMED BANK: 15.10 Navn på bank, adresse
		
			// NETTGIRO M/MELD. FORFALL I DAG: Nettgiro til: 1234.56.78901 Betalt: 15.10.09
			// NETTGIRO M/KID PÅ FORFALLSREG.: Nettgiro til: BEDRIFT AS Betalt: 15.10.09
			// NETTGIRO MED KID FORFALL I DAG: Nettgiro til: Bedrift Betalt: 15.10.09
			// NETTGIRO M/MELD. PÅ FORFALL: Nettgiro til: 1234.56.78901 Betalt: 15.10.09
			// NETTBANK OVERFØRSEL EGNE KONTI: Nettbank fra: NORDMANN OLA Betalt: 15.10.09
			// E-FAKTURA: Nettgiro til: 1234.56.78901 Betalt: 15.10.09
			// GIRO: Fra: Navn Betalt: 15.10.09
			// TELEGIRO I DAG M/MELDING: Telegiro fra: NAVN Betalt: 15.10.09
			
			// NETTBANK: Fra:12345678901
			// OVERFØRT TIL ANNEN KTO: Til:12345678901
			// OVERFØRSEL: FRA STATENS LÅNEKASSE FOR UTDANNIN
			// NETTBANK OVERFØRSEL EGNE KONTI: melding
			// AVTALEGIRO: AGREEMENT NAME 123456
			// OVERFØRSEL UTLAND: AB1234567890123A
		
			// GEBYR: KONTOHOLD
			// BRUKSRENTER: KREDITRENTER
			// OPPRETTING - Retur av for mye innbetalt på BSU-konto 12345678901. Fra:
			
			// VALUTA: Fra: Name
			// UTTAK: Optional message
		
			// TODO: fix oppretting
			$transaction['description'] = Model_Bankaccount_Importfile::
				replace_firstpart_if_found ($transaction['description'], 'OPPRETTING - ', 'OPPRETTING: ');
			$pos = strpos($transaction['description'], ':');
			$original_description = $transaction['description'];
			if($pos === false)
			{
				$transaction['type_csv'] = 'UNKNOWN';
			}
			else
			{
				$transaction['type_csv']     = substr($original_description, 0, $pos);
				$transaction['description']  = trim(substr($original_description, $pos+1));
				switch($transaction['type_csv'])
				{
					case 'VARER':
					case 'VAREKJØP':
					case 'INNSKUDD AUTOMAT':
					case 'MINIBANK':
					case 'MINIBANK-UTTAK I FREMMED BANK':
						// Format:
						// TYPE: 15.10 TEXT
						$transaction['date']         = 
							Model_Bankaccount_Importfile::
							getDateWithYear(substr($transaction['description'], 0, 5), $transaction['payment_date']);
						$transaction['description']  = trim(substr($transaction['description'], 5));
						break;
					case 'SKATT':
					case 'NETTGIRO M/MELD. FORFALL I DAG':
					case 'NETTGIRO M/KID PÅ FORFALLSREG.':
					case 'NETTGIRO MED KID FORFALL I DAG':
					case 'NETTGIRO M/MELD. PÅ FORFALL':
					case 'NETTBANK OVERFØRSEL EGNE KONTI':
					case 'E-FAKTURA':
					case 'GIRO':
					case 'TELEGIRO I DAG M/MELDING':
						// Format:
						// TYPE: TEXT Betalt: 15.10.09
						$betalt_pos = strpos($transaction['description'], 'Betalt: ');
						if($betalt_pos !== false) // Found "Betalt: "
						{
							$date_tmp = substr($transaction['description'], $betalt_pos+strlen('Betalt: '));
							if(substr($date_tmp, 6) >= 90) // year 1990-1999
								$date_tmp = substr($date_tmp, 0, 6).'19'.substr($date_tmp, 6);
							else // year 2000-2099
								$date_tmp = substr($date_tmp, 0, 6).'20'.substr($date_tmp, 6);
							$transaction['date']         = $date_tmp;
							$transaction['description']  = trim(substr($transaction['description'], 0, $betalt_pos));
						}
						break;
					case 'VISA VARE':
					case 'VISA SET':
					case 'VISA ELEKTRON':
					case 'KONTANTUTTAK':
						// Format:
						// TYPE: number date currency amount FromWho
					
						// Splitting: 1234567890000000 15.10 NOK 1234,00 Company AS
						// To array: array('1234567890000000', '15.10', 'NOK', '1234,00', 'Company AS')
						$parts = explode(' ', $transaction['description'], 5);
						if(count($parts) != 5) {
							break;
						}
					
						$transaction['date'] = 
							Model_Bankaccount_Importfile::
							getDateWithYear($parts[1], $transaction['payment_date']);
						$transaction['description'] = $parts[4];
						break;					
					case 'UTTAK':
						if($transaction['description'] == '')
							$transaction['description'] = __('No withdrawal message');
					case 'LØNN':
					case 'OVERFØRT TIL ANNEN KTO':
					case 'OVERFØRSEL':
					case 'GEBYR':
					case 'BRUKSRENTER':
					case 'AVTALEGIRO':
					case 'VALUTA':
					case 'NETTBANK':
					case 'OVERFØRSEL UTLAND':
					case 'OPPRETTING':
						break;
					
					case 'Fra':
						$transaction['type_csv'] = null;
						$transaction['description'] = $transaction['type_csv'].': '.$transaction['description'];
						break;
					
					default:
						if($transaction['description'] == '')
							$transaction['description'] = $transaction['type_csv'];
						else
							$transaction['description'] = $transaction['type_csv'].': '.$transaction['description'];
						throw new Kohana_Exception('Unknown transaction type. type_csv: :type_csv, description: :description',
							array(':type_csv' => $transaction['type_csv'], 
								':description' => $transaction['description']));
						$transaction['type_csv'] = 'UNKNOWN';
						
						break;
				}
			
				// Remove a few characters that we use in URIs
				$transaction['type_csv']     = str_replace('/', ' ', $transaction['type_csv']);
				$transaction['description']  = str_replace('/', ' ', $transaction['description']);
				$transaction['type_csv']     = str_replace('.', '',  $transaction['type_csv']);
				$transaction['description']  = str_replace('.', '',  $transaction['description']);
			}
			$new_transactions[] = $transaction;
		}
		
		$this->create_transactions($new_transactions);
		
		echo '<b>'.__('From').':</b> '.date('d-m-Y', $this->from).'</li><li>';
		echo '<b>'.__('To').':</b> '.date('d-m-Y', $this->to).'</li><li>';
		echo 
			__('Imported').': '.$this->transactions_new.', '.
			__('Already in database').': '.$this->transactions_already_imported.', '.
			__('No intrest date (not imported)').': '.$this->transactions_not_imported;

		
		$this->last_imported = time();
		if($this->loaded())
			$this->update();
		else
			$this->create();
	}
	
	
	function importFromSRbank_PDFFile ()
	{
		$statementparser = new sb1parser();
		$statementparser->importPDF(@file_get_contents($this->filepath, FILE_BINARY));
		
		foreach($statementparser->getAccounts() as $account)
		{
			$this->create_transactions($account['transactions']);
			
			echo '<li>';
			echo '<b>'.__('Bankaccount').':</b> '.$account['account_num'].', '.
				__('From').': '.date('d-m-Y', $account['accountoverview_start']).', '.
				__('To').': '.date('d-m-Y', $account['accountoverview_end']).'</li><li>';
			echo 
				__('Imported').': '.$this->transactions_new.', '.
				__('Already in database').': '.$this->transactions_already_imported.', '.
				__('No intrest date (not imported)').': '.$this->transactions_not_imported;
			echo '</li>';
			
			// Debugging:
			//echo '<tr><td><b>account_num:</b> '.$account['account_num'].'</td></tr>';
			//echo '<tr><td colspan="5">'; print_r($account); echo '</td></tr>';
			
			/*
			echo '<tr><td>accountoverview_num: '.$account['accountoverview_num'].'</td></tr>';
			echo '<tr><td>accountoverview_start: '.$account['accountoverview_start'].'</td></tr>';
			echo '<tr><td>accountoverview_end: '.$account['accountoverview_end'].'</td></tr>';
			echo '<tr><td>account_type: '.$account['account_type'].'</td></tr>';
			echo '<tr><td>accountoverview_balance_in: '.$account['accountoverview_balance_in'].'</td></tr>';
			echo '<tr><td>accountoverview_balance_out: '; var_dump($account['accountoverview_balance_out']); echo '</td></tr>';
			echo '<tr><td>control_amount: '; var_dump($account['control_amount']); echo '</td></tr>';
			*/
		}
	}
}
