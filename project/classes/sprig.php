<?php defined('SYSPATH') or die('No direct script access.');

abstract class Sprig extends Sprig_Core {
	
	/**
	 * If model is not loaded, try to load it or cast an exception
	 * 
	 * @param  string  Optional exception message
	 */
	public function loadOrThrowException($exceptionMsg = null)
	{
		if(!$this->loaded())
		{
			$this->load();
			
			if(!$this->loaded())
			{
				if(is_null($exceptionMsg))
				{
					throw new Kohana_Exception('Object not found');
				}
				else
				{
					throw new Kohana_Exception($exceptionMsg);
				}
			}
		}
		
		return $this;
	}
	
	/**
	 * If model is not loaded, throw an exception
	 * 
	 * @param  string  Optional exception message
	 */
	public function loadedOrThrowException($exceptionMsg = null)
	{
		if(!$this->loaded())
		{
			if(is_null($exceptionMsg))
			{
				throw new Kohana_Exception('Object not found');
			}
			else
			{
				throw new Kohana_Exception($exceptionMsg);
			}
		}
		
		return $this;
	}
}
