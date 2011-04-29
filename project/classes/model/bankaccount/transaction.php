<?php defined('SYSPATH') or die('No direct script access.');

class Model_Bankaccount_Transaction extends Sprig {
	
	protected function _init()
	{
		$this->_fields += array(
			'id' => new Sprig_Field_Auto(array(
			)),
			'bankaccount_id' => new Sprig_Field_Integer(array(
			)),
			'payment_date' => new Sprig_Field_Timestamp(array(
			)),
			'intrest_date' => new Sprig_Field_Timestamp(array(
			)),
			'description' => new Sprig_Field_Char(array(
			)),
			'amount' => new Sprig_Field_Float(array(
			)),
			'imported' => new Sprig_Field_Boolean(array(
				'editable'        => false,
				'default'         => false,
			)),
			'imported_automatically' => new Sprig_Field_Boolean(array(
				'editable'        => false,
				'default'         => false,
			)),
			
			
			'srbank_type' => new Sprig_Field_Char(array(
				'in_db'  => false,
				'empty'  => true,
			)),
			'srbank_date' => new Sprig_Field_Timestamp(array(
				'in_db'  => false,
				'empty'  => true,
			)),
			'srbank_text' => new Sprig_Field_Char(array(
				'in_db'  => false,
				'empty'  => true,
			)),
			
			'autoimport_account_id' => new Sprig_Field_Integer(array(
				'in_db'  => false,
				'empty'  => true,
			)),
		);
	}
	
	public function __toString()
	{
		return 'transaction_'.date('Y-m-d', $this->payment_date).': '.$this->amount;
	}
	
