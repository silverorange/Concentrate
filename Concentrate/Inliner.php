<?php

/**
 * @category  Tools
 * @package   Concentrate
 * @author    Michael Gauthier <mike@silverorange.com>
 * @copyright 2010-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class Concentrate_Inliner
{
    protected $inlinedFiles = null;
    protected $root = '';
    protected $destinationFilename = '';
    protected $sourceFilename = '';
    protected $sourceDirectory= '.';
    protected $destinationDirectory = '.';
    protected $content = '';

    public function __construct(
        $root,
        $sourceFilename,
        $destinationFilename,
        Concentrate_FileList $inlinedFiles
    ) {
        $this->root = $root;

        $this->sourceFilename      = $sourceFilename;
        $this->destinationFilename = $destinationFilename;

        $this->sourceDirectory      = dirname($sourceFilename);
        $this->destinationDirectory = dirname($destinationFilename);

        $this->filename = $root . DIRECTORY_SEPARATOR . $sourceFilename;

        $this->inlinedFiles = $inlinedFiles;
    }

    public static function factory(
        $root,
        $sourceFilename,
        $destinationFilename,
        Concentrate_FileList $inlinedFiles
    ) {
        $extension = pathinfo($sourceFilename, PATHINFO_EXTENSION);

        switch (strtolower($extension)) {
        case 'css':
            $class = 'Concentrate_InlinerCSS';
            break;
        case 'less':
            $class = 'Concentrate_InlinerLESS';
            break;
        default:
            $class = __CLASS__;
            break;
        }

        return new $class(
            $root,
            $sourceFilename,
            $destinationFilename,
            $inlinedFiles
        );
    }

    public function getInlineContent()
    {
        $this->inlinedFiles->add($this->sourceFilename);
        return $this->load($this->filename);
    }

    protected function load($filename)
    {
        $content = '';

        // only load it if has not already been inlined
        if (!$this->inlinedFiles->contains($filename)) {
            if (!is_readable($filename)) {
                throw new Concentrate_FileException(
                    "The file '{$filename}' could not be read."
                );
            }

            $content = file_get_contents($filename);
        }

        return $content;
    }

    protected function isRelative($uri)
    {
        return (preg_match('!^(?:https?:|ftp:|data:)!', $uri) === 0);
    }
}

?>
