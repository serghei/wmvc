<?php

class e
{

	// integer
	static function int($num)
	{
		return intval($num);
	}

	// number (can be integer, float, etc)
	static function num($num)
	{
		return ($num + 0);
	}

	// date
	static function date($date, $out_format = "d-m-Y", $in_format = "Y-m-d H:i:s")
	{
		$date = DateTime::createFromFormat($in_format, $date);
		if($date)
			return $date->format($out_format);
		return "";
	}

	// string
	static function str($str, $words_limit = 0)
	{
		if(!isset($str))
			return "";

		if($str && $words_limit)
		{
			$orig_len = strlen($str);
			$words = explode(" ", $str);
    		$str = implode(" ", array_splice($words, 0, $words_limit));
    		if($orig_len > strlen($str))
    			$str .= " [...]";
		}

		return htmlspecialchars($str);
	}

	// conditionally print
	static function cond($cond, $str)
	{
		if($cond)
			return $str;
		return "";
	}

	// pluralize
	static function pluralize($count, $singular, $plural)
	{
		if(1 == $count)
			return $singular;
		return $plural;
	}

	// display $_POST field
	static function post($field, $default = "")
	{
		if(isset($_POST[$field]))
			return htmlspecialchars($_POST[$field]);
		return htmlspecialchars($default);
	}


	// complex text
	static function text($text)
	{
		$content = "";
		$related = 0;
		$rel_tooltips = "";

		$lines = explode("\n", $text);
		$list = false;
		foreach($lines as $line)
		{
			$line = trim($line);
			$first_char = substr($line, 0, 1);

			if(!$list and "=" == $first_char)
				$content .= "<ul>";
			else if($list and "=" != $first_char)
				$content .= "</ul>";

			$list = ($first_char === "=");

			if($list)
				$text = "<li>" . htmlspecialchars(trim(substr($line, 1, 999))) . "</li>";
			else
				$text = "<p>" . htmlspecialchars($line) . "</p>";

			$text = str_replace("[**", "<span class=\"info\">", $text);
			$text = str_replace("**]", "</span>", $text);

			$text = str_replace("{{", "<em>", $text);
			$text = str_replace("}}", "</em>", $text);

			$text = str_replace("[[", "<strong>", $text);
			$text = str_replace("]]", "</strong>", $text);

			// process related
			if('%' == $first_char)
			{
				list($head, $id_prods) = explode('|', trim(substr($line, 1, 999)));
				MySQL::query("SELECT id, cat, link, brand, title, brief, price FROM products WHERE shown=1 AND id IN ($id_prods) ORDER BY FIELD (id, $id_prods)");

				if(0 == MySQL::rows())
					continue;

				$related++;
				$i = 0;
				$prods = array();
				while($row = MySQL::fetch())
				{
					list($id, $cat, $link, $brand, $title, $brief, $price) = $row;
					$prods[$i++] = array($id, $cat, $link, $brand, $title, $brief, $price);
				}


				if($_SERVER["REMOTE_ADDR"] == "109.99.148.134xx")
				{
					$text = '<div class="row"><div class="col-md-12">';
					$text .= '<div class="carousel slide">';
					$text .= '<div class="carousel-inner">';
					$text .= 'test';
					$text .= '</div></div></div></div>';
				}


				$text = "<strong>$head</strong><ul id=\"related$related\" class=\"related-skin\">";

				foreach($prods as $prod) {
					list($id, $cat, $link, $brand, $title, $brief, $price) = $prod;
					$text .= "<li id='rel_$id'>" . show_small_image($link, "");
					$text .= "<input type='hidden' value='/$cat/" . urlencode($link) . "'>";
					$text .= "<div class='title'>$title</div>";
					$text .= "<div class='brand'>$brand</div>";
					$text .= "<div class='price'><strong>" . ($price+0) . "</strong> LEI</div>";
					$text .= "</li>";

					$rel_tooltips .= "\t$('#rel_$id').poshytip({ content: '" . str_replace("'", "\\'", $brief) . "', className: 'tip-darkgray', showTimeout: 100 });\n";
				}

				$text .= "</ul>";
			}

			// process links
			$offset = 0;
			while(true)
			{
				$beg = strpos($text, "&lt;&lt;", $offset);
				if(false === $beg) break;

				$end = strpos($text, "&gt;&gt;", $beg);
				if(false === $end) $end = strlen($text);

				$text1 = substr($text, 0, $beg);
				$text2 = substr($text, $beg + 8, $end - $beg - 8);
				$text3 = substr($text, $end + 8);

				list($link, $text) = explode("|", $text2);
				if(!$text) $text = $uri;
				$text2 = "<a href='$link'>$text</a>";

				$text = $text1 . $text2 . $text3;

				$offset = $end;
			}

			$content .= $text;
		}

		if($line)
			$content .= "</ul>";

		if($related)
		{
			$content .= "\n" . '<script type="text/javascript">' . "\n";
			$content .= '$(document).ready(function() {' . "\n";
			for($i = 1; $i <= $related; $i++)
			{
				$content .= "\t" . '$("#related' . $i . '").jcarousel();' . "\n";
			}
			$content .= $rel_tooltips;
			$content .= "});\n</script>\n";
		}

		return $content;
	}
}
