<?php

require_once 'Concentrate/Exception.php';
require_once 'Concentrate/FileList.php';
require_once 'Concentrate/Inliner.php';

class Concentrate_Packer
{
	public function pack($root, array $sourceFiles, $destinationFile)
	{
		$packedFiles = new Concentrate_FileList();

		$content = '';
		foreach ($sourceFiles as $sourceFile) {
			$inliner = Concentrate_Inliner::factory(
				$root,
				$sourceFile,
				$destinationFile,
				$packedFiles
			);

			$content .= $inliner->getInlineContent();
		}

		$filename = $root . DIRECTORY_SEPARATOR . $destinationFile;

		if (!is_writeable($filename)) {
			throw new Concentrate_FileException(
				"The file '{$filename}' could not be written."
			);
		}

		file_put_contents($filename, $content);

		return $this;
	}
}

?>
