<?php

/**
 * @category  Tools
 *
 * @author    Michael Gauthier <mike@silverorange.com>
 * @copyright 2012-2023 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class Concentrate_Compiler_Less extends Concentrate_Compiler_Abstract
{
    protected string $lesscBin = '';

    public function __construct(array $options = [])
    {
        if (array_key_exists('lesscBin', $options)) {
            $this->setLesscBin($options['lesscBin']);
        } elseif (array_key_exists('lessc_bin', $options)) {
            $this->setLesscBin($options['lessc_bin']);
        }
    }

    public function isSuitable(string $type = ''): bool
    {
        return $type === 'less' && $this->getLesscBin() !== '';
    }

    public function setLesscBin(string $lesscBin): self
    {
        $this->lesscBin = $lesscBin;

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
        string $outputFile,
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
            $args[] = escapeshellarg($outputFile);
        }

        // build command
        $command = $this->getLesscBin() . ' ' . implode(' ', $args);

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

    protected function getLesscBin(): string
    {
        if ($this->lesscBin === '') {
            $this->setLesscBin($this->findLesscBin());
        }

        return $this->lesscBin;
    }

    protected function findLesscBin(): string
    {
        $output = [];
        $return = 1;
        exec('which lessc 2> /dev/null', $output, $return);

        if ($return === 0) {
            $lesscBin = $output[0];
        }

        return $lesscBin;
    }
}
