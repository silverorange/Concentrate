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

        // PHP seems to occassionally truncate or skip lines when passing large
        // amounts of data to shell_exec, so we use temporary instead of
        // echoing to STDIN. Using proc_open is also an option, but the added
        // complexity is not worth the effort.
        $filename = $this->writeTempFile($input);

        // Build command. Redirect STDERR to STDOUT so we can capture and parse
        // errors.
        $command = sprintf(
            '%s %s -- %s 2>&1',
            escapeshellarg($this->getTerserBin()),
            implode(' ', $args),
            escapeshellarg($filename)
        );

        // run command
        $output = exec($command);

        // remove temp file
        unlink($filename);

        return $output;
    }

    protected function writeTempFile(string $content): string
    {
        $filename = tempnam(sys_get_temp_dir(), 'concentrate-');
        file_put_contents($filename, $content);
        return $filename;
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
