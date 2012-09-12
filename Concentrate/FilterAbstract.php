<?php

/**
 * @category  Tools
 * @package   Concentrate
 * @author    Michael Gauthier <mike@silverorange.com>
 * @copyright 2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class Concentrate_FilterAbstract
{
	/**
	 * @var Concentrate_FilterAbstract
	 */
	protected $nextFilter = null;

	public function filter($input)
	{
		$output = $this->filterImplementation($input);

		if ($this->nextFilter instanceof Concentrate_FilterAbstract) {
			$output = $this->nextFilter->filter($output);
		}

		return $output;
	}

	public function filterFile($fromFilename, $toFilename)
	{
		if (!is_readable($fromFilename)) {
			throw new Concentrate_FileException(
				"Could not read {$fromFilename} for filtering.",
				0,
				$fromFilename
			);
		}

		$this->writeDirectory($toFilename);

		$content = file_get_contents($fromFilename);
		$content = $this->filter($content);
		file_put_contents($toFilename, $content);

		return $this;
	}

	public function setNextFilter(Concentrate_FilterAbstract $filter)
	{
		$this->nextFilter = $filter;
	}

	abstract protected function filterImplementation($input);

	protected function writeDirectory($toFilename)
	{
		$toDirectory = dirname($toFilename);
		if (!file_exists($toDirectory)) {
			mkdir($toDirectory, 0770, true);
		}
		if (!is_dir($toDirectory)) {
			throw new Concentrate_FileException(
				"Could not write to directory {$toDirectory} because it " .
				"is not a directory.",
				0,
				$toDirectory
			);
		}
	}
}

?>
