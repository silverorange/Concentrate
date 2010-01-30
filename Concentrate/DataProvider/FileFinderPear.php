<?php

require_once 'Concentrate/DataProvider/FileFinderInterface.php';
require_once 'PEAR/Config.php';

class Concentrate_DataProvider_FileFinderPear
	implements Concentrate_DataProvider_FileFinderInterface
{
	protected $pearConfig = null;

	public function __construct($pearrc = null)
	{
		$this->setPearRc($pearrc);
	}

	public function setPearRc($pearrc = null)
	{
		$this->pearConfig = PEAR_Config::singleton($pearrc);
		return $this;
	}

	public function getDataFiles()
	{
		$files = array();

		$dataDir = $this->pearConfig->get('data_dir');
		if (is_dir($dataDir)) {
			// check each package sub-directory in the data directory
			$dataDirObject = dir($dataDir);
			while (false !== ($subDir = $dataDirObject->read())) {

				$dependencyDir = $dataDir .
					DIRECTORY_SEPARATOR . $subDir .
					DIRECTORY_SEPARATOR . 'dependencies';

				if (!file_exists($dependencyDir) || !is_dir($dependencyDir)) {
					continue;
				}

				// check each file in the data/$package/dependencies directory
				$dependencyDirObject = dir($dependencyDir);
				while (false !== ($file = $dependencyDirObject->read())) {

					// if it is a YAML file, add it to the list
					if (preg_match('/\.yaml$/i', $file) === 1) {
						$files[] = $dependencyDir .
							DIRECTORY_SEPARATOR . $file;
					}

				}
				$dependencyDirObject->close();
			}
			$dataDirObject->close();
		}

		return $files;
	}
}

?>
