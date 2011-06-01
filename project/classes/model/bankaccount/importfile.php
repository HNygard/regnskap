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
	
	/**
	 * Convert a string with kroner to ører in integer
	 *
	 * @param   string   Amount, format: 1.234,93
	 * @return  integer  Ører
	 */
	static function stringKroner_to_intOerer($amount)
	{
		/*$amount =
			(int)
			(str_replace(',', '.',
			str_replace(' ', '',
			str_replace('.', '', $td[2])))*100);*/
		
		//$amount = $td[2]; // (string) "1.234,93"
		$amount = str_replace('.', '', $amount); // (string) "1234,93"
		$amount = str_replace(' ', '', $amount); //
		$amount = str_replace(',', '.', $amount); // (string) "1234.93"
		
		// Making integer
		$tmp = explode('.', $amount, 2);
		if(count($tmp) == 2)
			$amount = (int)(
					((int)$tmp[0]*100)+
					$tmp[1]
				);
		else
			$amount = (int)$tmp[0];
		//$amount = $amount*100; // (float) 123493
		//$amount = (int)$amount; // (int) 123493
		
		return $amount;
	}
	
	/**
	 * Convert a string with date to unix time
	 *
	 * @param   string   Date, format: 3112 or 31.12.2011
	 * @param   integer  Year, optional. Needed if format is 3112
	 * @return  integer  Unix time
	 */
	static function convert_stringDate_to_intUnixtime ($date, $year = null)
	{
		if(strlen($date) == strlen('31.12.2011'))
		{
			$parts = explode('.', $date);
			if(count($parts) == 3)
			{
				return mktime (0, 0, 0, $parts[1], $parts[0], $parts[2]);
			}
		}
		elseif(
			strlen($date) == strlen('3112') &&
			!is_null($year)
		)
		{
			return mktime (0, 0, 0, substr($date, 2, 2), substr($date, 0, 2), $year);
		}
		throw new Kohana_Exception ('Unknown date format: :date', array(':date' => $date));
	}
	
	/**
	 * Get the date including the year
	 * 
	 * If the transaction is done on a friday, saturday or sunday the date
	 * on the recite will differ from payment_date.
	 *
	 * @param  String  Date with format "dd.mm"
	 * @return String  Date with format "dd.mm.YYYY"
	 */
	public static function getDateWithYear ($tmp, $unixtime_paymentdate)
	{
		// Adding year
		if(
			($tmp == '31.12' || $tmp == '30.12' || $tmp == '29.12') &&
			date('m', $unixtime_paymentdate) == '01'
		)
		{
			$tmp = $tmp.'.'.(date('Y', $unixtime_paymentdate)-1);
		}
		else
		{
			$tmp = $tmp.'.'.date('Y', $unixtime_paymentdate);
		}
		return $tmp;
	}
	
	private static $type_pdf;
	static function remove_firstpart_if_found_and_set_type_pdf ($description, $search, $type_pdf_if_matched)
	{
		if(substr($description, 0, strlen($search)) == $search)
		{
			$description = substr($description, strlen($search));
			Model_Bankaccount_Importfile::$type_pdf = $type_pdf_if_matched;
		}
		return $description;
	}
	static function replace_firstpart_if_found ($description, $search, $replace_with)
	{
		if(substr($description, 0, strlen($search)) == $search)
		{
			$description = $replace_with.substr($description, strlen($search));
		}
		return $description;
	}
	
	function importFromSRbank_PDFFile ()
	{
		// Creator "Exstream Dialogue Version 5.0.051" should work
		// Creator "HP Exstream Version 7.0.605" should work
		
		pdf2textwrapper::pdf2text($this->filepath); // Returns the text, but we are using the table
		
		if(!count(pdf2textwrapper::$table))
		{
			throw new Kohana_Exception('PDF parser failed. Unable to read any lines.');
		}
		
		$next_is_balance_in   = false;
		$next_is_balance_out  = false;
		$next_is_fee          = false;
		$next_is_transactions = false;
		
		$accounts = array(); // Can be multiple accounts per file
		$last_account = null;
		foreach(pdf2textwrapper::$table as $td_id => $td)
		{
			if(!is_array($td))
				continue;
			
			// Checking and fixing multiline transactions
			if
			(
				$next_is_transactions &&
				(
					count($td) == 5 || // Personal accounts
					(
						// Business accounts
						count($td) == 7 &&
						is_numeric($td[5]) && // 123321123
						is_numeric(substr($td[6],1)) // *123123123
					) ||
					(
						// Business accounts
						count($td) == 6 &&
						is_numeric($td[5]) // 123321123
					)
				) &&
				is_numeric($td[2]) && strlen($td[2]) == 4 && // ddmm, intrest_date
				is_numeric(
					str_replace(',', '.',
					str_replace(' ', '',
					str_replace('.', '', $td[3])))) && // amount
				is_numeric($td[4]) && strlen($td[4]) == 4// ddmm, payment_date
			)
			{
				$td_tmp = $td;
				$td = array();
				$td[0] = $td_tmp[0].' '.$td_tmp[1];
				$td[1] = $td_tmp[2];
				$td[2] = $td_tmp[3];
				$td[3] = $td_tmp[4];
				if(isset($td_tmp[5]))
					$td[4] = $td_tmp[5];
				if(isset($td_tmp[6]))
					$td[5] = $td_tmp[6];
			}
			
			if(
				// Line with
				// Example data: Kontoutskrift nr. 2 for konto 1234.12.12345 i perioden 01.02.2011 - 28.02.2011 Alltid Pluss 18-34
				count($td) == 1 &&
				substr($td[0], 0, strlen('Kontoutskrift nr. ')) == 'Kontoutskrift nr. '
			)
			{
				preg_match('/Kontoutskrift nr. (.*) for konto (.*) i perioden (.*) - (.*)/', $td[0], $parts);
				if(count($parts) == 5)
				{
					$accountoverview_num    = $parts[1]; // 2
					$account_num            = $parts[2]; // 1234.12.12345
					$accountoverview_start  = Model_Bankaccount_Importfile::
					                          convert_stringDate_to_intUnixtime (
					                         $parts[3]); // 01.02.2011
					$parts  = explode(' ',$parts[4], 2); // 28.02.2011 Alltid Pluss 18-34
					$accountoverview_end    = Model_Bankaccount_Importfile::
					                          convert_stringDate_to_intUnixtime (
					                         $parts[0]); // 28.02.2011
					$account_type           = $parts[1]; // Alltid Pluss 18-34
					
					$last_account = $account_num.'_'.$accountoverview_start;
					$tmp = Sprig::factory('bankaccount', array('num' => $account_num))->load();
					if(!$tmp->loaded())
						$last_account_id = -1;
					else
						$last_account_id = $tmp->id;
					
					// If account spans over several pages, the heading repeats
					if(!isset($accounts[$last_account]))
					{
						$accounts[$last_account] = array(
							'accountoverview_num'    => $accountoverview_num,
							'account_id'             => $last_account_id,
							'account_num'            => $account_num,
							'accountoverview_start'  => $accountoverview_start,
							'accountoverview_end'    => $accountoverview_end,
							'account_type'           => $account_type,
							'transactions'           => array(),
							'control_amount'         => 0,
						);
						//echo '<tr><td>Account: <b>'.$account_num.'</b></td></tr>';
					}
					
					$next_is_fee = false;
					$next_is_transactions = true;
				}
			}
			elseif(
				// Checking for a row with transaction
				(
					count($td) == 4 || // Personal accounts
					(
						// Business accounts
						count($td) == 6 &&
						is_numeric($td[4]) && // 123321123
						is_numeric(substr($td[5],1)) // *123123123
					) ||
					(
						// Business accounts
						count($td) == 5 &&
						is_numeric($td[4]) // 123321123
					)
				) &&
				is_numeric($td[1]) && strlen($td[1]) == 4 && // ddmm, intrest_date
				is_numeric(
					str_replace(',', '.',
					str_replace(' ', '',
					str_replace('.', '', $td[2])))) && // amount
				is_numeric($td[3]) && strlen($td[3]) == 4// ddmm, payment_date
			)
			{
				$amount = Model_Bankaccount_Importfile::stringKroner_to_intOerer($td[2]);
				
				$pos_amount        = pdf2textwrapper::$table_pos[$td_id][1][2];
				$pos_payment_date  = pdf2textwrapper::$table_pos[$td_id][1][3];
				
				// If pos_amount is less than 365, the money goes out of the account
				// If pos_amount is more than 365, the money goes into the account
				if($pos_amount < 
					(
						365+ // pos if 0,00 goes out of the account
						19 // margin
					)
				)
				{
					$amount = -$amount;
				}
				
				$intrest_date = Model_Bankaccount_Importfile::
					convert_stringDate_to_intUnixtime ($td[1], date('Y', $accounts[$last_account]['accountoverview_end']));
				$payment_date = Model_Bankaccount_Importfile::
					convert_stringDate_to_intUnixtime ($td[3], date('Y', $accounts[$last_account]['accountoverview_end']));
				
				// Checking description for transaction type 
				// If found, add to type_pdf and match format for CSV
				$description = $td[0];
				Model_Bankaccount_Importfile::$type_pdf = '';
				$description = Model_Bankaccount_Importfile::
					remove_firstpart_if_found_and_set_type_pdf($description, 'Varer ', 'VARER'); // Varer => VARER:
				$description = Model_Bankaccount_Importfile::
					remove_firstpart_if_found_and_set_type_pdf($description, 'Lønn ', 'LØNN'); // Lønn => LØNN:
				$description = Model_Bankaccount_Importfile::
					remove_firstpart_if_found_and_set_type_pdf($description, 'Minibank ', 'MINIBANK'); // Minibank => MINIBANK:
				$description = Model_Bankaccount_Importfile::
					remove_firstpart_if_found_and_set_type_pdf($description, 'Avtalegiro ', 'AVTALEGIRO'); // Avtalegiro => AVTALEGIRO:
				$description = Model_Bankaccount_Importfile::
					remove_firstpart_if_found_and_set_type_pdf($description, 'Overføring ', 'OVERFØRSEL'); // Overføring => Overførsel:
				$description = Model_Bankaccount_Importfile::
					remove_firstpart_if_found_and_set_type_pdf($description, 'Valuta ', 'VALUTA'); // Valuta => VALUTA:
				if(substr($description, 0, 1) == '*' && is_numeric(substr($description, 1, 4))) // *1234 = VISA VARE
				{
				}
				if($next_is_fee)
				{
					// 1 Kontohold => Kontohold
					$description = str_replace('1 Kontohold', 'Kontohold', $description);
					Model_Bankaccount_Importfile::$type_pdf = 'GEBYR';
				}
				
				if(Model_Bankaccount_Importfile::$type_pdf == 'VARER')
				{
					$description  = trim(substr($description, 5));
				}
				
				$accounts[$last_account]['control_amount'] += $amount;
				$accounts[$last_account]['transactions'][] = array(
						'bankaccount_id'  => $last_account_id,
						'description'     => $description,
						'intrest_date'    => $intrest_date,
						'amount'          => ($amount/100),
						'payment_date'    => $payment_date,
						'type_pdf'        => Model_Bankaccount_Importfile::$type_pdf,
					);
				/*
				echo '<tr>';
				echo '<td>'.$description.'</td>';
				echo '<td>'.date('d.m.Y', $intrest_date).'</td>';
				if($amount > 0)
					echo '<td>&nbsp;</td><td>'.($amount/100).'</td>';
				else
					echo '<td>'.($amount/100).'</td><td>&nbsp;</td>';
				echo '<td>'.date('d.m.Y', $payment_date).'</td>';
				
				echo '</tr>';/**/
				/*
				$this->transactions[] = array(
						'bankaccount_id' => $this->bankaccount_id,
						'payment_date'   => utf8::clean($csv[0]),
						'intrest_date'   => utf8::clean($csv[2]),
						'description'    => utf8_encode($csv[1]),
						'amount'         => str_replace(',', '.', utf8::clean($csv[3])),
					);*/
			}
			
			/*
			
			## Balance in ##
			
			Example data:
			    [3] => Array
				(
				    [0] => Saldo
				    [1] => frå
				    [2] =>  kontoutskrift
				    [3] => 31.01.2011
				)

			    [4] => Array
				(
				    [0] => 12.345,67
				)
			*/
			elseif(
				// Saldo frå kontoutskrift dd.mm.yyyy
				count($td) == 4 && 
				trim($td[0]) == 'Saldo' &&
				(trim($td[1]) == 'frå' || trim($td[1]) == 'fra') && // Nynorsk and bokmål
				trim($td[2]) == 'kontoutskrift'
			)
			{
				$next_is_balance_in = true;
			}
			elseif(
				// Balance in on this account overview
				$next_is_balance_in
			)
			{
				$accounts[$last_account]['accountoverview_balance_in'] = 
					Model_Bankaccount_Importfile::stringKroner_to_intOerer ($td[0]);
				$accounts[$last_account]['control_amount'] += $accounts[$last_account]['accountoverview_balance_in'];
				$next_is_balance_in = false;
			}
			
			/*
			
			## Balance out ##
			
			Example data:
			    [55] => Array
				(
				    [0] => S
				    [1] => a
				    [2] => l
				    [3] => d
				    [4] => o
				    [5] => i
				    [6] => D
				    [7] => y
				    [8] => k
				    [9] => k
				    [10] => a
				    [11] => r
				    [12] => f
				    [13] => a
				    [14] => v
				    [15] => ø
				    [16] => r
				)

			    [56] => Array
				(
				    [0] => 65.432,10
				)
			*/
			elseif(
				// Saldo i Dykkar favør
				(count($td) == 17 && implode($td) == 'SaldoiDykkarfavør') || // Nynorsk
				(count($td) == 16 && implode($td) == 'SaldoiDeresfavør') // Bokmål
			)
			{
				$next_is_balance_out   = true;
				$next_is_transactions  = false;
			}
			elseif(
				// Balance out on this account overview
				$next_is_balance_out
			)
			{
				$accounts[$last_account]['accountoverview_balance_out'] =
					Model_Bankaccount_Importfile::stringKroner_to_intOerer ($td[0]);
				$next_is_balance_out = false;
			}
			elseif(
				implode($td) == 'Kostnadervedbrukavbanktjenester:' ||  // Bokmål
				implode($td) == 'Kostnadervedbrukavbanktenester:'      // Nynorsk
			)
			{
				// The next detected transactions, if any, is fees
				$next_is_fee = true;
			}
			
			
			/*
			elseif($last_account && $accounts[$last_account]['accountoverview_num'] == '9')
			{
				// Debugging
				echo '<tr><td colspan="4">'.print_r($td, true).'</td></tr>';
			}
			/**/
			/*
			else
			{
				// Debugging
				echo '<tr><td colspan="4">'.implode('', $td).'</td></tr>';
			}
			/**/
		}
		
		foreach($accounts as $account)
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
			
			try
			{
				// Debugging:
				//echo '<tr><td><b>account_num:</b> '.$account['account_num'].'</td></tr>';
				//echo '<tr><td colspan="5">'; print_r($account); echo '</td></tr>';
				
				// Checking if all parameters have been found
				if(!isset($account['accountoverview_balance_in']))
					throw new Kohana_Exception('PDF parser failed. Can not find accountoverview_balance_in.');
				if(!isset($account['accountoverview_balance_out']))
					throw new Kohana_Exception('PDF parser failed. Can not find accountoverview_balance_out.');
				if(!isset($account['accountoverview_start']))
					throw new Kohana_Exception('PDF parser failed. Can not find accountoverview_start.');
				if(!isset($account['accountoverview_end']))
					throw new Kohana_Exception('PDF parser failed. Can not find accountoverview_end.');
				
				/*
				echo '<tr><td>accountoverview_num: '.$account['accountoverview_num'].'</td></tr>';
				echo '<tr><td>accountoverview_start: '.$account['accountoverview_start'].'</td></tr>';
				echo '<tr><td>accountoverview_end: '.$account['accountoverview_end'].'</td></tr>';
				echo '<tr><td>account_type: '.$account['account_type'].'</td></tr>';
				echo '<tr><td>accountoverview_balance_in: '.$account['accountoverview_balance_in'].'</td></tr>';
				echo '<tr><td>accountoverview_balance_out: '; var_dump($account['accountoverview_balance_out']); echo '</td></tr>';
				echo '<tr><td>control_amount: '; var_dump($account['control_amount']); echo '</td></tr>';
				*/
				
				// Checking if the found amount is the same as the control amount found on accountoverview
				// If not, the file is corrupt or parser has made a mistake
				if(round($account['control_amount'],2) != $account['accountoverview_balance_out'])
					throw new Kohana_Exception('PDF parser failed. Controlamount is not correct. '.
						'Controlamount, calculated: '.$account['control_amount'].'. '.
						'Balance out should be: '.$account['accountoverview_balance_out'].'.');
			}
			catch (Exception $e)
			{
				//echo '<tr><td style="color: red" colspan="5">'.$e->getMessage().'</td></tr>';
				throw $e;
			}
		}
	}
}
