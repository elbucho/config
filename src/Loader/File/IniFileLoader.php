<?php

namespace Elbucho\Config\Loader\File;
use Elbucho\Config\InvalidFileException;

class IniFileLoader extends AbstractFileLoader
{
    /**
     * Parsed .ini file
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

        $contents = file_get_contents($input);

        if (empty($contents)) {
            $this->parsedData = array();

            return true;
        }

        $this->parsedData = parse_ini_string(
            $contents,
            true,
            INI_SCANNER_TYPED
        );

        if ($this->parsedData === false or ! is_array($this->parsedData) or empty($this->parsedData)) {
            throw new InvalidFileException(sprintf(
                'The provided .ini file is in an invalid format: %s',
                print_r($input, true)
            ));
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
                'Provided .ini file is not found, or is not readable: %s',
                print_r($input, true)
            ));
        }

        if (empty($this->parsedData)) {
            return array();
        }

        return $this->convertStrings($this->parsedData);
    }
}