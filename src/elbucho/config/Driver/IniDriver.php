<?php

namespace Elbucho\Config\Driver;
use Elbucho\Config\Config;
use Elbucho\Config\InvalidFileException;

class IniDriver extends BaseDriver
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
        $return = parse_ini_file($path, true, INI_SCANNER_TYPED);

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
        $info = pathinfo($path);

        if (empty($info['extension']) or $info['extension'] !== 'ini') {
            throw new InvalidFileException('The provided path must end in .ini');
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

        $lines = $this->transform($config->toArray());
        $output = '';

        foreach ($lines as $line) {
            $output .= sprintf(
                "%s\n",
                (preg_match("/^\[/", $line) ? "\n" . $line : $line)
            );
        }

        return (bool) file_put_contents($path, substr($output, 0, -1));
    }

    /**
     * Transform the config array into a format that is easy to transcribe to a file
     *
     * @access  private
     * @param   array   $data
     * @return  array
     */
    private function transform(array $data = array())
    {
        $base = array();
        $sections = array();
        $return = array();

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sections[$key] = $this->flatten($value);
            } else {
                $base[$key] = $value;
            }
        }

        foreach ($base as $key => $value) {
            $value = strtr($value, array("'" => "\\'"));
            $return[] = sprintf(
                '%s = %s',
                $key,
                (is_numeric($value) ? $value : "'" . $value . "'")
            );
        }

        foreach ($sections as $section => $values) {
            $return[] = sprintf(
                '[%s]',
                $section
            );

            foreach ($values as $key => $value) {
                $value = strtr($value, array("'" => "\\'"));
                $return[] = sprintf(
                    '%s = %s',
                    $key,
                    (is_numeric($value) ? $value : "'" . $value . "'")
                );
            }
        }

        return $return;
    }

    /**
     * Flatten an array into one dimension
     *
     * @access  private
     * @param   array   $data
     * @param   string  $prefix
     * @return  array
     */
    private function flatten(array $data = array(), $prefix = '')
    {
        $return = array();

        foreach ($data as $key => $value) {
            if ( ! empty($prefix)) {
                $key = sprintf(
                    "%s[%s]",
                    $prefix,
                    $key
                );
            }

            if ( ! is_array($value)) {
                $return[$key] = $value;
            } else {
                $return = array_merge($return, $this->flatten($value, $key));
            }
        }

        return $return;
    }
}