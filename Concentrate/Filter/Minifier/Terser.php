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

    protected $terserBin = null;

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
        if ($input === '') {
            return $input;
        }

        $args = [
            '--mangle',
            '--keep-classnames',
            '--keep-fnames',
        ];

        // Build command.
        $command = sprintf(
            '%s %s',
            escapeshellarg($this->getTerserBin()),
            implode(' ', $args)
        );

        $process = new Concentrate_Process($command);
        $output = $process->run($input);

        return $output;
    }

    protected function getTerserBin(): string
    {
        if ($this->terserBin === null) {
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
