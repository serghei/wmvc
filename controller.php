<?php

class Controller
{

	public function index()
	{
		header("HTTP/1.0 404 Not Found");
		echo "Error 404: Page not found (index not implemented).";
	}


	public $routes;
	public $req_uri;
	public $uri;

}
