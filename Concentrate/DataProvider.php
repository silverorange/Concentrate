<?php

require_once 'SymfonyComponents/YAML/sfYamlParser.php';
require_once 'Concentrate/Exception.php';

/**
 * @category  Tools
 * @package   Concentrate
 * @author    Michael Gauthier <mike@silverorange.com>
 * @copyright 2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class Concentrate_DataProvider
{
	protected $data = array();
	protected $pendingFiles = array();
	protected $loadedFiles = array();

	public function loadDataFile($filename)
	{
		$this->pendingFiles[] = $filename;
	}

	public function loadDataArray(array $data)
	{
		$this->data = array_merge_recursive($this->data, $data);
	}

	public function getData()
	{
		$this->loadPendingFiles();
		return $this->data;
	}

	protected function loadPendingFiles()
	{
		while (count($this->pendingFiles) > 0) {
			$filename = array_shift($this->pendingFiles);
			if (!in_array($filename, $this->loadedFiles)) {
				$this->loadPendingFile($filename);
			}
		}
	}

	protected function loadPendingFile($filename)
	{
		if (!is_readable($filename)) {
			throw new Concentrate_FileException(
				"Data file '{$filename}' can not be read.",0, $filename);
		}

		try {
			$data = sfYaml::load($filename);
			$this->loadedFiles[] = $filename;
		} catch (InvalidArgumentException $e) {
			throw new Concentrate_FileFormatException(
				"Data file '{$filename}' is not valid YAML.",0, $filename);
		}

		$this->loadDataArray($data);
	}
}

?>
