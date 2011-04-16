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
	
	// TODO: make a comment here for this method:
	public function valuesFromHtmlForm($data)
	{
		$values = array();
		foreach($this->fields() as $field_name => $field)
		{
			if(!$field->editable)
				continue;
			
			if(isset($data[$field_name]))
			{
				// Getting data from post
				$values[$field_name] = $data[$field_name];
			}
			else
			{
				// Default value
				$values[$field_name] = $field->default;
			}
		}
		$this->values($values);
		
		return $this;
	}
}
