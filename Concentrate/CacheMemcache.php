<?php

require_once 'Concentrate/CacheInterface.php';
require_once 'Concentrate/CacheArray.php';

/**
 * @category  Tools
 * @package   Concentrate
 * @author    Michael Gauthier <mike@silverorange.com>
 * @copyright 2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class Concentrate_CacheMemcache implements Concentrate_CacheInterface
{
	protected $extraPrefix = '';
	protected $prefix = '';
	protected $memcache = null;
	protected $arrayCache = null;

	public function __construct(Memcached $memcache, $extraPrefix = '')
	{
		$this->extraPrefix = strval($extraPrefix);
		$this->memcache    = $memcache;
		$this->arrayCache  = new Concentrate_CacheArray();
	}

	public function setPrefix($prefix)
	{
		$this->prefix = strval($prefix);
	}

	public function set($key, $value)
	{
		$this->arrayCache->set($key, $value);
		return $this->memcache->set($this->getMemcacheKey($key), $value);
	}

	public function get($key)
	{
		$value = $this->arrayCache->get($key);

		if ($value === false) {
			$value = $this->memcache->get($this->getMemcacheKey($key));
		}

		return $value;
	}

	public function delete($key)
	{
		$this->arrayCache->delete($key);
		return $this->memcache->delete($this->getMemcacheKey(key));
	}

	protected function getMemCacheKey($key)
	{
		if ($this->prefix != '') {
			$key = $this->prefix . ':' . $key;
		}

		if ($this->extraPrefix != '') {
			$key = $this->extraPrefix . ':' . $key;
		}

		return $key;
	}
}

?>
