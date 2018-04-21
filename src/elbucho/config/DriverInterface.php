<?php

namespace Elbucho\Config;

interface DriverInterface
{
    /**
     * Load information from a file and return an array
     *
     * @access  public
     * @param   string  $path
     * @return  array
     * @throws  InvalidFileException
     */
    public function load($path);

    /**
     * Save a Config object into a file of this type
     *
     * @access  public
     * @param   Config  $config
     * @param   string  $path
     * @return  bool
     * @throws  InvalidFileException
     */
    public function save(Config $config, $path);
}