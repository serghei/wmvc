<?php

class Email
{
	public $from;
	public $to;
	public $subject;
	public $message;


	function __construct()
	{
		$this->from = new stdClass;
		$this->to = new stdClass;
	}


	function send()
	{
		// From:
		$from_addr = $this->from->addr;
		$from_name = mb_encode_mimeheader($this->from->name, "UTF-8", "Q");

		// To:
		$to_addr = $this->to->addr;
		$to_name = mb_encode_mimeheader($this->to->name, "UTF-8", "Q");

		// Subject:
		$subject = mb_encode_mimeheader($this->subject, "UTF-8", "Q");

		// Headers
		$headers  = "From: {$from_name} <{$from_addr}>\r\n";
		$headers .= "MIME-Version: 1.0\r\n";
		$headers .= "Content-type: text/html; charset=UTF-8\r\n";

		mail("{$to_name} <{$to_addr}>", $subject, $this->message, $headers, "-f{$from_addr}");
	}
}
