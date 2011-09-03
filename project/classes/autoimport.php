<?php

class autoimport 
{
	public static $autos;
	public static function getAll(){
		if(!isset(self::$autos))
		{
			self::$autos = DB::select('*')
				->from('bankaccount_autoimports')
				//->where('bankaccount_id', '=', $this->bankaccount_id)
				//->where('date', '>=', $period_from)
				//->where('date', '<=', $period_to)
				->as_object('Model_Bankaccount_Autoimport')->execute()->as_array();	
		}
		
		return self::$autos;
	}
	
}
