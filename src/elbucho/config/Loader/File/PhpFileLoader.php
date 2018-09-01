<?php

namespace Elbucho\Config\Driver;
use Elbucho\Config\InvalidFileException;
use Elbucho\Config\Loader\File\AbstractFileLoader;

class PhpFileLoader extends AbstractFileLoader
{
    /**
     * Parsed .php file
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

        ob_start();

        /** @noinspection PhpIncludeInspection */
        $this->parsedData = @require($input);

        $output = ob_get_clean();

        if ( ! is_array($this->parsedData) or ! empty($output)) {
            throw new InvalidFileException(sprintf(
                'The provided .php file is in an invalid format: %s',
                sprintf($input, true)
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
                'Provided .php file is not found, or is not readable: %s',
                print_r($input, true)
            ));
        }

        if (empty($this->parsedData)) {
            return array();
        }

        return $this->parsedData;
    }
}