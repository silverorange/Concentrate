<?php

require_once 'Console/CommandLine.php';
require_once 'Concentrate/Concentrator.php';
require_once 'Concentrate/DataProvider.php';
require_once 'Concentrate/DataProvider/FileFinderPear.php';

class Concentrate_CLI
{
	/**
	 * @var Console_CommandLine
	 */
	protected $parser = null;

	/**
	 * @var Concentrate_Concentrator
	 */
	protected $concentrator = null;

	public function run()
	{
		$this->parser = Console_CommandLine::fromXmlFile($this->getUiXml());

		try {
			$result = $this->parser->parse();
			var_dump($result);
		} catch (Console_CommandLine_Exception $e) {
			$this->displayError($e->getMessage() . PHP_EOL);
		}
	}

	protected function getUiXml()
	{
		$dir = '@data-dir@' . DIRECTORY_SEPARATOR
			. '@package-name@' . DIRECTORY_SEPARATOR . 'data';

		if ($dir[0] == '@') {
			$dir = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..'
				. DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..'
				. DIRECTORY_SEPARATOR . 'data';
		}

		return $dir . DIRECTORY_SEPARATOR . 'cli.xml';
	}

	protected function display($string)
	{
		$this->parser->outputter->stdout($string);
	}

	protected function displayError($string, $code = 1, $exit = true)
	{
		$this->parser->outputter->stderr($string);
		if ($exit) {
			exit($code);
		}
	}
}

?>
