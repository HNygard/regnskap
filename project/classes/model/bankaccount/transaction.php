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
			
			
			'srbank_type' => new Sprig_Field_Char(array(
				'in_db'  => false,
				'empty'  => true,
			)),
			'srbank_date' => new Sprig_Field_Char(array(
				'in_db'  => false,
				'empty'  => true,
			)),
			'srbank_text' => new Sprig_Field_Char(array(
				'in_db'  => false,
				'empty'  => true,
			)),
		);
	}
	
	public function __toString()
	{
		return 'transaction_'.date('Y-m-d', $this->payment_date).': '.$this->amount;
	}
	
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
					$this->srbank_date = substr($this->srbank_text, 0, 5);
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
						$this->srbank_date = substr($this->srbank_text, $betalt_pos+strlen('Betalt: '));
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
					
					$this->srbank_date = $parts[1];
					$this->srbank_text = $parts[4];
					break;
			}
		}
	}
}
