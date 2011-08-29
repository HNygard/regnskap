<?php defined('SYSPATH') or die('No direct script access.');

class Model_Bankaccount_Transaction extends Sprig {
	
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
	
	/**
	 * Analyse a transaction from SR-bank
	 *
	 * Sets srbank_type, srbank_date and srbank_text
	 */
	public function analyse_srbank()
	{
		$this->loadedOrThrowException();
		
		if(!is_null($this->type_csv))
			$this->srbank_type = $this->type_csv;
		elseif(!is_null($this->type_pdf))
			$this->srbank_type = $this->type_pdf;
		else
			$this->srbank_type = null;
		$this->srbank_date = $this->date;
		$this->srbank_text = $this->description;
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
		
		$query = DB::select()
				->where('text', '=', $this->srbank_text)
				->where('type', '=', $this->srbank_type)
			;
		$autoimport_possibles = Sprig::factory('bankaccount_autoimport', array())->load($query, FALSE);
		
		$autoimports = array();
		$autoimports_errors = array();
		foreach($autoimport_possibles as $autoimport)
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
	
	/**
	 * Get information about transaction
	 *
	 * @return array  Contains array of bankaccount_transaction_info
	 */
	public function getInfo()
	{
		$query = DB::select()->where('bankaccount_transaction_id', '=', $this->id);
		return Sprig::factory('Bankaccount_Transaction_Info', array())->load($query, FALSE);
	}
	
	/**
	 * Update information about transaction.
	 * Checks for duplicates and removes amount, date, bankaccount_id
	 * 
	 * @param array
	 */
	public function updateInfo($info)
	{
		// Unset info saved directly in transaction ($this)
		unset($info['amount']);
		unset($info['date']);
		unset($info['bankaccount_id']);

		// Checking against saved information
		$info_db = $this->getInfo();
		foreach($info_db as $i)
		{
			// ? Does info in database match updated info?
			if(isset($info[$i->key]) && $info[$i->key] == $i->value) {
				// -> Yes. Lets not save it
				unset($info[$i->key]);
			}
		}
		
		// -> Every piece of info in $info is now unique
		
		foreach($info as $a => $i)
		{
			// Save info to database
			$transaction = Sprig::factory('Bankaccount_Transaction_Info', 
					array(
						'bankaccount_transaction_id' => $this->id,
						'key'    => $a,
						'value'  => $i,
					)
				);
			$transaction->create();
		}
	}
}
