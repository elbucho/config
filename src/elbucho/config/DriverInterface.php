<?php

namespace Elbucho\Config;

interface DriverInterface
{
    /**
     * Return an array of valid file extensions for this class
     *
     * @access  public
     * @param   void
     * @return  array   // e.g. ['php']
     */
    public function getExtensions();

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