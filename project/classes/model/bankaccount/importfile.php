<?php defined('SYSPATH') or die('No direct script access.');

class Model_Bankaccount_Importfile extends Sprig {
	
	public $transactions = array();
	public $transactions_new = 0;
	public $transactions_not_imported = 0;
	public $transactions_already_imported = 0;
	
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
	
	public function create_transactions ($transactions, $contains_all_transactions_in_period = false, $period_from = null, $period_to = null)
	{
		$existing_transactions = array();
		$existing_transactions2 = array();
		
		// :? Creating transactions for a period of time?
		if(
			$contains_all_transactions_in_period &&
			!is_null($period_from) && !is_null($period_to)
		)
		{
			// -> Yes, lets match against all existing transactions
			$existing_transactions_query = DB::select('*')
				->from('bankaccount_transactions')
				->where('bankaccount_id', '=', $this->bankaccount_id)
				->where('date', '>=', $period_from)
				->where('date', '<=', $period_to)
				->as_object('Model_Bankaccount_Transaction')->execute();
			foreach($existing_transactions_query as $transaction) {
				// Building array with: id=>$transaction
				$existing_transactions[$transaction->id] = $transaction;
			}
		}
		else {
			// -> No, not given any period
			$contains_all_transactions_in_period = false;
		}
		
		$this->from  = null;
		$this->to    = null;
		$already_found = array(); // date_amount = number of
		$transactions_no_match = array();
		foreach($transactions as $transaction_array)
		{
			/*
			 * We have a match if:
			 * - Date is the same
			 * - Amount is the same
			 * - bankaccount_id is the same
			 */
			
			$selector = array(
					'date'            => $transaction_array['date'],
					'amount'          => $transaction_array['amount'],
					'bankaccount_id'  => $transaction_array['bankaccount_id'],
				);
			
			$transactions = Sprig::factory('bankaccount_transaction', $selector)->load(NULL, FALSE);
			$this_id = $transaction_array['date'].'_'.$transaction_array['amount'];
			
			$no_match = true;
			$matches_skipped_past = 0; // To support two transactions with the same amount and date
			foreach($transactions as $transaction)
			{
				// Transactions are matching on date and amount because of $selector
				if(isset($already_found[$this_id]) && $matches_skipped_past >= $already_found[$this_id])
				{
					// Multiple transactions with the same date and amount
					$matches_skipped_past++;
				}
				else
				{
					if(!isset($already_found[$this_id]))
						$already_found[$this_id] = 0;
					$already_found[$this_id]++;
					
					$no_match = false; // match found
					unset($existing_transactions[$transaction->id]);
					
					$this->transactions_already_imported++;
					//echo $transaction.' - '.__('Already in database');
					
					break;
				}
			}
			
			if($no_match)
			{
				// No match found
				// -> Save it for later matching
				$transactions_no_match[] = $transaction_array;
			}
			else
			{
				// Add/update transaction_info to database
				$transaction->updateInfo($transaction_array);
			
				if(is_null($this->from))
					$this->from = $transaction->date;
				elseif($this->from > $transaction->date)
					$this->from = $transaction->date; // Older transaction
			
				if(is_null($this->to))
					$this->to = $transaction->date;
				elseif($this->to < $transaction->date)
					$this->to = $transaction->date; // Newer transaction
			}
		}
		
		foreach($transactions_no_match as $transaction_array)
		{
			$no_match = true;
			foreach($existing_transactions as $transaction)
			{
				// :? Match with a margin of 4 days
				if(
					abs($transaction_array['date']-$transaction->date) < 4*24*60*60 &&
					$transaction_array['amount'] == $transaction->amount // Same amount
				)
				{
					$no_match = false; // match found
					unset($existing_transactions[$transaction->id]);
					
					$this->transactions_already_imported++;
					//echo $transaction.' - '.__('Already in database');
					
					break;
				}
			}
			
			if($no_match)
			{
				$transaction = Sprig::factory('bankaccount_transaction', $transaction_array);
				$transaction->create();
				$this->transactions_new++;
				
				echo '<tr><td>'.print_r($transaction_array, true).'</td></tr>';
			}
			
			// Add/update transaction_info to database
			$transaction->updateInfo($transaction_array);
			
			if(is_null($this->from))
				$this->from = $transaction->date;
			elseif($this->from > $transaction->date)
				$this->from = $transaction->date; // Older transaction
			
			if(is_null($this->to))
				$this->to = $transaction->date;
			elseif($this->to < $transaction->date)
				$this->to = $transaction->date; // Newer transaction
		}
		
		if(!is_null($period_from))
			$this->from = $period_from;
		if(!is_null($period_to))
			$this->to = $period_to;
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
					//echo __('One with no interest date, not importing.');
					//echo '<br />';
					$this->transactions_not_imported++;
					continue;
				}
				
