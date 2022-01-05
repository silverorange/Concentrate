<?php

/**
 * @category  Tools
 * @package   Concentrate
 * @author    Michael Gauthier <mike@silverorange.com>
 * @copyright 2022 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class Concentrate_Filter_Minifier_Terser
    extends Concentrate_Filter_Minifier_Abstract
{
    const DEFAULT_BIN_NAME = 'terser';

    protected $terserBin = '';

    public function __construct(array $options = [])
    {
    }

    public function isSuitable(string $type = ''): bool
    {
        return ($type === 'js' && $this->getTerserBin() !== '');
    }

    protected function filterImplementation(
        string $input,
        string $type = ''
    ): string {
        $args = [
            '--mangle',
            '--keep-classnames',
            '--keep-fnames',
        ];

        // Build command. Redirect STDERR to STDOUT so we can capture and parse
        // errors.
        $command = sprintf(
            'printf %s | %s %s 2>&1',
            escapeshellarg(str_replace(['\\', '%'], ['\\\\', '%%'], $input)),
            escapeshellarg($this->getTerserBin()),
            implode(' ', $args)
        );
        $command = sprintf(
            'printf %s > foo.js',
            escapeshellarg(str_replace(['\\', '%'], ['\\\\', '%%'], $input)),
            // escapeshellarg($this->getTerserBin()),
            // implode(' ', $args)
        );


        // run command
        $output = shell_exec($command);
        echo $input;
        //echo $command;
        return null;
        return $output;
    }

    protected function getTerserBin(): string
    {
        if ($this->terserBin == '') {
            $this->terserBin = $this->findTerserBin();
        }

        return $this->terserBin;
    }

    protected function findTerserBin(): string
    {
        $terserBin = '';

        $paths = [
            // Try to load Terser if Concentrate is the root project.
            __DIR__ . '/../../../node_modules/.bin',

            // Try to load Terser if Concentrate is installed as a library for
            // another root project.
            __DIR__ . '/../../../../../../node_modules/.bin',
        ];

        foreach ($paths as $path) {
            if (is_dir($path)) {
                $dir = dir($path);
                while (false !== ($entry = $dir->read())) {
                    if ($entry === self::DEFAULT_BIN_NAME) {
                        $terserBin = $path . DIRECTORY_SEPARATOR . $entry;
                        break 2;
                    }
                }
            }
        }

        return $terserBin;
    }
}

?>
