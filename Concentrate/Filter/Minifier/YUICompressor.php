<?php

/**
 * @category  Tools
 * @package   Concentrate
 * @author    Michael Gauthier <mike@silverorange.com>
 * @copyright 2010-2022 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class Concentrate_Filter_Minifier_YUICompressor
    extends Concentrate_Filter_Minifier_Abstract
{
    const DEFAULT_JAR_NAME = '/yuicompressor(?:-[0-9]\.[0-9]\.[0-9])?\.jar/';

    protected $javaBin = 'java';
    protected $jarFile = null;
    protected $types = ['css', 'js'];

    public function __construct(array $options = [])
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

        if (array_key_exists('types', $options)) {
            $this->setTypes($options['types']);
        }
    }

    public function setJavaBin(string $javaBin): self
    {
        $this->javaBin = $javaBin;
        return $this;
    }

    public function setJarFile(string $jarFile): self
    {
        $this->jarFile = $jarFile;
        return $this;
    }

    public function setTypes(array $types): self
    {
        $this->types = $types;
        return $this;
    }

    public function addType(string $type): self
    {
        $this->types[] = $type;
        return $this;
    }

    public function isSuitable(string $type = ''): bool
    {
        return (in_array($type, $this->types) && $this->getJarFile() !== '');
    }

    protected function filterImplementation(
        string $input,
        string $type = ''
    ): string {
        // default args
        $args = array(
            '--nomunge',
            '--preserve-semi',
        );

        // type
        switch ($type) {
        case 'css':
            $args[] = '--type css';
            break;
        case 'js':
        default:
            $args[] = '--type js';
            break;
        }

        // filename
        $filename = $this->writeTempFile($input);
        $args[] = escapeshellarg($filename);

        // Build command. Redirect STDERR to STDOUT so we can capture and parse
        // errors.
        $command = sprintf(
            '%s -jar %s %s 2>&1',
            $this->javaBin,
            escapeshellarg($this->getJarFile()),
            implode(' ', $args)
        );

        // run command
        $output = shell_exec($command);

        // remove temp file
        unlink($filename);

        $errorExpression = '/Unable to access jarfile/';
        if (preg_match($errorExpression, $output) === 1) {
            throw new Concentrate_FileException(
                "The JAR file '{$this->jarFile}' does not exist.",
                0,
                $this->jarFile
            );
        }

        return $output;
    }

    protected function writeTempFile(string $content): string
    {
        $filename = tempnam(sys_get_temp_dir(), 'concentrate-');
        file_put_contents($filename, $content);
        return $filename;
    }

    protected function getJarFile(): string
    {
        if ($this->jarFile === null) {
            $this->jarFile = $this->findJarFile();
        }

        return $this->jarFile;
    }

    protected function findJarFile(): string
    {
        $jarFile = '';

        // Support supported and deprecated YUI Compressor package namespaces.
        $packages = [
            'packagelist/yuicompressor-bin',
            'packagist/yuicompressor-bin',
        ];

        $paths = [
            // Try to load jar if Concentrate is the root project.
            __DIR__ . '/../../../vendor/$1/bin',

            // Try to load jar if Concentrate is installed as a library for
            // another root project.
            __DIR__ . '/../../../../../$1/bin',
        ];

        $fullPaths = [];
        foreach ($paths as $path) {
            foreach ($packages as $package) {
                $fullPaths[] = str_replace('$1', $package, $path);
            }
        }

        foreach ($fullPaths as $path) {
            if (is_dir($path)) {
                $dir = dir($path);
                while (false !== ($entry = $dir->read())) {
                    if (preg_match(self::DEFAULT_JAR_NAME, $entry) === 1) {
                        $jarFile = $path . DIRECTORY_SEPARATOR . $entry;
                        break 2;
                    }
                }
            }
        }

        return $jarFile;
    }
}

?>
