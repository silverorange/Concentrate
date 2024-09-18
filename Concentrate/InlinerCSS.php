<?php

/**
 * @category  Tools
 *
 * @author    Michael Gauthier <mike@silverorange.com>
 * @copyright 2010-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class Concentrate_InlinerCSS extends Concentrate_Inliner
{
    public function getInlineContent()
    {
        $content = parent::getInlineContent();
        $content = $this->inlineImports($content);
        $content = $this->updateUris($content);

        return "\n/* inlined file \"{$this->sourceFilename}\" */\n" .
            $content;
    }

    protected function updateUris($content)
    {
        if ($this->sourceDirectory != $this->destinationDirectory) {
            $content = preg_replace_callback(
                '/url\((.+?)\)/ui',
                $this->updateUrisCallback(...),
                $content
            );
        }

        return $content;
    }

    protected function updateUrisCallback(array $matches)
    {
        $uri = $matches[1];
        $quoted = false;

        // check if the URI is quoted
        if (preg_match('/^[\'"].*[\'"]$/', $matches[1]) === 1) {
            $quoted = true;
            $uri = trim($matches[1], '\'"');
        }

        // check if it is a relative URI; if so, rewrite it
        if ($this->isRelative($uri)) {
            // get path relative to root
            $directory = $this->sourceDirectory . '/' . dirname($uri);

            // add relative paths back to the root from the destination
            foreach (explode('/', $this->destinationDirectory) as $segment) {
                $directory = '../' . $directory;
            }

            // evaluate relative paths
            $directory = new Concentrate_Path($directory);
            $directory = (string) $directory->evaluate();

            $uri = $directory . '/' . basename($uri);
        }

        // if quoted, re-add quotation marks
        if ($quoted) {
            $uri = '"' . str_replace('"', '%22', $uri) . '"';
        }

        // re-add CSS URI syntax
        return 'url(' . $uri . ')';
    }

    protected function inlineImports($content)
    {
        return preg_replace_callback(
            '/@import\s+(?:url\((.+?)\)|(.+?));/ui',
            $this->inlineImportCallback(...),
            $content
        );
    }

    protected function inlineImportCallback($matches)
    {
        $replacement = '';

        if (isset($matches[2])) {
            $uri = trim($matches[2], '\'"');
        } else {
            $uri = trim($matches[1], '\'"');
        }

        // modify relative path to be relative to root, rather than
        // directory of CSS file
        if ($this->isRelative($uri)) {
            $uri = $this->sourceDirectory . '/' . $uri;
            $uri = new Concentrate_Path($uri);
            $uri = (string) $uri->evaluate();
        }

        if (!$this->inlinedFiles->contains($uri)) {
            // recursively inline the import
            $inliner = Concentrate_Inliner::factory(
                $this->root,
                $uri,
                $this->destinationFilename,
                $this->inlinedFiles
            );

            $content = $inliner->load($inliner->filename);
            $replacement = "\n/* at-import inlined file \"{$uri}\" */\n";
            $replacement .= $inliner->inlineImports($content);

            $this->inlinedFiles->add($uri);
        }

        return $replacement;
    }
}
