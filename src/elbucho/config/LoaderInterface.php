<?php

namespace Elbucho\Config;

interface LoaderInterface
{
    /**
     * Validate that the constructor argument is correct
     *
     * @access  public
     * @param   mixed   $input
     * @return  bool
     * @throws  InvalidFileException
     */
    public function isValid($input);

    /**
     * Generate an array of keys / values from the constructor argument
     *
     * @access  public
     * @param   mixed   $input
     * @return  array
     * @throws  InvalidFileException
     */
    public function load($input);
}