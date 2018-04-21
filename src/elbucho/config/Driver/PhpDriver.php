<?php

namespace Elbucho\Config\Driver;
use Elbucho\Config\Config;
use Elbucho\Config\InvalidFileException;

class PhpDriver extends BaseDriver
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
        if (file_exists($path)) {
            /** @noinspection PhpIncludeInspection */
            $return = @require($path);

            if (is_array($return)) {
                return $return;
            }
        }

        throw new InvalidFileException(sprintf(
            'The provided PHP file %s is either un-readable, or does not return an array',
            $path
        ));
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

        if (empty($info['extension']) or $info['extension'] !== 'php') {
            throw new InvalidFileException('The provided path must end in .php');
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

        $header = '<?php' . "\n\n" . 'return array(';
        $body = $this->convertToString($config->toArray());
        $footer = ');';

        return (bool) file_put_contents(
            $path,
            sprintf(
                "%s\n%s\n%s",
                $header,
                $body,
                $footer
            )
        );
    }

    /**
     * Convert an array of values into a PHP array string
     *
     * @access  private
     * @param   array   $data
     * @param   int     $indent
     * @return  string
     */
    private function convertToString(array $data = array(), $indent = 1)
    {
        $return = '';

        foreach ($data as $key => $value) {
            for ($i=0;$i<$indent;$i++) {
                $return .= "\t";
            }

            if (is_array($value)) {
                $return .= $key . "\t=>\tarray(\n";
                $return .= $this->convertToString($value, $indent + 1);

                for ($i=0;$i<$indent;$i++) {
                    $return .= "\t";
                }

                $return .= "),\n";
            } else {
                $return .= $key . "\t=>\t" . $value . ",\n";
            }
        }

        return substr($return, 0, -2);
    }
}