				if(strlen($csv[0]) > 10) // 01.08.2008-00:00:00
					$csv[0] = substr($csv[0], 0, 10); // 01.08.2008
				
				if(strlen($csv[2]) > 10) // 01.08.2008-00:00:00
					$csv[2] = substr($csv[2], 0, 10); // 01.08.2008
				
				$this->transactions[] = array(
						'bankaccount_id'            => $this->bankaccount_id,
						'amount'                    => str_replace(',', '.', utf8::clean($csv[3])),
						'srbank_csv_payment_date'   => sb1helper::
						                               convert_stringDate_to_intUnixtime(utf8::clean($csv[0])),
						'srbank_csv_interest_date'  => sb1helper::
						                               convert_stringDate_to_intUnixtime(utf8::clean($csv[2])),
						'srbank_csv_description'    => utf8_encode($csv[1]),
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
			$transaction['srbank_csv_description'] = sb1helper::
				replace_firstpart_if_found ($transaction['srbank_csv_description'], 'OPPRETTING - ', 'OPPRETTING: ');
			$pos = strpos($transaction['srbank_csv_description'], ':');
			$original_description = $transaction['srbank_csv_description'];
			if($pos === false)
			{
				$transaction['srbank_csv_type'] = 'UNKNOWN';
			}
			else
			{
				$transaction['srbank_csv_type']     = substr($original_description, 0, $pos);
				$transaction['srbank_csv_description']  = trim(substr($original_description, $pos+1));
				switch($transaction['srbank_csv_type'])
				{
					case 'VARER':
					case 'VAREKJØP':
					case 'INNSKUDD AUTOMAT':
					case 'MINIBANK':
					case 'MINIBANK-UTTAK I FREMMED BANK':
						// Format:
						// TYPE: 15.10 TEXT
						$transaction['date']         = 
							sb1helper::
							convert_stringDate_to_intUnixtime(
								sb1helper::
								getDateWithYear(
									substr($transaction['srbank_csv_description'], 0, 5), 
									$transaction['srbank_csv_payment_date']
								)
							);
						$transaction['srbank_csv_description']  = 
							trim(substr($transaction['srbank_csv_description'], 5));
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
						$betalt_pos = strpos($transaction['srbank_csv_description'], 'Betalt: ');
						if($betalt_pos !== false) // Found "Betalt: "
						{
							$date_tmp = substr(
									$transaction['srbank_csv_description'], 
									$betalt_pos+strlen('Betalt: ')
								);
							if(substr($date_tmp, 6) >= 90) // year 1990-1999
								$date_tmp = substr($date_tmp, 0, 6).'19'.substr($date_tmp, 6);
							else // year 2000-2099
								$date_tmp = substr($date_tmp, 0, 6).'20'.substr($date_tmp, 6);
							$transaction['srbank_csv_payment_date']         = 
										sb1helper::
										convert_stringDate_to_intUnixtime($date_tmp);
							$transaction['srbank_csv_description']  =
								trim(substr($transaction['srbank_csv_description'], 0, $betalt_pos));
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
						$parts = explode(' ', $transaction['srbank_csv_description'], 5);
						if(count($parts) != 5) {
							break;
						}
					
						$transaction['srbank_csv_payment_date'] = 
							sb1helper::
							convert_stringDate_to_intUnixtime(
								sb1helper::
								getDateWithYear($parts[1], $transaction['srbank_csv_payment_date'])
							);
						$transaction['srbank_csv_description'] = $parts[4];
						break;					
					case 'UTTAK':
						if($transaction['srbank_csv_description'] == '')
							$transaction['srbank_csv_description'] = __('No withdrawal message');
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
					case 'TRAPPERENTE':
						break;
					
					case 'Fra':
						$transaction['srbank_csv_type'] = null;
						$transaction['srbank_csv_description'] = 
							$transaction['srbank_csv_type'].': '.$transaction['srbank_csv_description'];
						break;
					
					default:
						if($transaction['srbank_csv_description'] == '')
							$transaction['srbank_csv_description'] = $transaction['srbank_csv_type'];
						else
							$transaction['srbank_csv_description'] = 
								$transaction['srbank_csv_type'].': '.$transaction['srbank_csv_description'];
						throw new Kohana_Exception('Unknown transaction type. '.
							'srbank_csv_type: :srbank_csv_type, '.
							'srbank_csv_description: :srbank_csv_description',
							array(':srbank_csv_type' => $transaction['srbank_csv_type'], 
								':srbank_csv_description' => $transaction['srbank_csv_description']));
						$transaction['srbank_csv_type'] = 'UNKNOWN';
						
						break;
				}
			
				// Remove a few characters that we use in URIs
				$transaction['srbank_csv_type']         = str_replace('/', ' ', $transaction['srbank_csv_type']);
				$transaction['srbank_csv_description']  = str_replace('/', ' ', $transaction['srbank_csv_description']);
				$transaction['srbank_csv_type']         = str_replace('.', '',  $transaction['srbank_csv_type']);
				$transaction['srbank_csv_description']  = str_replace('.', '',  $transaction['srbank_csv_description']);
			}
			if($transaction['srbank_csv_type'] == 'VISA VARE') {
				// The PDF equivalent of these CSV transactions of these types
				// are not able to read the payment_date, use interest date instead
				// for better matching
				$transaction['date'] = $transaction['srbank_csv_interest_date'];
			} 
			else {
				$transaction['date'] = $transaction['srbank_csv_payment_date'];
			}
			
			if(isset($tranaction['srbank_csv_description']))
			{
				$transaction['srbank_csv_description'] = 
					str_replace('ß', 'ø', $transaction['srbank_csv_description']); // encoding fix
				$transaction['srbank_csv_description'] = 
					str_replace('¿', 'ø', $transaction['srbank_csv_description']); // encoding fix
			}
			
			$new_transactions[] = $transaction;
		}
		
		$this->create_transactions($new_transactions, true);
		
		echo '<b>'.__('From').':</b> '.date('d-m-Y', $this->from).'</li><li>';
		echo '<b>'.__('To').':</b> '.date('d-m-Y', $this->to).'</li><li>';
		echo 
			__('Imported').': '.$this->transactions_new.', '.
			__('Already in database').': '.$this->transactions_already_imported.', '.
			__('No interest date (not imported)').': '.$this->transactions_not_imported;

		
		$this->last_imported = time();
		if($this->loaded())
			$this->update();
		else
			$this->create();
	}
	