	/**
	 * Analyse a transaction from SR-bank
	 *
	 * Sets srbank_type, srbank_date and srbank_text
	 */
	public function analyse_srbank()
	{
		// Analyse according to the following types for transactions:
		
		// VARER: 15.10 SHOP AND OTHER INFO
		// VISA VARE: 1234567890000000 15.10 NOK 1234,00 FIRMA
		// VISA SET: 1234567890000000 05.11 NOK 100,00 ICE.NET
		
		// LØNN: Tekst
		// SKATT: Fra: SKATTEKONTOR Betalt: 15.10.09
		// INNSKUDD AUTOMAT: 15.10 SR-Bank Navn og adresse
		
		// MINIBANK: 15.10 SR-Bank Navn og adresse
		// MINIBANK-UTTAK I FREMMED BANK: 15.10 Navn på bank, adresse
		
		// NETTGIRO M/MELD. FORFALL I DAG: Nettgiro til: 1234.56.78901 Betalt: 15.10.09
		// NETTBANK OVERFØRSEL EGNE KONTI: Nettbank fra: NORDMANN OLA Betalt: 15.10.09
		// NETTGIRO M/KID PÅ FORFALLSREG.: Nettgiro til: BEDRIFT AS Betalt: 15.10.09
		// NETTGIRO MED KID FORFALL I DAG: Nettgiro til: Bedrift Betalt: 15.10.09
		// OVERFØRT TIL ANNEN KTO: Til:12345678901
		// OVERFØRSEL: FRA STATENS LÅNEKASSE FOR UTDANNIN
		// NETTBANK OVERFØRSEL EGNE KONTI: melding
		
		// GEBYR: KONTOHOLD
		// OPPRETTING - Retur av for mye innbetalt på BSU-konto 12345678901. Fra:
		
		$this->loadedOrThrowException();
		
		$pos = strpos($this->description, ':');
		if($pos === false)
		{
			$this->srbank_type = 'UNKNOWN';
		}
		else
		{
			$this->srbank_type = substr($this->description, 0, $pos);
			$this->srbank_text = trim(substr($this->description, $pos+1));
			switch($this->srbank_type)
			{
				case 'VARER':
				case 'INNSKUDD AUTOMAT':
				case 'MINIBANK':
				case 'MINIBANK-UTTAK I FREMMED BANK':
					// Format:
					// TYPE: 15.10 TEXT
					
					
					
					$this->srbank_date = $this->getDateWithYear(substr($this->srbank_text, 0, 5));
					$this->srbank_text = trim(substr($this->srbank_text, 5));
					break;
				case 'SKATT':
				case 'NETTGIRO M/MELD. FORFALL I DAG':
				case 'NETTBANK OVERFØRSEL EGNE KONTI':
				case 'NETTGIRO M/KID PÅ FORFALLSREG.':
				case 'NETTGIRO MED KID FORFALL I DAG':
					// Format:
					// TYPE: TEXT Betalt: 15.10.09
					$betalt_pos = strpos($this->srbank_text, 'Betalt: ');
					if($betalt_pos !== false) // Found "Betalt: "
					{
						$date_tmp = substr($this->srbank_text, $betalt_pos+strlen('Betalt: '));
						if(substr($date_tmp, 6) >= 90) // year 1990-1999
							$date_tmp = substr($date_tmp, 0, 6).'19'.substr($date_tmp, 6);
						else // year 2000-2099
							$date_tmp = substr($date_tmp, 0, 6).'20'.substr($date_tmp, 6);
						$this->srbank_date = $date_tmp;
						$this->srbank_text = trim(substr($this->srbank_text, 0, $betalt_pos));
					}
					break;
				case 'VISA VARE':
				case 'VISA SET':
					// Format:
					// TYPE: number date currency amount FromWho
					
					// Splitting: 1234567890000000 15.10 NOK 1234,00 Company AS
					// To array: array('1234567890000000', '15.10', 'NOK', '1234,00', 'Company AS')
					$parts = explode(' ', $this->srbank_text, 5);
					if(count($parts) != 5) {
						break;
					}
					
					$this->srbank_date = $this->getDateWithYear($parts[1]);
					$this->srbank_text = $parts[4];
					break;
			}
			
			// Remove a few characters that we use in URIs
			$this->srbank_type = str_replace('/', ' ', $this->srbank_type);
			$this->srbank_text = str_replace('/', ' ', $this->srbank_text);
			$this->srbank_type = str_replace('.', '',  $this->srbank_type);
			$this->srbank_text = str_replace('.', '',  $this->srbank_text);
		}
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
	public function getDateWithYear ($tmp)
	{
		// Adding year
		if(
			($tmp == '31.12' || $tmp == '30.12' || $tmp == '29.12') &&
			date('m', $this->payment_date) == '01'
		)
		{
			$tmp = $tmp.'.'.(date('Y', $this->payment_date)-1);
		}
		else
		{
			$tmp = $tmp.'.'.date('Y', $this->payment_date);
		}
		return $tmp;
	}
	
	/**
	 * Can this bank transaction be automatically imported?
	 * 
	 * @return  boolean
	 */
	public function canAutoimport()
	{
		if(!isset($this->srbank_text))
			return false;
		if(!isset($this->srbank_type))
			return false;
		
		$autoimport = Sprig::factory('bankaccount_autoimport', 
			array(
				'text' => $this->srbank_text,
				'type' => $this->srbank_type,
			))->load(); // TODO: Multiple
		$autoimports = array();
		$autoimports_errors = array();
		if(!$autoimport->loaded())
		{
			
		}
		else
		{
			$autoimports_errors[$autoimport->id] = array();
			if(
				!is_null($autoimport->bankaccount_id) && 
				$autoimport->bankaccount_id != $this->bankaccount_id
			)
			{
				// Account spesific and wrong account
				$autoimports_errors[$autoimport->id][] = 'bankaccount_id';
			}
			
			if(
				!is_null($autoimport->amount_max) &&
				$autoimport->amount_max < $this->amount
			)
			{
				// Over the limited amount
				$autoimports_errors[$autoimport->id][] = 'amount_max';
			}
			
			if(
				!is_null($autoimport->amount_min) &&
				$autoimport->amount_min > $this->amount
			)
			{
				// Under the limited amount
				$autoimports_errors[$autoimport->id][] = 'amount_min';
			}
			
			// TODO: move $time stuff to __get('time'), also used in autoimport()
			// Checking date
			if(!is_null($this->srbank_date))
				$time = $this->srbank_date;
			else
				$time = $this->payment_date;
			
			if(
				!is_null($autoimport->time_max) &&
				$autoimport->time_max < $time
			)
			{
				// Over the limited date
				$autoimports_errors[$autoimport->id]['time_max'] = 
					array('is' => $time, 'max was' => $autoimport->time_max);
			}
			if(
				!is_null($autoimport->time_min) &&
				$autoimport->time_min > $time
			)
			{
				// Under the limited date
				$autoimports_errors[$autoimport->id]['time_min'] = 
					array('is' => $time, 'min was' => $autoimport->time_min);
			}
			
			// FOUND A MATCH
			if(!count($autoimports_errors[$autoimport->id]))
			{
				$this->autoimport_account_id = $autoimport->account_id;
				$autoimports[$autoimport->id] = $autoimport;
			}
		}
		
		if(count($autoimports))
		{
			return true;
		}
		else
		{
			//print_r($autoimports_errors);
			return false;
		}
	}
	
	/**
	 * Automatically import this bank transaction
	 */
	public function autoimport ()
	{
		// Need to be loaded
		$this->loadedOrThrowException();
		
		// Already imported?
		if($this->imported)
			return false;
		
		// Checking that there is not a transaction out there
		$transaction_check = Sprig::factory('transaction', array(
				'bankaccount_transaction_id' => $this->id,
			))->load();
		if($transaction_check->loaded())
			return false;
		
		// Analyse this SR-bank transaction
		$this->analyse_srbank();
		
		// Can it be automatically imported?
		if(!$this->canAutoimport())
		{
			return false;
		}
		
		// Creating
		if(!is_null($this->srbank_date))
			$time = $this->srbank_date;
		else
			$time = $this->payment_date;
		
		$transactions = Sprig::factory('transaction',
			array(
				'account_id'                  => $this->autoimport_account_id,
				'description'                 => $this->description,
				'amount'                      => $this->amount,
				'time'                        => $time,
				'bankaccount_transaction_id'  => $this->id,
				'imported_automatically'      => true,
			))->create();
		
		$this->imported = true;
		$this->imported_automatically = true;
		$this->update();
		
		return true;
	}
}
