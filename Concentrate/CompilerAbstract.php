<?php

/**
 * @category  Tools
 * @package   Concentrate
 * @author    Michael Gauthier <mike@silverorange.com>
 * @copyright 2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class Concentrate_CompilerAbstract
{
	abstract public function compile($content, $type);

	public function compileFile($fromFilename, $toFilename, $type)
	{
		if (!is_readable($fromFilename)) {
			throw new Concentrate_FileException(
				"Could not read {$fromFilename} for compilation.",
				0,
				$fromFilename
			);
		}

		$path = new Concentrate_Path($toFilename);
		$path->writeDirectory();

		$content = file_get_contents($fromFilename);
		$content = $this->compile($content, $type);
		file_put_contents($toFilename, $content);

		return $this;
	}
}

?>
