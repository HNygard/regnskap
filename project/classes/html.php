<?php defined('SYSPATH') or die('No direct script access.');

class HTML extends Kohana_HTML {
	
	/**
	 * Formating a sum of money.
	 * Adds 2 decimals and spaces on thousands 
	 *
	 * @param string    $money
	 * @return string   Formatted sum of money
	 */
	public static function money ($money)
	{
		return number_format(
			$money,
			2, // number of decimals
			',', // decimal character
			' ' // thousands seperater
			);
	}
	
	/**
	 * Success message
	 * 
	 * @param  String  The message
	 */
	public static function msg_success ($msg)
	{
		return '<div class="success">'.$msg.'</div>';
	}
	
	/**
	 * Warning message
	 * 
	 * @param  String  The message
	 */
	public static function msg_warning ($msg)
	{
		return '<div class="notice">'.$msg.'</div>';
	}
	
	/**
	 * Error message
	 * 
	 * @param  String  The message
	 */
	public static function msg_error ($msg)
	{
		return '<div class="error">'.$msg.'</div>';
	}
}