<?php

namespace Elbucho\Config\Driver;
use Elbucho\Config\Config;
use Elbucho\Config\InvalidFileException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Dumper;

class YamlDriver extends BaseDriver
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
                'Unable to load the file located at %s',
                $path
            ));
        }

        $parser = new Parser();

        try {
            return $parser->parseFile($path);
        } catch (ParseException $e) {
            throw new InvalidFileException(sprintf(
                'Unable to parse the contents of the file located at %s',
                $path
            ));
        }
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

        if (empty($info['extension']) or ! in_array($info['extension'], array('yml','yaml'))) {
            throw new InvalidFileException('The provided path must end in .yml or .yaml');
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

        $dumper = new Dumper();
        $output = $dumper->dump($config->toArray());

        return (bool) file_put_contents($path, $output);
    }
}