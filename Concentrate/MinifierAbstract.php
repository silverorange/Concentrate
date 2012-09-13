<?php

require_once 'Concentrate/Exception.php';
require_once 'Concentrate/Path.php';

/**
 * @category  Tools
 * @package   Concentrate
 * @author    Michael Gauthier <mike@silverorange.com>
 * @copyright 2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class Concentrate_MinifierAbstract
{
	abstract public function minify($content, $type);

	public function minifyFile($fromFilename, $toFilename, $type)
	{
		if (!is_readable($fromFilename)) {
			throw new Concentrate_FileException(
				"Could not read {$fromFilename} for minification.",
				0,
				$fromFilename
			);
		}

		$path = new Concentrate_Path($toFilename);
		$path->writeDirectory();

		$content = file_get_contents($fromFilename);
		$content = $this->minify($content, $type);
		file_put_contents($toFilename, $content);
	}
}

?>
