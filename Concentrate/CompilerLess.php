<?php

/**
 * @category  Tools
 * @package   Concentrate
 * @author    Michael Gauthier <mike@silverorange.com>
 * @copyright 2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class Concentrate_CompilerLess extends Concentrate_CompilerAbstract
{
    protected $lesscBin = '';

    public function __construct(array $options = array())
    {
        if (array_key_exists('lesscBin', $options)) {
            $this->setLesscBin($options['lesscBin']);
        } elseif (array_key_exists('lessc_bin', $options)) {
            $this->setLesscBin($options['lessc_bin']);
        }
    }

    public function setLesscBin($lesscBin)
    {
        $this->lesscBin = $lesscBin;
        return $this;
    }

    public function compile($content, $type)
    {
        return $this->compileInternal($content, false, null, $type);
    }

    public function compileFile($fromFilename, $toFilename, $type)
    {
        $path = new Concentrate_Path($toFilename);
        $path->writeDirectory();

        $this->compileInternal($fromFilename, true, $toFilename, $type);

        return $this;
    }

    protected function compileInternal($data, $isFile, $outputFile, $type)
    {
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

        if ($this->lesscBin == '') {
            $lesscBin = $this->findLesscBin();
        } else {
            $lesscBin = $this->lesscBin;
        }

        // build command
        $command = $lesscBin . ' ' . implode(' ', $args);

        // run command
        $output = shell_exec($command);

        // remove temp file
        if (!$isFile) {
            unlink($filename);
        }

        return $output;
    }

    protected function writeTempFile($content)
    {
        $filename = tempnam(sys_get_temp_dir(), 'concentrate-');
        file_put_contents($filename, $content);
        return $filename;
    }

    protected function findLesscBin()
    {
        $output = array();
        $return = 1;
        exec('which lessc 2> /dev/null', $output, $return);

        if ($return == 0) {
            $lesscBin = $output[0];
        } else {
            throw new Concentrate_FileException(
                'LESS compiler not found. Either the LESS compiler is not '
                . 'installed, or the location must be specified by using the '
                . 'lessc_bin option or by calling the setLesscBin() method '
                . 'on the Concentrate_CompilerLess object.'
            );
        }

        return $lesscBin;
    }
}

?>
