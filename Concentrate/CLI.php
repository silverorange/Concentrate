<?php

use League\CLImate\CLImate;

/**
 * @category  Tools
 * @package   Concentrate
 * @author    Michael Gauthier <mike@silverorange.com>
 * @copyright 2010-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class Concentrate_CLI
{
    const VERBOSITY_NONE     = 0;
    const VERBOSITY_MESSAGES = 1;
    const VERBOSITY_DETAILS  = 2;

    /**
     * @var Console_CommandLine
     */
    protected $parser = null;

    /**
     * @var CLImate
     */
    protected $climate = null;

    /**
     * @var Concentrate_Concentrator
     */
    protected $concentrator = null;

    /**
     * @var boolean
     */
    protected $minify = false;

    /**
     * @var boolean
     */
    protected $combine = false;

    /**
     * @var boolean
     */
    protected $compile = false;

    /**
     * @var string
     */
    protected $webroot = './';

    /**
     * @var string
     */
    protected $directory = '';

    /**
     * @var integer
     */
    protected $verbosity = self::VERBOSITY_NONE;

    /**
     * @var Concentrate_FileCache
     */
    protected $compiledCache = null;

    /**
     * @var Concentrate_FileCache
     */
    protected $minifiedCache = null;

    public function run()
    {
        $this->concentrator = new Concentrate_Concentrator();

        $this->parser = Console_CommandLine::fromXmlFile($this->getUiXml());
        $this->climate = new CLImate;
        $this->climate->forceAnsiOn();
        try {
            $result = $this->parser->parse();

            $this->setOptions($result->options);
            $this->setWebRoot($result->args['webroot']);
            $this->loadDataFiles();

            if ($this->combine) {
                $this->writeCombinedFiles();
                $this->writeCombinedFlagFile();
            }

            if ($this->compile) {
                $this->writeCompiledFiles();
                $this->writeCompiledFlagFile();
            }

            if ($this->minify) {
                $this->writeMinifiedFiles();
                $this->writeMinifiedFlagFile();
            }

        } catch (Console_CommandLine_Exception $e) {
            $this->displayError($e->getMessage());
        } catch (Exception $e) {
            $this->displayError($e->getMessage(), false);
            $this->displayError($e->getTraceAsString());
        }
    }

    public function setCompiledCache(Concentrate_FileCache $cache)
    {
        $this->compiledCache = $cache;
        return $this;
    }

    public function setMinifiedCache(Concentrate_FileCache $cache)
    {
        $this->minifiedCache = $cache;
        return $this;
    }

    protected function setWebRoot($webroot)
    {
        if ($this->verbosity >= self::VERBOSITY_MESSAGES) {
            $this->climate->br();
            $this->display('Web root:');
        }

        $this->webroot = strval($webroot);
        $this->webroot = rtrim($this->webroot, DIRECTORY_SEPARATOR);

        if ($this->verbosity >= self::VERBOSITY_MESSAGES) {
            $this->display('=> set to "' . $this->webroot . '"');
        }

        return $this;
    }

    protected function setOptions(array $options)
    {
        if (array_key_exists('verbose', $options)
            && $options['verbose'] !== null
        ) {
            $this->verbosity = intval($options['verbose']);
        }

        if (array_key_exists('combine', $options)
            && $options['combine'] !== null
        ) {
            $this->combine = ($options['combine']) ? true : false;
        }

        if (array_key_exists('minify', $options)
            && $options['minify'] !== null
        ) {
            $this->minify = ($options['minify']) ? true : false;
        }

        if (array_key_exists('compile', $options)
            && $options['compile'] !== null
        ) {
            $this->compile = ($options['compile']) ? true : false;
        }

        if (array_key_exists('directory', $options)
            && $options['directory'] !== null
        ) {
            $this->directory = strval($options['directory']);
            $this->directory = rtrim($this->directory, DIRECTORY_SEPARATOR);
        }

        if ($this->verbosity >= self::VERBOSITY_MESSAGES) {
            // To display a new line before this output
            $this->climate->br();
            $this->display('Options:');
            $this->display('=> directory : ' . $this->directory);
            $this->display(
                sprintf(
                    '=> combine   : %s',
                    ($this->combine) ? 'yes' : 'no'
                )
            );
            $this->display(
                sprintf(
                    '=> minify    : %s',
                    ($this->minify) ? 'yes' : 'no'
                )
            );
            $this->display(
                sprintf(
                    '=> compile   : %s',
                    ($this->compile) ? 'yes' : 'no'
                )
            );
        }

        return $this;
    }

    protected function loadDataFiles()
    {
        // load data files from composer vendor dir
        $fileFinder = new Concentrate_DataProvider_FileFinderComposer(
            $this->webroot
        );
        $this->concentrator->loadDataFiles(
            $fileFinder->getDataFiles()
        );

        // load data files from optional directory
        if ($this->directory != '') {
            $fileFinder = new Concentrate_DataProvider_FileFinderDirectory(
                $this->directory
            );
            $this->concentrator->loadDataFiles(
                $fileFinder->getDataFiles()
            );
        }

        return $this;
    }

    protected function writeCombinedFiles()
    {
        if ($this->verbosity >= self::VERBOSITY_MESSAGES) {
            $this->climate->br();
            $this->display('Writing combined files:');
        }

        $packer       = new Concentrate_Packer();
        $combinesInfo = $this->concentrator->getCombinesInfo();

        if (count($combinesInfo) === 0
            && $this->verbosity >= self::VERBOSITY_MESSAGES
        ) {
            $this->display('=> no combined files to write.');
        }

        foreach ($combinesInfo as $combine => $info) {
            if ($this->verbosity >= self::VERBOSITY_MESSAGES) {
                $filename = $this->webroot . DIRECTORY_SEPARATOR . $combine;
                $this->display('=> writing "' . $filename . '"');
            }

            $files = $info['Includes'];

            $this->checkForConflicts(array_keys($files));
            uksort($files, array($this->concentrator, 'compareFiles'));

            if ($this->verbosity >= self::VERBOSITY_DETAILS) {
                foreach ($files as $file => $info) {
                    $this->displayInline(' * ' . $file);
                    if (!$info['explicit']) {
                        $this->displayInline(' (implicit)', $this->climate->cyan());
                    };
                    $this->climate->br();
                }
                $this->climate->br();
            }

            $packer->pack($this->webroot, array_keys($files), $combine);
        }
    }

    protected function writeMinifiedFiles()
    {
        $filter = new Concentrate_Filter_Minifier_YUICompressor();
        $this->writeMinifiedFilesFromDirectory($filter);

        if ($this->compile) {
            $this->writeMinifiedFilesFromDirectory(
                $filter,
                'compiled',
                array(
                    'css',
                    'js',
                    'less'
                )
            );
        }
    }

    protected function writeMinifiedFilesFromDirectory(
        Concentrate_Filter_Abstract $filter,
        $directory = '',
        array $types = array('css', 'js')
    ) {
        if ($this->verbosity >= self::VERBOSITY_MESSAGES) {
            $this->climate->br();
            if ($directory != '') {
                $this->display(
                    sprintf(
                        'Writing minified files from %s:',
                        $directory
                    )
                );
            } else {
                $this->display(
                    'Writing minified files:'
                );
            }
        }

        $fileInfo = $this->concentrator->getFileInfo();
        foreach ($fileInfo as $file => $info) {
            if ($directory == '') {
                $fromFilename = $this->webroot
                    . DIRECTORY_SEPARATOR . $file;
            } else {
                $fromFilename = $this->webroot
                    . DIRECTORY_SEPARATOR . $directory
                    . DIRECTORY_SEPARATOR . $file;
            }

            // if source file does not exist, skip it
            if (!file_exists($fromFilename)) {
                continue;
            }

            // if file specifies that is should not be minified, skip it
            if (!$info['Minify']) {
                continue;
            }

            // only minify valid types
            $type = pathinfo($fromFilename, PATHINFO_EXTENSION);
            if (!in_array($type, $types)) {
                continue;
            }

            // Compiled less files are actually CSS. Set type appropriately.
            if ($type === 'less') {
                $type = 'css';
            }

            if ($directory == '') {
                $toFilename = $this->webroot
                    . DIRECTORY_SEPARATOR . 'min'
                    . DIRECTORY_SEPARATOR . $file;
            } else {
                $toFilename = $this->webroot
                    . DIRECTORY_SEPARATOR . 'min'
                    . DIRECTORY_SEPARATOR . $directory
                    . DIRECTORY_SEPARATOR . $file;
            }

            if ($this->verbosity >= self::VERBOSITY_DETAILS) {
                $this->display(' * ' . $directory . '/' . $file);
            }

            // If type is CSS, add extra filter to chain to update relative
            // URIs within the CSS
            if ($type === 'css') {
                $fromFilterFile = ($directory == '')
                    ? $file
                    : $directory . '/' . $file;

                $toFilterFile = ($directory =='')
                    ? 'min/' . $file
                    : 'min/' . $directory . '/' . $file;

                $moveFilter = new Concentrate_Filter_CSSMover(
                    $fromFilterFile,
                    $toFilterFile
                );

                $filter->setNextFilter($moveFilter);
            } else {
                $filter->clearNextFilter();
            }

            $this->writeMinifiedFile(
                $filter,
                $fromFilename,
                $toFilename,
                $type
            );
        }

        if ($this->combine) {
            $combinesInfo = $this->concentrator->getCombinesInfo();
            foreach ($combinesInfo as $combine => $info) {
                if ($directory == '') {
                    $fromFilename = $this->webroot
                        . DIRECTORY_SEPARATOR . $combine;
                } else {
                    $fromFilename = $this->webroot
                        . DIRECTORY_SEPARATOR . $directory
                        . DIRECTORY_SEPARATOR . $combine;
                }

                // if source file does not exist, skip it
                if (!file_exists($fromFilename)) {
                    continue;
                }

                // if file specifies that is should not be minified, skip it
                if (!$info['Minify']) {
                    continue;
                }

                // only minify valid types
                $type = pathinfo($fromFilename, PATHINFO_EXTENSION);
                if (!in_array($type, $types)) {
                    continue;
                }

                // Compiled less files are actually CSS. Set type appropriately.
                if ($type === 'less') {
                    $type = 'css';
                }

                if ($directory == '') {
                    $toFilename = $this->webroot
                        . DIRECTORY_SEPARATOR . 'min'
                        . DIRECTORY_SEPARATOR . $combine;
                } else {
                    $toFilename = $this->webroot
                        . DIRECTORY_SEPARATOR . 'min'
                        . DIRECTORY_SEPARATOR . $directory
                        . DIRECTORY_SEPARATOR . $combine;
                }

                if ($this->verbosity >= self::VERBOSITY_DETAILS) {
                    $this->display(' * ' . $directory . '/' . $combine);
                }

                // If type is CSS, add extra filter to chain to update relative
                // URIs within the CSS
                if ($type === 'css') {
                    $fromFilterFile = ($directory == '')
                        ? $combine
                        : $directory . '/' . $combine;

                    $toFilterFile = ($directory =='')
                        ? 'min/' . $combine
                        : 'min/' . $directory . '/' . $combine;

                    $moveFilter = new Concentrate_Filter_CSSMover(
                        $fromFilterFile,
                        $toFilterFile
                    );

                    $filter->setNextFilter($moveFilter);
                } else {
                    $filter->clearNextFilter();
                }

                $this->writeMinifiedFile(
                    $filter,
                    $fromFilename,
                    $toFilename,
                    $type
                );
            }
        }
    }

    protected function writeMinifiedFile(
        Concentrate_Filter_Abstract $filter,
        $fromFilename,
        $toFilename,
        $type
    ) {
        // cache key is unique on file path and file content
        $key = md5($fromFilename . md5_file($fromFilename));

        $cache = $this->minifiedCache;

        if ($cache instanceof Concentrate_FileCache
            && $cache->copyTo($key, $toFilename)
        ) {
            if ($this->verbosity >= self::VERBOSITY_DETAILS) {
                $this->display(
                    '   used cached version',
                    $this->climate->lightGray()
                );
            }
        } else {
            // minify
            $filter->filterFile($fromFilename, $toFilename, $type);

            if ($cache instanceof Concentrate_FileCache
                && $cache->write($key, $toFilename)
            ) {
                if ($this->verbosity >= self::VERBOSITY_DETAILS) {
                    $this->display(
                        '   wrote cached version',
                        $this->climate->lightGray()
                    );
                }
            } else {
                if ($this->verbosity >= self::VERBOSITY_DETAILS) {
                    $this->display(
                        '   could not write cached version',
                        $this->climate->lightGray()
                    );
                }
            }
        }
    }

    protected function writeCompiledFiles()
    {
        if ($this->verbosity >= self::VERBOSITY_MESSAGES) {
            $this->climate->br();
            $this->display('Writing compiled files:');
        }

        $compiler = new Concentrate_CompilerLess();

        $fileInfo = $this->concentrator->getFileInfo();
        foreach ($fileInfo as $file => $info) {
            $fromFilename = $this->webroot
                . DIRECTORY_SEPARATOR . $file;

            // if source file does not exist, skip it
            if (!file_exists($fromFilename)) {
                continue;
            }

            // only compile LESS
            $type = pathinfo($fromFilename, PATHINFO_EXTENSION);
            if ($type !== 'less') {
                continue;
            }

            $toFilename = $this->webroot
                . DIRECTORY_SEPARATOR . 'compiled'
                . DIRECTORY_SEPARATOR . $file;

            if ($this->verbosity >= self::VERBOSITY_DETAILS) {
                $this->display(' * ' . $file);
            }

            $filter = new Concentrate_Filter_CSSMover(
                $file,
                'compiled/' . $file
            );

            $this->writeCompiledFile(
                $compiler,
                $filter,
                $fromFilename,
                $toFilename,
                $type
            );
        }

        if ($this->combine) {
            $combinesInfo = $this->concentrator->getCombinesInfo();
            foreach ($combinesInfo as $combine => $info) {
                $fromFilename = $this->webroot
                    . DIRECTORY_SEPARATOR . $combine;

                // if source file does not exist, skip it
                if (!file_exists($fromFilename)) {
                    continue;
                }

                // only compile LESS
                $type = pathinfo($fromFilename, PATHINFO_EXTENSION);
                if ($type !== 'less') {
                    continue;
                }

                $toFilename = $this->webroot
                    . DIRECTORY_SEPARATOR . 'compiled'
                    . DIRECTORY_SEPARATOR . $combine;

                if ($this->verbosity >= self::VERBOSITY_DETAILS) {
                    $this->display(' * ' . $combine);
                }

                $filter = new Concentrate_Filter_CSSMover(
                    $combine,
                    'compiled/' . $combine
                );

                $this->writeCompiledFile(
                    $compiler,
                    $filter,
                    $fromFilename,
                    $toFilename,
                    $type
                );
            }
        }
    }

    protected function writeCompiledFile(
        Concentrate_CompilerAbstract $compiler,
        Concentrate_Filter_Abstract $filter = null,
        $fromFilename,
        $toFilename,
        $type
    ) {
        // cache key is unique on file path and file content
        $key = md5($fromFilename . md5_file($fromFilename));

        $cache = $this->compiledCache;

        if ($cache instanceof Concentrate_FileCache
            && $cache->copyTo($key, $toFilename)
        ) {
            if ($this->verbosity >= self::VERBOSITY_DETAILS) {
                $this->display(
                    '   used cached version',
                    $this->climate->lightGray()
                );
            }
        } else {
            // compile
            $compiler->compileFile($fromFilename, $toFilename, $type);

            // TODO: perform filtering before writing file
            if ($filter instanceof Concentrate_Filter_Abstract) {
                $filter->filterFile($toFilename, $toFilename);
            }

            if ($cache instanceof Concentrate_FileCache
                && $cache->write($key, $toFilename)
            ) {
                if ($this->verbosity >= self::VERBOSITY_DETAILS) {
                    $this->display(
                        '   wrote cached version',
                        $this->climate->lightGray()
                    );
                }
            } else {
                if ($this->verbosity >= self::VERBOSITY_DETAILS) {
                    $this->display(
                        '   could not write cached version',
                        $this->climate->lightGray()
                    );
                }
            }
        }
    }

    protected function writeCombinedFlagFile()
    {
        $this->writeFlagFile(Concentrate_FlagFile::COMBINED);
    }

    protected function writeMinifiedFlagFile()
    {
        $this->writeFlagFile(Concentrate_FlagFile::MINIFIED);
    }

    protected function writeCompiledFlagFile()
    {
        $this->writeFlagFile(Concentrate_FlagFile::COMPILED);
    }

    protected function writeFlagFile($filename)
    {
        $filename = $this->webroot
            . DIRECTORY_SEPARATOR . $filename;

        if ($this->verbosity >= self::VERBOSITY_MESSAGES) {
            $this->climate->br();
            $this->display(
                "Writing flag file '{$filename}':"
            );
        }

        if ((!file_exists($filename) && !is_writable($this->webroot))
            || (file_exists($filename) && !is_writable($filename))
        ) {
            throw new Concentrate_FileException(
                "The flag file '{$filename}' could not be written."
            );
        }

        file_put_contents($filename, time());

        if ($this->verbosity >= self::VERBOSITY_MESSAGES) {
            $this->display('=> written');
        }
    }

    protected function getUiXml()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . '..'
            . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR
            . 'cli.xml';
    }

    protected function display($string, $climate = null)
    {
        if (is_null($climate)) {
            $climate = $this->climate;
        }
        $climate->out($string);
    }

    protected function displayInline($string, $climate = null) {
        if (is_null($climate)) {
            $climate = $this->climate;
        }
        $climate->inline($string);
    }

    protected function displayError($string, $exit = true, $code = 1)
    {
        $this->climate->to('error')->out($string);
        if ($exit) {
            exit($code);
        }
    }

    /**
     * @param array $files
     *
     * @throws Exception if one or more conflicts are present.
     */
    protected function checkForConflicts(array $files)
    {
        $conflicts = $this->concentrator->getConflicts($files);
        if (count($conflicts) > 0) {
            $conflictList = '';
            $count = 0;
            foreach ($conflicts as $file => $conflict) {
                $conflictList.= sprintf(
                    "\n- %s conflicts with %s",
                    $file,
                    implode(', ', $conflict)
                );
                $count++;
            }
            throw new Exception(
                'The following conflicts were detected: ' . $conflictList
            );
        }
    }

}

?>
