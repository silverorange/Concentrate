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

	public function __construct(...$directories)
	{
		$this->setDirectory(...$directories);
	}

	public function setDirectory(...$directories)
	{
		$this->directory = null;

		foreach ($directories as $directory) {
			// Try to create it in parent dir if it does not exist.
			if (!is_dir($directory) && is_writeable(dirname($directory))) {
				mkdir($directory, 0770, true);
			}

			// Check if dir exists and is writeable. If so, use it.
			if (is_dir($directory) && is_writeable($directory)) {
				$this->directory = $directory;
				break;
			}
		}

		return $this;
	}

	public function exists($key)
	{
		if ($this->directory === null) {
			return false;
		}

		$filePath = $this->getFilePath($key);
		return (file_exists($filePath) && is_readable($filePath));
	}

	public function write($key, $fromFilename)
	{
		if (is_dir($this->directory)) {
			copy($fromFilename, $this->getFilePath($key));
			return true;
		}

		return false;
	}

	public function copyTo($key, $toFilename)
	{
		if ($this->exists($key)) {
			$directory = dirname($toFilename);
			if (!file_exists($directory) && is_writeable(dirname($directory))) {
				mkdir($directory, 0770, true);
			}
			if (is_writeable($directory)) {
				copy($this->getFilePath($key), $toFilename);
				return true;
			}
		}

		return false;
	}

	protected function getFilePath($key)
	{
		return $this->directory . DIRECTORY_SEPARATOR . $key;
	}
}

?>
