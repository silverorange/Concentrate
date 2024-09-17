<?php

/**
 * @category  Tools
 *
 * @author    Michael Gauthier <mike@silverorange.com>
 * @copyright 2022 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class Concentrate_Process
{
    public const FD_STDIN = 0;
    public const FD_STDOUT = 1;
    public const FD_STDERR = 2;

    protected $command = '';

    public function __construct(string $command)
    {
        $this->command = $command;
    }

    public function run(string $input): string
    {
        $descriptorSpec = [
            self::FD_STDIN  => ['pipe', 'r'],
            self::FD_STDOUT => ['pipe', 'w'],
            self::FD_STDERR => ['pipe', 'w'],
        ];

        $pipes = [];
        $process = proc_open($this->command, $descriptorSpec, $pipes);

        if (!is_resource($process)) {
            throw new Concentrate_ProcessException(
                'Unable to open process for "' . $this->command . '".',
                0
            );
        }

        fwrite($pipes[self::FD_STDIN], $input);
        fclose($pipes[self::FD_STDIN]);

        $output = stream_get_contents($pipes[self::FD_STDOUT]);
        fclose($pipes[self::FD_STDOUT]);

        $error = stream_get_contents($pipes[self::FD_STDERR]);
        fclose($pipes[self::FD_STDERR]);

        $returnValue = proc_close($process);

        if ($returnValue !== 0) {
            throw new Concentrate_ProcessException(
                'Error running "' . $this->command . '": ' . $error,
                0
            );
        }

        return $output;
    }
}
