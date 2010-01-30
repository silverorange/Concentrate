<?php

require_once 'Concentrate/MinifierInterface.php';
require_once 'Concentrate/Exception.php';

class Concentrate_MinifierYuiCompressor
	implements Concentrate_MinifierInterface
{
	const DEFAULT_JAR_NAME = '/yuicompressor-[0-9]\.[0-9]\.[0-9]\.jar/';

	protected $javaBin = 'java';
	protected $jarFile = '';

	public function __construct(array $options = array())
	{
		if (array_key_exists('javaBin', $options)) {
			$this->setJavaBin($options['javaBin']);
		} elseif (array_key_exists('java_bin', $options)) {
			$this->setJavaBin($options['java_bin']);
		}

		if (array_key_exists('jarFile', $options)) {
			$this->setJavaBin($options['jarFile']);
		} elseif (array_key_exists('jar_file', $options)) {
			$this->setJavaBin($options['jar_file']);
		}
	}

	public function setJavaBin($javaBin)
	{
		$this->javaBin = $javaBin;
		return $this;
	}

	public function setJarFile($jarFile)
	{
		$this->jarFile = $jarFile;
		return $this;
	}

	public function minify($content)
	{
		$filename = $this->writeTempFile($content);

		$command = sprintf(
			'%s -jar %s --nomunge %s',
			$this->javaBin,
			escapeshellarg($this->getJarFile()),
			escapeshellarg($filename)
		);

		$minifiedContent = shell_exec($command);

		unlink($filename);

		$errorExpression = '/^Unable to access jarfile/';
		if (preg_match($errorExpression, $minifiedContent) === 1) {
			throw new Concentrate_FileException(
				"The JAR file '{$this->jarFile}' does not exist.",
				0,
				$this->jarFile
			);
		}

		return $minifiedContent;
	}

	protected function writeTempFile($content)
	{
		$filename = tempnam(sys_get_temp_dir(), 'concentrate');
		file_put_contents($filename, $content);
		return $filename;
	}

	protected function getJarFile()
	{
		if ($this->jarFile == '') {
			$this->jarFile = $this->findJarFile();
		}

		return $this->jarFile();
	}

	protected function findJarFile()
	{
		$jarFile = '';

		$paths = explode(PATH_SEPARATOR, get_include_path());
		foreach ($paths as $path) {
			$dir = dir($path);
			while (false !== ($entry = $dir->read())) {
				if (preg_match(self::DEFAULT_JAR_NAME, $entry) === 1) {
					$jarFile = $path . DIRECTORY_SEPARATOR . $entry;
					break 2;
				}
			}
		}

		return $jarFile;
	}
}

?>
