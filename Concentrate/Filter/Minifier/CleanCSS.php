<?php

/**
 * @category  Tools
 * @package   Concentrate
 * @author    Michael Gauthier <mike@silverorange.com>
 * @copyright 2022 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class Concentrate_Filter_Minifier_CleanCSS
    extends Concentrate_Filter_Minifier_Abstract
{
    const DEFAULT_BIN_NAME = 'cleancss';

    protected $cleanCSSBin = '';

    public function __construct(array $options = [])
    {
    }

    public function isSuitable(string $type = ''): bool
    {
        return (
            ($type === 'css' || $type === 'less')
            && $this->getCleanCSSBin() !== ''
        );
    }

    protected function filterImplementation(
        string $input,
        string $type = ''
    ): string {
        $args = [
            '-O1',
            '--inline=none'
        ];

        // Build command. Redirect STDERR to STDOUT so we can capture and parse
        // errors.
        $command = sprintf(
            'printf %s | %s %s 2>&1',
            escapeshellarg(str_replace(['\\', '%'], ['\\\\', '%%'], $input)),
            escapeshellarg($this->getCleanCSSBin()),
            implode(' ', $args)
        );

        // run command
        $output = shell_exec($command);

        return $output;
    }

    protected function getCleanCSSBin(): string
    {
        if ($this->cleanCSSBin == '') {
            $this->cleanCSSBin = $this->findCleanCSSBin();
        }

        return $this->cleanCSSBin;
    }

    protected function findCleanCSSBin(): string
    {
        $cleanCSSBin = '';

        $paths = [
            // Try to load clean-css if Concentrate is the root project.
            __DIR__ . '/../../../node_modules/.bin',

            // Try to load clean-css if Concentrate is installed as a library
            // for another root project.
            __DIR__ . '/../../../../../../node_modules/.bin',
        ];

        foreach ($paths as $path) {
            echo "trying ", $path, "\n";
            if (is_dir($path)) {
                $dir = dir($path);
                while (false !== ($entry = $dir->read())) {
                    if ($entry === self::DEFAULT_BIN_NAME) {
                        $cleanCSSBin = $path . DIRECTORY_SEPARATOR . $entry;
                        break 2;
                    }
                }
            }
        }

        return $cleanCSSBin;
    }
}

?>
