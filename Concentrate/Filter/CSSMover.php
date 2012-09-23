<?php

require_once 'Concentrate/Path.php';
require_once 'Concentrate/Filter/Abstract.php';

/**
 * Updates CSS content with rebased relative URIs
 *
 * @category  Tools
 * @package   Concentrate
 * @author    Michael Gauthier <mike@silverorange.com>
 * @copyright 2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class Concentrate_Filter_CSSMover extends Concentrate_Filter_Abstract
{
	/**
	 * @var string
	 */
	protected $toPath = '';

	/**
	 * @var string
	 */
	protected $fromPath = '';

	public function __construct($fromPath, $toPath)
	{
		$this->setFromPath($fromPath);
		$this->setToPath($toPath);
	}

	public function setFromPath($fromPath)
	{
		$this->fromPath = (string)$fromPath;
		return $this;
	}

	public function setToPath($toPath)
	{
		$this->toPath = (string)$toPath;
		return $this;
	}

	protected function filterImplementation($input, $type = '')
	{
		return $this->updateURIs($input);
	}

	protected function updateURIs($content)
	{
		if ($this->fromPath != $this->toPath) {
			$content = preg_replace_callback(
				'/url\((.+?)\)/ui',
				array($this, 'updateURIsCallback'),
				$content
			);
		}
		return $content;
	}

	protected function updateURIsCallback(array $matches)
	{
		$uri    = $matches[1];
		$quoted = false;

		// check if the URI is quoted
		if (preg_match('/^[\'"].*[\'"]$/', $matches[1]) === 1) {
			$quoted = true;
			$uri = trim($matches[1], '\'"');
		}

		// check if it is a relative URI; if so, rewrite it
		if ($this->isRelative($uri)) {

			// evaluate to and from paths
			$fromPath = new Concentrate_Path(dirname($this->fromPath));
			$toPath   = new Concentrate_Path(dirname($this->toPath));

			$fromPath = (string)$fromPath->evaluate();
			$toPath   = (string)$toPath->evaluate();

			// strip common basedir
			$fromPathParts = explode('/', $fromPath);
			$toPathParts   = explode('/', $toPath);

			$length = min(count($fromPathParts), count($toPathParts));
			for ($i = 0; $i < $length; $i++) {
				if ($fromPathParts[$i] == $toPathParts[$i]) {
					array_shift($fromPathParts);
					array_shift($toPathParts);
					$length--;
					$i--;
				} else {
					break;
				}
			}

			$fromPath = implode('/', $fromPathParts);
			$toPath   = implode('/', $toPathParts);

			// get normalized relative URI
			$uri = new Concentrate_Path($fromPath . '/' . $uri);
			$uri = (string)$uri->evaluate();

			// add relative paths back to the root from the destination
			foreach (explode('/', $toPath) as $segment) {
				$uri = '../' . $uri;
			}
		}

		// if quoted, re-add quotation marks
		if ($quoted) {
			$uri = '"' . str_replace('"', '%22', $uri) . '"';
		}

		// re-add CSS URI syntax
		return 'url(' . $uri . ')';
	}

	protected function isRelative($uri)
	{
		return (preg_match('!^(?:https?:|ftp:|data:)!', $uri) === 0);
	}
}

?>
