<?php

require_once 'Concentrate/DataProvider.php';

class Concentrate_DataProviderMemcache extends Concentrate_DataProvider
{
	protected $memcache = null;

	public function __construct(Memcached $memcache)
	{
		$this->memcache = $memcache;
	}

	public function getData()
	{
		if (($data = $this->memcache->get($this->getCacheKey())) === false) {
			$this->data = parent::getData();
			$this->memcache->set($this->getCacheKey(), $this->data);
		} else {
			$this->data = $data;
		}

		return $this->data;
	}

	protected function getCacheKey()
	{
		$files = array_merge($this->loadedFiles, $this->pendingFiles);
		return 'concentrate-' . md5(implode(':', $files));
	}
}

?>