	function printAccountOverview ($account) {
		echo '<li>';
		echo '<b>'.__('Bankaccount').':</b> '.$account['account_num'].', '.
			__('From').': '.date('d-m-Y', $account['accountstatement_start']).', '.
			__('To').': '.date('d-m-Y', $account['accountstatement_end']).'</li>';
		echo 
			'<li>'.
				__('Account type').': '.$account['account_type'].', '.
				__('Statement number').': '.$account['accountstatement_num'].
			'</li>'.
			'<li>'.
				__('Balance in').': '.($account['accountstatement_balance_in']/100).', '.
				__('Balance out').': '.($account['accountstatement_balance_out']/100).
			'</li>'.
			'<li>'.
				__('Number of transactions').': '.count($account['transactions']).
			'</li>';
		echo '<li>'.__('Transactions').': <pre class="toggle" style="display: none;">';
		var_dump($account['transactions']);
		echo '</pre></li>';
	}
	
	function importFromSRbank_PDFFile ($create = true)
	{
		$all_accounts = array();
		$bankaccounts = Sprig::factory('bankaccount', array())->load(DB::select(), FALSE);
		foreach($bankaccounts as $bankaccount_tmp) {
			$all_accounts[$bankaccount_tmp->num] = $bankaccount_tmp->id;
		}
		
		$statementparser = new sb1parser();
		$statementparser->setAccountTranslation($all_accounts);
		$statementparser->importPDF(file_get_contents($this->filepath, FILE_BINARY));
		
		foreach($statementparser->getAccounts() as $account)
		{
			if($account['account_id'] == -1 && count($account['transactions'])) {
				$this->printAccountOverview($account);
				echo '<li>';
				if($account['account_id'] == -1) 
					echo 'Nothing imported, unknown account';
				else
					echo 'Nothing to import. No transactions found.';
				echo '</li>';
				
				continue;
			}
			foreach($account['transactions'] as $i => $a)
			{
				// Looping through transactions and renaming keys
				$account['transactions'][$i] = array(
						'bankaccount_id'  => $a['bankaccount_id'],
						'amount'          => $a['amount'],
						'date'            => $a['payment_date'], // The most accurate date
						'srbank_pdf_description'    => $a['description'],
						'srbank_pdf_interest_date'  => $a['interest_date'],
						'srbank_pdf_payment_date'   => $a['payment_date'],
						'srbank_pdf_type'           => $a['type'],
					);
				
				if(isset($transaction_array['srbank_pdf_description']))
				{
					$transaction_array['srbank_pdf_description'] = 
						str_replace('ß', 'ø', $transaction_array['srbank_pdf_description']); // encoding fix
					$transaction_array['srbank_pdf_description'] = 
						str_replace('¿', 'ø', $transaction_array['srbank_pdf_description']); // encoding fix
				}
			}
			if($create) {
				$importfile = Sprig::factory('Bankaccount_Importfile', array(
						'filepath' => $this->filepath,
						'bankaccount_id' => $account['account_id'],
					))->load();
				$importfile->create_transactions(
						$account['transactions'],
						true, 
						$account['accountstatement_start'], 
						$account['accountstatement_end']
					);
			}
			
			$this->printAccountOverview($account);
			if($create) {
				echo '<li>';
				echo 
					__('Imported').': '.$importfile->transactions_new.', '.
					__('Already in database').': '.$importfile->transactions_already_imported.', '.
					__('No interest date (not imported)').': '.$importfile->transactions_not_imported;
				echo '</li>';
			}
			
			if($create) {
				try {
					$importfile->last_imported = time();
					if($importfile->loaded())
						$importfile->update();
					else
						$importfile->create();
				} catch (Validation_Exception $e) {
					var_dump($e->array->errors());
				}
			}
		}
	}
	
