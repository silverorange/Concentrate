<?php

/**
 * @category  Tools
 *
 * @author    Michael Gauthier <mike@silverorange.com>
 * @copyright 2023 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class Concentrate_Compiler_Babel extends Concentrate_Compiler_Abstract
{
    public const DEFAULT_BIN_NAME = 'babel';

    protected string $babelBin = '';

    public function __construct(array $options = [])
    {
        if (array_key_exists('babelBin', $options)) {
            $this->setBabelBin($options['babelBin']);
        } elseif (array_key_exists('babel_bin', $options)) {
            $this->setBabelBin($options['babel_bin']);
        }
    }

    public function isSuitable(string $type = ''): bool
    {
        return $type === 'js' && $this->getBabelBin() !== '';
    }

    public function setBabelBin(string $babelBin): self
    {
        $this->babelBin = $babelBin;

        return $this;
    }

    public function compile(string $content, string $type): string
    {
        return $this->compileInternal($content, false, null, $type);
    }

    public function compileFile(
        string $fromFilename,
        string $toFilename,
        string $type
    ): self {
        $path = new Concentrate_Path($toFilename);
        $path->writeDirectory();

        $this->compileInternal($fromFilename, true, $toFilename, $type);

        return $this;
    }

    protected function compileInternal(
        string $data,
        bool $isFile,
        ?string $outputFile,
        string $type
    ): string {
        $args = [];

        // filename
        if ($isFile) {
            $filename = $data;
        } else {
            $filename = $this->writeTempFile($data);
        }
        $args[] = escapeshellarg($filename);

        // output
        if ($outputFile !== null) {
            $args[] = '--out-file';
            $args[] = escapeshellarg($outputFile);
        }

        // build command
        $command = $this->getBabelBin() . ' ' . implode(' ', $args);

        // run command
        $output = shell_exec($command) ?: '';

        // remove temp file
        if (!$isFile) {
            unlink($filename);
        }

        return $output;
    }

    protected function writeTempFile(string $content): string
    {
        $filename = tempnam(sys_get_temp_dir(), 'concentrate-');
        file_put_contents($filename, $content);

        return $filename;
    }

    protected function getBabelBin(): string
    {
        if ($this->babelBin === '') {
            $this->setBabelBin($this->findBabelBin());
        }

        return $this->babelBin;
    }

    protected function findBabelBin(): string
    {
        $babelBin = '';

        $paths = [
            // Try to load Babel if Concentrate is the root project.
            __DIR__ . '/../../../node_modules/.bin',

            // Try to load Babel if Concentrate is installed as a library for
            // another root project.
            __DIR__ . '/../../../../../../node_modules/.bin',

            // Try to load running from current dir
            getcwd() . '/node_modules/.bin',
        ];

        foreach ($paths as $path) {
            if (is_dir($path)) {
                $dir = dir($path);
                while (false !== ($entry = $dir->read())) {
                    if ($entry === self::DEFAULT_BIN_NAME) {
                        $babelBin = $path . DIRECTORY_SEPARATOR . $entry;
                        break 2;
                    }
                }
            }
        }

        return $babelBin;
    }
}
