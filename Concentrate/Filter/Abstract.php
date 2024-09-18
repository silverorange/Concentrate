<?php

/**
 * @category  Tools
 *
 * @author    Michael Gauthier <mike@silverorange.com>
 * @copyright 2012-2022 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class Concentrate_Filter_Abstract
{
    /**
     * @var Concentrate_FilterAbstract
     */
    protected $nextFilter;

    /**
     * Whether or not this filter is suitable to run for the given file type.
     *
     * @param string $type the file type
     *
     * @return bool true if this filter is suitable to run, otherwise false
     */
    abstract public function isSuitable(string $type = ''): bool;

    /**
     * Applies this filter to an input string, producing an output string.
     *
     * @param string $input the input string
     * @param string $type  optional. The type of the input.
     *
     * @return string the filtered output
     */
    public function filter(string $input, string $type = ''): string
    {
        $output = $input;

        if ($this->isSuitable($type)) {
            $output = $this->filterImplementation($output, $type);
        }

        if ($this->nextFilter instanceof Concentrate_Filter_Abstract) {
            $output = $this->nextFilter->filter($output, $type);
        }

        return $output;
    }

    /**
     * Applies this filter to a file's content and saves the output to another
     * file.
     *
     * @param string $fromFilename the input file
     * @param string toFilename    the output file
     * @param string $type optional. The type of the input file.
     *
     * @return self this filter
     */
    public function filterFile(
        string $fromFilename,
        string $toFilename,
        string $type = ''
    ): self {
        if (!is_readable($fromFilename)) {
            throw new Concentrate_FileException(
                "Could not read {$fromFilename} for filtering.",
                0,
                $fromFilename
            );
        }

        $path = new Concentrate_Path($toFilename);
        $path->writeDirectory();

        $content = file_get_contents($fromFilename);
        $content = $this->filter($content, $type);
        file_put_contents($toFilename, $content);

        return $this;
    }

    public function setNextFilter(Concentrate_Filter_Abstract $filter): self
    {
        $this->nextFilter = $filter;

        return $this;
    }

    /**
     * Adds a filter to the end of the filter chain for this filter.
     *
     * @param Concentrate_Filter_Abstract $filter the filter to add
     *
     * @return Concentrate_Filter_Abstract the added filter
     */
    public function chain(
        Concentrate_Filter_Abstract $filter
    ): Concentrate_Filter_Abstract {
        if ($this->nextFilter instanceof Concentrate_Filter_Abstract) {
            return $this->nextFilter->chain($filter);
        }

        $this->setNextFilter($filter);

        return $filter;
    }

    public function clearNextFilter(): self
    {
        $this->nextFilter = null;

        return $this;
    }

    /**
     * Gets a filter from the fitler chain by its class name.
     *
     * @param string $class the class name of the filter to get
     *
     * @return Concentrate_Filter_Abstract the first filter in the chain that
     *                                     matches the requested class or null if no such filter exists
     */
    public function get(string $class): ?Concentrate_Filter_Abstract
    {
        if (!is_subclass_of($class, Concentrate_Filter_Abstract::class)) {
            throw new Exception(
                '"' . $class . '" is not a subclass of '
                . Concentrate_Filter_Abstract::class . '.'
            );
        }

        if ($this instanceof $class) {
            return $this;
        }

        if ($this->nextFilter instanceof Concentrate_Filter_Abstract) {
            return $this->nextFilter->get($class);
        }

        return null;
    }

    /**
     * Gets an id for this filter.
     */
    public function getId(string $type = ''): string
    {
        return static::class;
    }

    /**
     * Gets a full id for this filter's filter chain.
     *
     * This can be used as a cache identifier.
     */
    public function getChainId(string $type = ''): string
    {
        return implode('-', $this->getIds($type));
    }

    protected function getIds(string $type = ''): array
    {
        $ids = [];

        if ($this->isSuitable($type)) {
            $ids[] = $this->getId($type);
        }

        if ($this->nextFilter instanceof Concentrate_Filter_Abstract) {
            return array_merge($ids, $this->nextFilter->getIds($type));
        }

        return $ids;
    }

    abstract protected function filterImplementation(
        string $input,
        string $type = ''
    ): string;
}