	public function importFromGenericCSVFile()
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
					//echo __('One with no interest date, not importing.');
					//echo '<br />';
					$this->transactions_not_imported++;
					continue;
				}
				
				if(strlen($csv[0]) > 10) // 01.08.2008-00:00:00
					$csv[0] = substr($csv[0], 0, 10); // 01.08.2008
				
				if(strlen($csv[2]) > 10) // 01.08.2008-00:00:00
					$csv[2] = substr($csv[2], 0, 10); // 01.08.2008
				
				$this->transactions[] = array(
						'bankaccount_id'            => $this->bankaccount_id,
						'date'                      => sb1helper::
						                               convert_stringDate_to_intUnixtime(utf8::clean($csv[0])),
						'csv_description'           => trim($csv[1]),
						'amount'                    => str_replace(',', '.', utf8::clean($csv[2])),
					);
			}
		}
		
		$this->create_transactions($this->transactions, true);
		
		echo '<b>'.__('From').':</b> '.date('d-m-Y', $this->from).'</li><li>';
		echo '<b>'.__('To').':</b> '.date('d-m-Y', $this->to).'</li><li>';
		echo 
			__('Imported').': '.$this->transactions_new.', '.
			__('Already in database').': '.$this->transactions_already_imported.', '.
			__('No interest date (not imported)').': '.$this->transactions_not_imported;

		
		$this->last_imported = time();
		if($this->loaded())
			$this->update();
		else
			$this->create();
	}
	public function print_tree ($item, $spaces)
	{
		if($item->nodeName == '#text') {
			$name = $item->nodeName;
			$content = $item->textContent.'<br>';
		}
		else {
			$name = $item->nodeName;
			$content = '';
		}
		
		echo $spaces.'&lt;'.$name.'&gt;<br>'.$content;
		if($item->hasChildNodes()) {
			foreach($item->childNodes as $item2)
			{
				$this->print_tree($item2, $spaces.' &nbsp;'.' &nbsp;'.' &nbsp;');
			}
		}
		echo $spaces.'&lt;/'.$name.'&gt;<br>';
	}
	public function importFromKolumbusReisekonto()
	{
		if(!isset($this->filepath) || $this->filepath == '')
		{
			throw new Kohana_Exception('Filepath not set.');
		}
		
		// Read file
		$dom = new DOMDocument();
		$dom->loadHTMLFile($this->filepath);
		$treeContainingData =
			$dom->getElementsByTagName('form')
			->item(0) // <form name="travelAccountForm" (...)
			->childNodes->item(1) // <table width="100%" (...)
			->childNodes->item(0) // <tbody>
			->childNodes->item(0) // <tr>
			->childNodes->item(0) // <td>
			;
		
		/*
		$this->print_tree(
			$treeContainingData
			,'');*/
		
		/*
		$treeContainingData has
			- text node first (empty)
			- <table>
				- This table contains some text strings with data
				- Can use regex
					SALDO: 123,45
					(Sist oppdatert DD.MM.YYYY kl. HH:MM)
			- <table><tbody>
				- First <tr> is info
				- Second <tr> contains headings for each column
				- Next table rows contains the real data until a row containing
				  "Søket viser de nyeste bevegelsene. Skriv inn dato her for eldre transaksjoner."
		 */
		$first_table =
			str_replace("\t", '', // Remove tabs
			str_replace("\r", '',
			str_replace("\n", ' ', // Fix line endings
				$treeContainingData
				->childNodes->item(1)->textContent
			)));
		preg_match_all(
			'/'.
				// SALDO: 123,45
				'SALDO\:.([0-9\,]*).'.
				'*'.
				// (Sist oppdatert DD.MM.YYYY kl. HH:MM)
				'\(Sist.oppdatert.([0-9]{2}).([0-9]{2}).([0-9]{4}).kl..([0-9]{2}).([0-9]{2})\)'.
			'/', $first_table, $matches);
		$balance = str_replace(',', '.', $matches[1][0]); // "Saldo"
		$last_updated = mktime(
			$matches[5][0], // hour
			$matches[6][0], // minutes
			0, // seconds
			$matches[3][0], // month
			$matches[2][0], // day
			$matches[4][0]); // year
		
		// :: WE NOW HAVE
		// $balance
		// $last_updated		
		
		$second_table = $treeContainingData
				->childNodes->item(2) // The second table
				->childNodes->item(0); // Go into the <tbody>, foreach know returns <tr>
		$keys = array();
		$data = array();
		foreach($second_table->childNodes as $i => $tr) {
			if($i == 0) { // Ignore first <tr>
				continue;
			}
			elseif($i == 1) { // Second <tr>, contains table heading
				//$this->print_tree($tr, '');
				$j = 0;
				foreach($tr->childNodes as $td) {
					if($td->nodeName != '#text') {
						if($j != 0) {
							$keys[$j] = 
								// Strip the key
								str_replace("\t", '',
								str_replace("\r", '',
								str_replace("\n", '',
								str_replace(" ", '',
									$td->textContent
								))));
						}
						$j++;
					}
				}
			} else {
				$data[$i] = array();
				$j = 0;
				foreach($tr->childNodes as $td) {
					if($td->nodeName != '#text') {
						if($j != 0) {
							$data[$i][$keys[$j]] = 
								// Strip the key
								str_replace("\t", '',
								str_replace("\r", '',
								str_replace("\n", '',
									trim($td->textContent)
								)));
						}
						$j++;
					}
				}
				if(!count($data[$i])) {
					unset($data[$i]);
				}
			}
		}
		
		
		// :: WE NOW HAVE
		// $balance
		// $last_updated
		// $data
		
		$sum = 0;
		$transactions = array();
		$period_start = $last_updated;
		foreach($data as $row)
		{
			$row['Inn'] = str_replace(',', '.', $row['Inn']);
			$row['Ut'] = str_replace(',', '.', $row['Ut']);
			$amount = $row['Inn']-$row['Ut'];
			
			$date = sb1helper::convert_stringDate_to_intUnixtime(utf8::clean($row['Dato']));
			
			$transactions[] = array(
					'bankaccount_id'         => $this->bankaccount_id,
					'date'                   => $date,
					'amount'                 => $amount,
					'kolumbus_transaction'   => trim($row['Transaksjon']),
					'kolumbus_owner'         => trim($row['Korteier']),
				);
			
			$sum += $amount;
			if($period_start > $date)
				$period_start = $date;
		}
		
		if($sum != $balance) {
			throw new Exception ('Sum of the transactions found is not the same as balance for the account');
		}
		
		$this->create_transactions($transactions, true, $period_start, $last_updated);
		
		echo '<b>'.__('From').':</b> '.date('d-m-Y', $this->from).'</li><li>';
		echo '<b>'.__('To').':</b> '.date('d-m-Y', $this->to).'</li><li>';
		echo 
			__('Imported').': '.$this->transactions_new.', '.
			__('Already in database').': '.$this->transactions_already_imported.', '.
			__('No interest date (not imported)').': '.$this->transactions_not_imported;

		
		$this->last_imported = time();
		if($this->loaded())
			$this->update();
		else
			$this->create();
	}
}
