<?php defined('SYSPATH') or die('No direct script access.');

class Model_Bankaccount_Transaction extends hasinfo {
	
	protected function _init()
	{
		$this->_fields += array(
			'id' => new Sprig_Field_Auto(array(
			)),
			'bankaccount_id' => new Sprig_Field_Integer(array(
				
			)),
			'date' => new Sprig_Field_Timestamp(array(
				'empty'    => true,
				'default'  => null,
				'null'     => true,
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
	
	public $autoimport_debug = false;
	/**
	 * Can this bank transaction be automatically imported?
	 * 
	 * @return  boolean
	 */
	public function canAutoimport()
	{
		$autoimports = array();
		$autoimports_errors = array();
		foreach(autoimport::getAll() as $autoimport)
		{
			$autoimports_errors[$autoimport->id] = array();
			if(
				!is_null($autoimport->bankaccount_id) && 
				$autoimport->bankaccount_id != $this->bankaccount_id
			)
			{
				// Account spesific and wrong account
				if(!$this->autoimport_debug) {
					continue;
				}
				$autoimports_errors[$autoimport->id][] = 'bankaccount_id';
			}
			
			if(
				!is_null($autoimport->amount_max) &&
				$autoimport->amount_max < $this->amount
			)
			{
				// Over the limited amount
				if(!$this->autoimport_debug) {
					continue;
				}
				$autoimports_errors[$autoimport->id][] = 'amount_max';
			}
			
			if(
				!is_null($autoimport->amount_min) &&
				$autoimport->amount_min > $this->amount
			)
			{
				// Under the limited amount
				if(!$this->autoimport_debug) {
					continue;
				}
				$autoimports_errors[$autoimport->id][] = 'amount_min';
			}
			
			if(
				!is_null($autoimport->time_max) &&
				$autoimport->time_max < $this->date
			)
			{
				// Over the limited date
				if(!$this->autoimport_debug) {
					continue;
				}
				$autoimports_errors[$autoimport->id]['time_max'] = 
					array('is' => $this->date, 'max was' => $autoimport->time_max);
			}
			if(
				!is_null($autoimport->time_min) &&
				$autoimport->time_min > $this->date
			)
			{
				// Under the limited date
				if(!$this->autoimport_debug) {
					continue;
				}
				$autoimports_errors[$autoimport->id]['time_min'] = 
					array('is' => $this->date, 'min was' => $autoimport->time_min);
			}
			
			// Match on autoimport_info
			$keyvalue_match = true;
			$this_info = $this->getInfo();
			foreach($autoimport->getInfo() as $key => $value)
			{
				if(
					!isset($this_info[$key]) ||
					strtolower($value) != strtolower($this_info[$key])
				) {
					if($this->autoimport_debug) {
						$autoimports_errors[$autoimport->id][] = $key;
					}
					$keyvalue_match = false;
					break;
				}
			}
			
			// FOUND A MATCH
			if($keyvalue_match && !count($autoimports_errors[$autoimport->id]))
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
		//$this->analyse_srbank();
		
		// Can it be automatically imported?
		if(!$this->canAutoimport())
		{
			return false;
		}
		
		// Description hack
		$info = $this->getInfo();
		if(isset($info['srbank_description'])) { // Common for CSV and PDF import from SRbank
			$description = $info['srbank_description'];
		} elseif(isset($info['csv_description'])) { // Generic description
			$description = $info['csv_description'];
		} elseif(isset($info['kolumbus_transaction']) && 
			isset($info['kolumbus_owner']) && $info['kolumbus_owner'] != '') {
			$description = $info['kolumbus_owner'].': '.$info['kolumbus_transaction'];
		} elseif(isset($info['kolumbus_transaction'])) {
			$description = $info['kolumbus_transaction'];
		}
		else {
			$description = '';
		}
		
		// Creating
		$transactions = Sprig::factory('transaction',
			array(
				'account_id'                  => $this->autoimport_account_id,
				'description'                 => $description,
				'amount'                      => $this->amount,
				'time'                        => $this->date,
				'bankaccount_transaction_id'  => $this->id,
				'imported_automatically'      => true,
			))->create();
		
		// Update bankaccount transaction
		$this->imported = true;
		$this->imported_automatically = true;
		$this->update();
		
		return true;
	}
	
	/**
	 * Gets type from type_pdf or type_csv or returns null
	 * 
	 * @return  string/null
	 */
	public function getType ()
	{
		if(!is_null($this->type_pdf) && $this->type_pdf != '')
			return $this->type_pdf;
		elseif(!is_null($this->type_csv) && $this->type_csv != '')
			return $this->type_csv;
		else
			return null;
	}
	
	public function updateInfo($info)
	{
		// Unset info saved directly in object ($this)
		unset($info['id']);
		unset($info['amount']);
		unset($info['date']);
		unset($info['bankaccount_id']);
		
		parent::updateInfo($info);
	}
	
	public function getInfoForDisplay () {
		$info = $this->getInfo();
		unset($info['srbank_pdf_description']);
		unset($info['srbank_pdf_intrest_date']);
		unset($info['srbank_pdf_payment_date']);
		unset($info['srbank_pdf_type']);
		unset($info['srbank_csv_payment_date']);
		unset($info['srbank_csv_interest_date']);
		unset($info['srbank_csv_description']);
		unset($info['srbank_csv_type']);
		
		return $info;
	}
}
