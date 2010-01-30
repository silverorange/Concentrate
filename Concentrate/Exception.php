<?php

class Concentrate_Exception extends Exception
{
}

class Concentrate_FileException extends Concentrate_Exception
{
	protected $filename = '';

	public function __construct($message, $code = 0, $filename = '')
	{
		parent::__construct($message, $code);
		$this->filename = $filename;
	}

	public function getFilename()
	{
		return $this->filename;
	}
}

class Concentrate_FileFormatException extends Concentrate_FileException
{
}

?>
