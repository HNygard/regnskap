<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Getfile extends Kohana_Controller_Template
{
	public function before()
	{
		$this->auto_render = false;
		return parent::before();
	}
	
	public function action_transactionfiles ($path) {
		$path = preg_replace("/[^A-Za-z0-9\(\)\_\,\-\/\.\s]/", "", $path);
		$path = str_replace('..', '', $path);
		
		$path = Controller_Import::$transactionfiles_main_folder.'/'.$path;
		if(file_exists($path)) {
			// Check if the browser sent an "if-none-match: <etag>" header, and tell if the file hasn't changed
			$this->response->check_cache(sha1($this->request->uri()).filemtime($path), $this->request);

			// Send the file content as the response
			$this->response->body(file_get_contents($path));

			// Set the proper headers to allow caching
			$this->response->headers('content-type',  File::mime_by_ext(pathinfo($path, PATHINFO_EXTENSION)));
			$this->response->headers('last-modified', date('r', filemtime($path)));
		}
		else {
			// Return a 404 status
			$this->response->status(404);
		}
	}
}
