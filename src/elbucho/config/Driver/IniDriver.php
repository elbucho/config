<?php

namespace Elbucho\Config\Driver;
use Elbucho\Config\Config;
use Elbucho\Config\InvalidFileException;

class IniDriver extends BaseDriver
{
    /**
     * Return an array of valid file extensions for this class
     *
     * @access  public
     * @param   void
     * @return  array|false // e.g. ['php']
     */
    public function getExtensions()
    {
        return array('ini');
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
        $return = parse_ini_file($path, true, INI_SCANNER_NORMAL);

        if ( ! is_array($return)) {
            throw new InvalidFileException('Provided .ini file is not in a valid format');
        }

        return $this->convertStrings($return);
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

    }
}