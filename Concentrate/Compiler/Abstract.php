<?php

/**
 * @category  Tools
 * @package   Concentrate
 * @author    Michael Gauthier <mike@silverorange.com>
 * @copyright 2012-2023 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class Concentrate_Compiler_Abstract
{
    abstract public function compile(string $content, string $type): string;

    abstract public function isSuitable(string $type = ''): bool;

    public function compileFile(
        string $fromFilename,
        string $toFilename,
        string $type
    ): self {
        if (!is_readable($fromFilename)) {
            throw new Concentrate_FileException(
                "Could not read {$fromFilename} for compilation.",
                0,
                $fromFilename
            );
        }

        $path = new Concentrate_Path($toFilename);
        $path->writeDirectory();

        $content = file_get_contents($fromFilename);
        $content = $this->compile($content, $type);
        file_put_contents($toFilename, $content);

        return $this;
    }
}

?>
