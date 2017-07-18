<?php

/**
 * @category  Tools
 * @package   Concentrate
 * @author    Michael Gauthier <mike@silverorange.com>
 * @copyright 2010-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class Concentrate_FileCache
{
	/**
	 * @var string
	 */
	protected $directory;

	public function __construct($directory)
	{
		$this->setDirectory($directory);
	}

	public function setDirectory($directory)
	{
		$this->directory = $directory;
		return $this;
	}

	public function exists($key)
	{
		$filePath = $this->getFilePath($key);
		return (file_exists($filePath) && is_readable($filePath));
	}

	public function write($key, $fromFilename)
	{
		if (!is_dir($this->directory) && is_writeable(dirname($this->directory))) {
			mkdir($this->directory, 0770, true);
		}

		if (is_dir($this->directory) && is_writeable($this->directory)) {
			$filePath = $this->getFilePath($key);
			copy($fromFilename, $filePath);
			return true;
		}

		return false;
	}

	public function copyTo($key, $toFilename)
	{
		if ($this->exists($key)) {
			$directory = dirname($toFilename);
			if (!file_exists($directory)) {
				mkdir($directory, 0770, true);
			}
			copy($this->getFilePath($key), $toFilename);
			return true;
		}

		return false;
	}

	protected function getFilePath($key)
	{
		return $this->directory . DIRECTORY_SEPARATOR . $key;
	}
}

?>
