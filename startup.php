<?php

function decamelize($class_name)
{
	return ltrim(strtolower(preg_replace('/[A-Z]/', '-$0', $class_name)), '-');
}

spl_autoload_register(function ($class)
{
	$this_dir = dirname(__FILE__);

	if(strlen($class) > 10 && "Controller" == substr($class, -10))
		$file = decamelize(substr($class, 0, -10)) . ".ctrl.php";
	elseif(strlen($class) > 5 && "Model" == substr($class, -5))
		$file = decamelize(substr($class, 0, -5)) . ".mdl.php";
	elseif(strlen($class) > 4 && "View" == substr($class, -4))
		$file = decamelize(substr($class, 0, -4)) . ".view.php";
	else
		$file = decamelize($class) . ".php";

	if(defined("__CONTROLLER_PATH__") && file_exists("$this_dir/../app/" . __CONTROLLER_PATH__ . "/{$file}"))
		require "$this_dir/../app/" . __CONTROLLER_PATH__ . "/{$file}";
	elseif(file_exists("$this_dir/../app/{$file}"))
		require "$this_dir/../app/{$file}";
	elseif(file_exists("$this_dir/{$file}"))
		require "$this_dir/{$file}";
	else
	{
		error_log("Unable to autoload class $class: \"$file\" not found");
		header("HTTP/1.0 404 Not Found");
		die("Error 404: Page not found.");
	}
});


// default php settings
mb_internal_encoding("UTF-8");


App::run();
