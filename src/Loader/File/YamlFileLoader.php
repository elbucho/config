<?php

namespace Elbucho\Config\Loader\File;
use Elbucho\Config\InvalidFileException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;

class YamlFileLoader extends AbstractFileLoader
{
    /**
     * Parsed .yaml file
     *
     * @access  private
     * @var     array
     */
    private $parsedData = array();

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

        $parser = new Parser();
        $exception = false;

        try {
            $this->parsedData = $parser->parseFile(
                $input,
                Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE
            );
        } catch (ParseException $e) {
            $exception = true;
        }

        if ( ! is_array($this->parsedData) or $exception === true) {
            throw new InvalidFileException(sprintf(
                'The provided .yaml file is in an invalid format: %s',
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
                'Provided .yaml file is not found, or is not readable: %s',
                print_r($input, true)
            ));
        }

        return $this->parsedData;
    }
}