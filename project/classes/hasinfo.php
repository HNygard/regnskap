<?php

abstract class hasinfo extends Sprig
{
	private $_infocache;
	
	/**
	 * Get information about transaction
	 *
	 * @return array  Contains array of key=>value
	 */
	public function getInfo()
	{
		if(!isset($this->_infocache))
		{
			$this->_infocache = array();
			$query = DB::select()
				->from(strtolower($this->_model).'_infos')
				->where(strtolower($this->_model).'_id', '=', $this->id)
				->execute();
			foreach($query as $row)
			{
				$this->_infocache[$row['key']] = $row['value'];
				if($row['key'] == 'srbank_csv_description' || $row['key'] == 'srbank_pdf_description')
					$this->_infocache['srbank_description'] = $row['value'];
				if(
					($row['key'] == 'srbank_csv_type' || $row['key'] == 'srbank_pdf_type') &&
					$row['value'] != ''
				)
					$this->_infocache['srbank_type'] = $row['value'];
			}
			//$this->_infocache = Sprig::factory(ucfirst($this->_model).'_Info', array())->load($query, FALSE);
		}
		
		return $this->_infocache;
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
		foreach($info_db as $key => $value)
		{
			// ? Does info in database match updated info?
			if(isset($info[$key]) && $info[$key] == $value) {
				// -> Yes. Lets not save it
				unset($info[$key]);
			}
			elseif(isset($info[$key])) {
				// -> Does not match on value, but has the same key
				
				// Let give it a new random key
				$tmp_value = $info[$key];
				unset($info[$key]);
				$info[$key.time().rand(0,20)] = $tmp_value;
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
			$transaction->create();
		}
	}
	
	public function getInfoByKey($key)
	{
		foreach($this->getInfo() as $ikey => $value)
		{
			if($ikey == $key) {
				return $value;
			}
		}
		return NULL;
	}
}
