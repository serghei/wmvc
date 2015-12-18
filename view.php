<?php

class View
{
	protected $__tpl;
	protected $__data;


	function __construct($tpl, $data = null)
	{
		$this->__tpl = $tpl;
		$this->__data = $data;
	}


	function render()
	{
		$data = $this->__data;

		foreach(get_object_vars($this) as $key => $val)
			if("__data" !== $key)
			{
				if(is_object($val) && is_a($val, "View"))
					$$key = $val->render();
				else
					$$key = $val;
			}

		// send to view the method of request
		$is_post = ("POST" == $_SERVER["REQUEST_METHOD"]);

		ob_start();
		require dirname(__FILE__) . "/../tpl/{$this->__tpl}.php";

		return ob_get_clean();
	}


	function display()
	{
		$content = $this->render();

		// translation
		if(preg_match_all('/{([\^a-z0-9-]+)}/', $content, $matches))
		{
			$codes = array_unique($matches[1]);
			foreach($codes as $code)
			{
				if($replace = Dictionary::get($code))
					$content = str_replace("{" . $code . "}", $replace, $content);
			}
		}

		echo str_replace(array("\t", "\n\n"), "", $content);
	}

}
