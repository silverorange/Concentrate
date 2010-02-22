<?php

require_once 'Concentrate/CacheInterface.php';

/**
 * @category  Tools
 * @package   Concentrate
 * @author    Michael Gauthier <mike@silverorange.com>
 * @copyright 2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class Concentrate_CacheArray implements Concentrate_CacheInterface
{
	protected $data = array();

	public function setPrefix($prefix)
	{
		// do nothing since array is not saved between requests
	}

	public function set($key, $value)
	{
		$this->data[$key] = $value;
		return true;
	}

	public function get($key)
	{
		$value = false;

		if (isset($this->data[$key])) {
			$value = $this->data[$key];
		}

		return $value;
	}

	public function delete($key)
	{
		unset($this->data[$key]);
		return true;
	}
}

?>
