<?php

namespace Elbucho\Config\Driver;
use Elbucho\Config\Config;
use Elbucho\Config\DriverInterface;
use Elbucho\Config\InvalidFileException;

class JsonDriver implements DriverInterface
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
        if (function_exists('json_decode')) {
            return array('json');
        }

        return false;
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
        if (($payload = file_get_contents($path)) === false) {
            throw new InvalidFileException(sprintf(
                'Unable to read the contents of the file %s',
                $path
            ));
        }

        $return = json_decode(trim($payload), true);

        if (empty($return)) {
            throw new InvalidFileException(sprintf(
                'The provided file %s does not contain valid JSON',
                $path
            ));
        }

        if ( ! is_array($return)) {
            return array($return);
        }

        return $return;
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

        if (empty($info['extension']) or $info['extension'] !== 'json') {
            throw new InvalidFileException('The provided path must end in .json');
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

        return (bool) file_put_contents(
            $path,
            json_encode(
                $config->toArray(),
                JSON_PRETTY_PRINT,
                9999
            )
        );
    }
}