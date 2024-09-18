<?php

/**
 * @category  Tools
 *
 * @author    Michael Gauthier <mike@silverorange.com>
 * @copyright 2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class Concentrate_FileException extends Concentrate_Exception
{
    protected $filename = '';

    public function __construct($message, $code = 0, $filename = '')
    {
        parent::__construct($message, $code);
        $this->filename = $filename;
    }

    public function getFilename()
    {
        return $this->filename;
    }
}
