<?php

namespace Elbucho\Config\Loader\File;
use Elbucho\Config\InvalidFileException;

class JsonFileLoader extends AbstractFileLoader
{
    /**
     * Parsed .json file
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

        $payload = file_get_contents($input);

        if ($payload === false) {
            return false;
        }

        $parsed = json_decode(
            trim($payload),
            true
        );

        if (is_null($parsed)) {
            throw new InvalidFileException(sprintf(
                'The provided .json file is in an invalid format: %s',
                print_r($input, true)
            ));
        }

        if ( ! is_array($parsed)) {
            $this->parsedData = array($parsed);
        } else {
            $this->parsedData = $parsed;
        }

        return true;
    }

    /**
     * Load information from a file and return an array
     *
     * @access  public
     * @param   mixed   $input
     * @return  array
     * @throws  InvalidFileException
     */
    public function load($input)
    {
        if ( ! $this->isValid($input)) {
            throw new InvalidFileException(sprintf(
                'Provided .json file is not found, or is not readable: %s',
                print_r($input, true)
            ));
        }

        if (empty($this->parsedData)) {
            return array();
        }

        return $this->parsedData;
    }
}