<?php

namespace Elbucho\Config\Driver;
use Elbucho\Config\Config;
use Elbucho\Config\DriverInterface;
use Elbucho\Config\InvalidFileException;
use Doctrine\Common\Inflector\Inflector;

class XmlDriver implements DriverInterface
{
    /**
     * Return an array of valid file extensions for this class
     *
     * @access  public
     * @param   void
     * @return  array   // e.g. ['php']
     */
    public function getExtensions()
    {
        return array('xml');
    }

    /**
     * Load information from a file and return an array
     *
     * @access  public
     * @param   string $path
     * @return  array
     * @throws  InvalidFileException
     */
    public function load($path)
    {
        if ( ! file_exists($path) or ! is_readable($path)) {
            throw new InvalidFileException(sprintf(
                'The xml file %s is not readable',
                $path
            ));
        }

        if (($xml = simplexml_load_file($path)) === false) {
            throw new InvalidFileException(sprintf(
                'The xml file %s does not contain valid XML',
                $path
            ));
        }

        return $this->parseXml($xml);
    }

    /**
     * Save a Config object into a file of this type
     *
     * @access  public
     * @param   Config $config
     * @param   string $path
     * @return  bool
     * @throws  InvalidFileException
     */
    public function save(Config $config, $path)
    {
        $info = pathinfo($path);

        if (empty($info['extension']) or $info['extension'] !== 'xml') {
            throw new InvalidFileException('The provided path must end in .xml');
        }

        if ( ! is_dir($info['dirname'])) {
            @mkdir($info['dirname'], 0755, true);
        }

        if ( ! is_dir($info['dirname'])) {
            throw new InvalidFileException('Unable to create the target directory');
        }

        if ( ! is_writable($info['dirname'])) {
            throw new InvalidFileException('Unable to write to the target directory');
        }

        $topElement = Inflector::pluralize($info['filename']);
        $xml = new \SimpleXMLElement(sprintf(
            '<?xml version="1.0"?><%s></%s>',
            $topElement,
            $topElement
        ));

        $this->parseArray($config->toArray(), $xml);
        return (bool) $xml->asXML($path);
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

    /**
     * Parse an array into a SimpleXMLElement Object
     *
     * @access  private
     * @param   array               $data
     * @param   \SimpleXMLElement   &$xml
     * @return  void
     */
    private function parseArray(array $data = array(), \SimpleXMLElement &$xml)
    {
        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                $key = 'item' . $key;
            }

            if (is_array($value)) {
                $subnode = $xml->addChild($key);
                $this->parseArray($value, $subnode);
            } else {
                $xml->addChild($key, $this->makeString($value));
            }
        }
    }

    /**
     * Transform any boolean or numeric values in the source XML
     *
     * @access  private
     * @param   string  $value
     * @return  mixed
     */
    private function makeValue($value)
    {
        if (strtolower($value) === 'true') {
            return true;
        }

        if (strtolower($value) === 'false') {
            return false;
        }

        if (is_numeric($value)) {
            return $value + 0;
        }

        return $value;
    }

    /**
     * Transform all array values to strings for the destination XML
     *
     * @access  private
     * @param   mixed   $value
     * @return  string
     */
    private function makeString($value)
    {
        if ($value === true) {
            return 'true';
        }

        if ($value === false) {
            return 'false';
        }

        if ($value instanceof \DateTime) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_object($value)) {
            return serialize($value);
        }

        if (is_resource($value)) {
            return 'RESOURCE';
        }

        return (string) $value;
    }
}