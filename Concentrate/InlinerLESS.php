<?php

/**
 * @category  Tools
 * @package   Concentrate
 * @author    Michael Gauthier <mike@silverorange.com>
 * @copyright 2013-2014 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class Concentrate_InlinerLESS extends Concentrate_InlinerCSS
{
    protected function inlineImportCallback($matches)
    {
        $replacement = '';

        if (isset($matches[2])) {
            $uri = trim($matches[2], '\'"');
        } else {
            $uri = trim($matches[1], '\'"');
        }

        // check if it contains an interpolated LESS variable
        $uriMatches = array();
        if (preg_match('/@{[\w-]+}/', $uri, $uriMatches) === 1) {
            $replacement = $matches[0];
        } else {
            $replacement = parent::inlineImportCallback($matches);
        }

        return $replacement;
    }
}

?>
