<?php

/**
 * @category  Tools
 * @package   Concentrate
 * @author    Michael Gauthier <mike@silverorange.com>
 * @copyright 2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class Concentrate_Path
{
    /**
     * @var string
     */
    protected $path;

    /**
     * Creates a new path object
     *
     * @param string $path the path.
     */
    public function __construct($path = '')
    {
        $this->path = $path;
    }

    /**
     * Gets a string representation of this path
     *
     * @return string a string representation of this path.
     */
    public function __toString()
    {
        return $this->path;
    }

    /**
     * Creates a directory for this path if such a directory does not already
     * exist
     *
     * The last item in this path is considered the file and is stripped off the
     * path before the directory is created. For example, this creates the
     * directory '/foo/bar':
     * <code>
     * <?php
     * $path = new Concentrate_Path('/foo/bar/baz');
     * $path->writeDirectory();
     * ?>
     * </code>
     *
     * @return Concentrate_Path the current object for fluent interface.
     *
     * @throws Concentrate_FileException if the directory could not be created.
     */
    public function writeDirectory()
    {
        $toDirectory = dirname($this->path);

        if (!file_exists($toDirectory)) {
            mkdir($toDirectory, 0770, true);
        }

        if (!is_dir($toDirectory)) {
            throw new Concentrate_FileException(
                "Could not write to directory {$toDirectory} because it " .
                "exists and is not a directory.",
                0,
                $toDirectory
            );
        }

        return $this;
    }

    /**
     * Evaluates a path that contains relative references and removes
     * superfluous current directory references
     *
     * @return Concentrate_Path a new path object.
     */
    public function evaluate()
    {
        $postPath = array();
        $prePath = array();

        $path = rtrim($this->path, '/');
        $pathSegments = explode('/', $path);
        foreach ($pathSegments as $segment) {
            if ($segment == '..') {
                if (count($postPath) > 0) {
                    array_pop($postPath);
                } else {
                    // we've gone past the start of the relative path
                    array_push($prePath, '..');
                }
            } else if ($segment == '.') {
                // no-op
            } else {
                array_push($postPath, $segment);
            }
        }

        return new Concentrate_Path(
            implode(
                '/',
                array_merge(
                    $prePath,
                    $postPath
                )
            )
        );
    }
}

?>
