<?php

abstract class hasinfo extends Sprig
{

	/**
	 * Get information about transaction
	 *
	 * @return array  Contains array of bankaccount_transaction_info
	 */
	public function getInfo()
	{
		$query = DB::select()->where(strtolower($this->_model).'_id', '=', $this->id);
		return Sprig::factory(ucfirst($this->_model).'_Info', array())->load($query, FALSE);
	}
	
	/**
	 * Update information about object.
	 * Checks for duplicates and removes amount, date, bankaccount_id
	 * 
	 * @param array
	 */
	public function updateInfo($info)
	{
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
			$transaction = Sprig::factory(ucfirst($this->_model).'_Info', 
					array(
						strtolower($this->_model).'_id' => $this->id,
						'key'    => $a,
						'value'  => $i,
					)
				);
			echo 'New: '.$a.'='.$i.'<br>'.chr(10);
			$transaction->create();
		}
	}
	
	private $_infocache;
	public function getInfoByKey($key)
	{
		
		if(!isset($this->_infocache))
			$this->_infocache = $this->getInfo();
		foreach($this->_infocache as $ikey => $value)
		{
			if($ikey == $key) {
				return $value;
			}
		}
		return NULL;
	}
}
