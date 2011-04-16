<?php defined('SYSPATH') or die('No direct script access.');

class Model_Bankaccount_Importfile extends Sprig {
	
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
	
	public function importFromFile()
	{
		if(!isset($this->filepath) || $this->filepath == '')
		{
			throw new Kohana_Exception('Filepath not set.');
		}
		
		// Parser filer
		$csv_array = csv_to_array(explode("\n", file_get_contents($this->filepath)));
		
		$new = 0; // Nye
		$already_imported = 0; // Allerede inne
		$not_imported = 0; // 
		
		$this->from  = null;
		$this->to    = null;
		
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
					$not_imported++;
					continue;
				}
				
				if(strlen($csv[0]) > 10) // 01.08.2008-00:00:00
					$csv[0] = substr($csv[0], 0, 10); // 01.08.2008
				
				if(strlen($csv[2]) > 10) // 01.08.2008-00:00:00
					$csv[2] = substr($csv[2], 0, 10); // 01.08.2008
				
				$transaction = Sprig::factory('bankaccount_transaction',
						array(
							'bankaccount_id' => $this->bankaccount_id,
							'payment_date'   => utf8::clean($csv[0]),
							'intrest_date'   => utf8::clean($csv[2]),
							'description'    => utf8_encode($csv[1]),
							'amount'         => str_replace(',', '.', utf8::clean($csv[3])),
						)
					)->load();
				if($transaction->loaded())
				{
					$already_imported++;
					//echo $transaction.' - '.__('Already in database');
				}
				else
				{
					$transaction->create();
					$new++;
					//echo $transaction.' - '.__('Not imported jet');
				}
				//echo '<br>';
				
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
		echo '<b>'.__('From').':</b> '.date('d-m-Y', $this->from).'</li><li>';
		echo '<b>'.__('To').':</b> '.date('d-m-Y', $this->to).'</li><li>';
		echo 
			__('Imported').': '.$new.', '.
			__('Already in database').': '.$already_imported.', '.
			__('No intrest date (not imported)').': '.$not_imported;

		
		$this->last_imported = time();
		if($this->loaded())
			$this->update();
		else
			$this->create();
	}
}
