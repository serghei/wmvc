<?php

class Tools
{

	static public function randomString($len)
	{
		$source = $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

		while($len > strlen($source))
			$source .= $chars;

		return substr(str_shuffle($source), 0, $len);
	}

}
