<?php

class Image
{
	protected $is_valid = false;
	protected $info;
	protected $image;


	function __construct($filename)
	{
		$this->info = getimagesize($filename);

		if(false == $this->info)
		{
			error_log("Image(): unable to read the type of source image (invalid format?)");
			return;
		}

		if(!in_array($this->info[2], array(IMAGETYPE_JPEG, IMAGETYPE_PNG)))
		{
			error_log("Image: unsupported input image format ({$this->info[2]})");
			return false;
		}

		$image_data = file_get_contents($filename);

		if(false === $image_data)
		{
			error_log("Image(): unable to read the content of source image");
			return;
		}

		$this->image = imagecreatefromstring($image_data);

		if(false === $this->image)
		{
			error_log("Image(): unable to create image object (invalid format?)");
			return;
		}

		$this->is_valid = true;
	}


	function isValid()
	{
		return $this-is_valid;
	}


	function crop($x, $y, $w, $h, $target_w, $target_h)
	{
		if(!$this->is_valid)
			return false;

		// destination image
		$cropped_image = imagecreatetruecolor($target_w, $target_h);

		// crop & resize the image
		imagecopyresampled($cropped_image, $this->image, 0, 0, $x, $y, $target_w, $target_h, $w, $h);

		// replace the current image with the cropped one
		$this->image = $cropped_image;

		return true;
	}


	function output($base64 = true)
	{
		if(!$this->is_valid)
			return false;

		if($base64)
			ob_start();
		else
			header("Content-type: {$this->info['mime']}");

		// type of source image
		switch($this->info[2])
		{
			case IMAGETYPE_JPEG:
				imagejpeg($this->image, null, 85);
				break;
			case IMAGETYPE_PNG:
				imagepng($this->image, null, 9);
				break;
		}

		if($base64)
		{
			$image = ob_get_clean();
			echo "data:{$this->info['mime']};base64,", base64_encode($image);
		}

		return true;
	}


	function save($filename)
	{
		if(!$this->is_valid)
			return false;

		$supported_extensions = array("jpg", "jpeg", "png");
		$filename_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		if(!in_array($filename_ext, $supported_extensions))
		{
			error_log("Image::save($filename): unsupported output image format ($filename_ext)");
			return false;
		}

		switch($filename_ext)
		{
			case "jpg":
			case "jpeg":
				imagejpeg($this->image, $filename, 85);
				break;
			case "png":
				imagepng($this->image, $filename, 9);
				break;
		}

		return true;
	}


	static function fromDataToFile($data_url, $filename)
	{
		$data = file_get_contents($data_url);
		file_put_contents($filename, $data);
	}


	static function toDataURL($filename)
	{
	}


	static function resize($src_img_file, $dst_img_file, $new_w, $new_h = 0)
	{
		$supported_extensions = array("jpg", "jpeg", "png");
		$dst_img_ext = strtolower(pathinfo($dst_img_file, PATHINFO_EXTENSION));
		if(!in_array($dst_img_ext, $supported_extensions))
		{
			error_log("Image::resize($dst_img_file): unsupported output image format ($dst_img_ext)");
			return false;
		}

		$src_img_info = getimagesize($src_img_file);
		if(!$src_img_info)
			return false;

		list($src_w, $src_h, $src_type) = $src_img_info;

		switch($src_type)
		{
			case IMAGETYPE_JPEG:
				$src_img_object = imagecreatefromjpeg($src_img_file);
				break;
			case IMAGETYPE_PNG:
				$src_img_object = imagecreatefrompng($src_img_file);
				break;
			default:
				error_log("Image::resize($src_img_file): unsupported input image format ($src_img_info[mime])");
				return false;
		}


		$aspect_ratio = $src_w / $src_h;
		if(0 < $new_h && $new_w / $new_h > $aspect_ratio)
			$new_w = $new_h * $aspect_ratio;
		else
			$new_h = $new_w / $aspect_ratio;

		$dst_img_object = imagecreatetruecolor($new_w, $new_h);

		imagecopyresampled($dst_img_object, $src_img_object, 0, 0, 0, 0, $new_w, $new_h, $src_w, $src_h);

		switch($dst_img_ext)
		{
			case "jpg":
			case "jpeg":
				imagejpeg($dst_img_object, $dst_img_file, 85);
				break;
			case "png":
				imagepng($dst_img_object, $dst_img_file, 9);
				break;
		}

		$info = new stdClass;
		$info->w = $new_w;
		$info->h = $new_h;

		return $info;
	}
}
