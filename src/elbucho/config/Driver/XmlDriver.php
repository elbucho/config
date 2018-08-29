<?php

namespace Elbucho\Config\Driver;
use Elbucho\Config\Config;
use Elbucho\Config\InvalidFileException;
use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\CachedWordInflector;
use Doctrine\Inflector\RulesetInflector;
use Doctrine\Inflector\Rules\English\Rules;

class XmlDriver extends BaseDriver
{
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

        $inflector = new Inflector(
            new CachedWordInflector(new RulesetInflector(
                Rules::getSingularRuleset()
            )),
            new CachedWordInflector(new RulesetInflector(
                Rules::getPluralRuleset()
            ))
        );

        $topElement = $inflector->pluralize($info['filename']);
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
    private function parseArray(array $data, \SimpleXMLElement &$xml)
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
}