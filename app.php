<?php

class App
{

	static public function run()
	{
		// get baseurl
		if(isset($_SERVER["BASEURL"]))
			$baseurl = $_SERVER["BASEURL"];
		else
			$baseurl = "/";
		define("__BASEURL__", $baseurl);

		// get url path
		$url_path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

		// strip the trailing slash
		$url_path = rtrim($url_path, "/");

		// strip the baseurl from url path
		$baseurl_pattern = str_replace("/", '\/', $baseurl);
		$url_path = preg_replace("/^{$baseurl_pattern}/", "", "{$url_path}/");

		// strip the trailing slash (again)
		$url_path = rtrim($url_path, "/");

		// search for apropriate controller
		$found_route = false;
		require dirname(__FILE__) . "/../cfg/routes.php";
		foreach($routes as $class_path => $url_patterns)
		{
			foreach($url_patterns as $url_pattern => $method)
			{
				if($url_pattern == $url_path || fnmatch($url_pattern, $url_path, FNM_PATHNAME))
				{
					$found_route = true;
					break;
				}
			}

			if($found_route)
				break;
		}

		// path to controller (if defined any path)
		$class_name = basename($class_path);
		if($class_name != $class_path)
			define("__CONTROLLER_PATH__", dirname($class_path));

		// instantiate controller object
		$class_name .= "Controller";
		$controller = new $class_name;

		// populate controller with properties
		$controller->baseurl = $baseurl;
		$controller->url_path = $url_path;
		$controller->is_post = ("POST" == $_SERVER["REQUEST_METHOD"]);

		// trim string POST variables
		if($controller->is_post)
		{
			foreach($_POST as $key => $var)
				if(is_string($var))
					$_POST[$key] = trim($var);
		}

		// capture arguments from URI, if any
		$args = array();
		if(false !== strpos($url_pattern, "*"))
		{
			$pattern = str_replace("/", '\/', $url_pattern);
			$pattern = str_replace("*", "(.*)", $pattern);
			preg_match("/$pattern/", $url_path, $args);
			array_shift($args);
		}

		// call initializaton method, if exists
		if(method_exists($controller, "__initialize"))
			$controller->__initialize();

		// call special POST method, if exists
		if($controller->is_post && method_exists($controller, "{$method}Post"))
			$method = "{$method}Post";

		if(method_exists($controller, $method))
		{
			// call method, with arguments
			call_user_func_array(array($controller, $method), $args);
		}
		else
		{
			error_log("Method not found for \"{$baseurl}{$url_path}\": {$class_name}::{$method}()");
			header("HTTP/1.0 404 Not Found");
			die("Error 404: Page not found.");
		}

		// call finalization method, if exists
		if(method_exists($controller, "__finalize"))
			$controller->__finalize();
	}

}
