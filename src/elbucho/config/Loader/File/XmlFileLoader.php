<?php

namespace Elbucho\Config\Driver;
use Elbucho\Config\InvalidFileException;
use Elbucho\Config\Loader\File\AbstractFileLoader;

class XmlFileLoader extends AbstractFileLoader
{
    /**
     * Parsed .xml file
     *
     * @access  private
     * @var     \SimpleXMLElement
     */
    private $parsedData;

    /**
     * Validate that the constructor argument is correct
     *
     * @access  public
     * @param   mixed   $input
     * @return  bool
     * @throws  InvalidFileException
     */
    public function isValid($input)
    {
        if ( ! is_string($input)) {
            return false;
        }

        if ( ! file_exists($input)) {
            return false;
        }

        if ( ! is_readable($input)) {
            return false;
        }

        /** @noinspection PhpIncludeInspection */
        if (($this->parsedData = @simplexml_load_file($input)) === false) {
            throw new InvalidFileException(sprintf(
                'The provided .xml file is in an invalid format: %s',
                sprintf($input, true)
            ));
        }

        return true;
    }

    /**
     * Load information from a file and return an array
     *
     * @access  public
     * @param   string  $input
     * @return  array
     * @throws  InvalidFileException
     */
    public function load($input)
    {
        if ( ! $this->isValid($input)) {
            throw new InvalidFileException(sprintf(
                'Provided .xml file is not found, or is not readable: %s',
                print_r($input, true)
            ));
        }

        return $this->parseXml($this->parsedData);
    }

    /**
     * Parse a SimpleXMLElement object into an array
     *
     * @access  private
     * @param   \SimpleXMLElement    $xml
     * @return  mixed
     */
    private function parseXml(\SimpleXMLElement $xml)
    {
        $return = array();

        if ($xml->attributes()->count() > 0) {
            foreach ($xml->attributes() as $key => $value) {
                $return[$key] = $this->makeValue((string) $value);
            }
        }

        if ($xml->children()->count() > 0) {
            foreach ($xml->children() as $child) {
                /* @var \SimpleXMLElement $child */
                $return[$child->getName()] = $this->parseXml($child);
            }
        }

        if (empty($return)) {
            $value = trim((string) $xml);

            if (empty($value)) {
                return true;
            }

            return $this->makeValue($value);
        }

        return $return;
    }
}