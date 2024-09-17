<?php

/**
 * @category  Tools
 * @package   Concentrate
 * @author    Michael Gauthier <mike@silverorange.com>
 * @copyright 2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class Concentrate_DataProvider_FileFinderComposer
    implements Concentrate_DataProvider_FileFinderInterface
{
    protected $wwwPath = null;

    public function __construct($wwwPath)
    {
        $this->setWwwPath($wwwPath);
    }

    public function setWwwPath($wwwPath)
    {
        $this->wwwPath = (string)$wwwPath;
        return $this;
    }

    public function getDataFiles()
    {
        $files = [];

        $basePath = dirname($this->wwwPath) . DIRECTORY_SEPARATOR . 'vendor';
        foreach ($this->getVendorPaths($basePath) as $vendorPath) {
            foreach ($this->getPackagePaths($vendorPath) as $packagePath) {
                $finder = new Concentrate_DataProvider_FileFinderDirectory(
                    $packagePath . DIRECTORY_SEPARATOR . 'dependencies'
                );
                $files = array_merge(
                    $files,
                    $finder->getDataFiles()
                );
            }
        }

        return $files;
    }

    protected function getVendorPaths($basePath)
    {
        $paths = [];

        if (is_dir($basePath)) {
            $baseDir = dir($basePath);
            while (false !== ($vendorName = $baseDir->read())) {
                if ($vendorName === '.'
                    || $vendorName === '..'
                    || $vendorName === 'bin'
                    || $vendorName === 'autoload.php'
                ) {
                    continue;
                }

                $vendorPath = $basePath . DIRECTORY_SEPARATOR . $vendorName;
                if (is_dir($vendorPath)) {
                    $paths[] = $vendorPath;
                }
            }
        }

        return $paths;
    }

    protected function getPackagePaths($vendorPath)
    {
        $paths = [];

        $vendorDir = dir($vendorPath);
        while (false !== ($packageName = $vendorDir->read())) {
            if ($packageName === '.' || $packageName === '..') {
                continue;
            }

            $packagePath = $vendorPath . DIRECTORY_SEPARATOR . $packageName;
            $paths[] = $packagePath;
        }

        return $paths;
    }
}

?>
