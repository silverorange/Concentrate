<?php

require_once 'Concentrate/DataProvider/FileFinderInterface.php';
require_once 'PEAR/Config.php';

class Concentrate_DataProvider_FileFinderDevelopment
	implements Concentrate_DataProvider_FileFinderInterface
{
	public function getDataFiles()
	{
		$files = array();

		foreach ($this->getIncludeDirs() as $includeDir) {
			$dependencyDir = $includeDir . DIRECTORY_SEPARATOR . 'dependencies';

			if (!file_exists($dependencyDir) || !is_dir($dependencyDir)) {
				continue;
			}

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

		return $files;
	}

	protected function getIncludeDirs()
	{
		return explode(PATH_SEPARATOR, get_include_path());
	}
}

?>
