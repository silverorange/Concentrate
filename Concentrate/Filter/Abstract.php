<?php

require_once 'Concentrate/Path.php';

/**
 * @category  Tools
 * @package   Concentrate
 * @author    Michael Gauthier <mike@silverorange.com>
 * @copyright 2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class Concentrate_Filter_Abstract
{
	/**
	 * @var Concentrate_FilterAbstract
	 */
	protected $nextFilter = null;

	public function filter($input, $type = '')
	{
		$output = $this->filterImplementation($input, $type);

		if ($this->nextFilter instanceof Concentrate_FilterAbstract) {
			$output = $this->nextFilter->filter($output, $type);
		}

		return $output;
	}

	public function filterFile($fromFilename, $toFilename, $type = '')
	{
		if (!is_readable($fromFilename)) {
			throw new Concentrate_FileException(
				"Could not read {$fromFilename} for filtering.",
				0,
				$fromFilename
			);
		}

		$path = new Concentrate_Path($toFilename);
		$path->writeDirectory();

		$content = file_get_contents($fromFilename);
		$content = $this->filter($content, $type);
		file_put_contents($toFilename, $content);

		return $this;
	}

	public function setNextFilter(Concentrate_Filter_Abstract $filter)
	{
		$this->nextFilter = $filter;
	}

	public function clearNextFilter()
	{
		$this->nextFilter = null;
	}

	abstract protected function filterImplementation($input, $type = '');
}

?>
