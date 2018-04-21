<?php

namespace Elbucho\Config;
use Elbucho\Config\Driver\IniDriver;
use Elbucho\Config\Driver\JsonDriver;
use Elbucho\Config\Driver\PhpDriver;
use Elbucho\Config\Driver\XmlDriver;
use Elbucho\Config\Driver\YamlDriver;

class Config implements \Serializable, \Iterator
{
    /**
     * Registered file handlers
     *
     * @access  private
     * @var     DriverInterface[]
     */
    private $drivers = array();

    /**
     * Configuration data
     *
     * @access  private
     * @var     Config[]
     */
    private $data = array();

    /**
     * Class constructor
     *
     * @access  public
     * @param   string|array    $config
     * @return  Config
     * @throws  \Exception
     */
    public function __construct($config = null)
    {
        $this->registerHandlers();

        if ( ! is_null($config)) {
            $this->data = $this->load($config);
        }

        return $this;
    }

    /**
     * Append additional files / array to Config object
     *
     * @access  public
     * @param   mixed   $input
     * @return  Config
     * @throws  \Exception
     */
    public function append($input)
    {
        $newData = $this->load($input);

        array_walk($newData, function ($value, $key) {
            if ( ! array_key_exists($key, $this->data)) {
                $this->data[$key] = $value;
            } else {
                if ( ! is_object($this->data[$key]) or ! $this->data[$key] instanceof Config) {
                    $this->data[$key] = $value;
                } else {
                    $this->data[$key]->append($value);
                }
            }
        });

        return $this;
    }

    /**
     * Save this config to a file
     *
     * @access  public
     * @param   string  $path
     * @return  bool
     * @throws  InvalidFileException
     */
    public function save($path)
    {
        $info = pathinfo($path);

        if (empty($info['extension']) or ! array_key_exists($info['extension'], $this->drivers)) {
            throw new InvalidFileException('No driver has been loaded to support this file type');
        }

        if ( ! is_dir($info['dirname'])) {
            @mkdir($info['dirname'], 0755, true);
        }

        return $this->drivers[$info['extension']]->save($this, $path);
    }

    /**
     * Return the data array as an array
     *
     * @access  public
     * @param   void
     * @return  array
     */
    public function toArray()
    {
        $data = array();

        foreach ($this->data as $key => $value) {
            if (is_object($value) and $value instanceof Config) {
                $data[$key] = $value->toArray();
            } else {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    /**
     * Return count of elements in $this->data array
     *
     * @access  public
     * @param   void
     * @return  int
     */
    public function count()
    {
        return count($this->data);
    }

    /**
     * Return a value, if it exists, otherwise return the default
     *
     * @access  public
     * @param   string  $path
     * @param   mixed   $default
     * @return  mixed
     */
    public function get($path, $default = false)
    {
        $pointer = $this;

        foreach (explode('.', $path) as $part) {
            if ( ! isset($pointer->$part)) {
                return $default;
            }

            $pointer = $pointer->$part;
        }

        return $pointer;
    }

    /**
     * Remove a key / tree of keys
     *
     * @access  public
     * @param   string  $path
     * @return  void
     */
    public function remove($path)
    {
        $pointer = $this;
        $parts = explode('.', $path);

        for ($i=0;$i<count($parts)-1;$i++) {
            if ( ! isset($pointer->{$parts[$i]})) {
                return;
            }

            $pointer = $pointer->{$parts[$i]};
        }

        unset($pointer->{$parts[count($parts)-1]});
    }

    /**
     * Magic __get method
     *
     * @access  public
     * @param   string  $key
     * @return  mixed
     */
    public function __get($key)
    {
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        return false;
    }

    /**
     * Magic __set method
     *
     * @access  public
     * @param   string  $key
     * @param   mixed   $value
     * @return  void
     */
    public function __set($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Magic __isset method
     *
     * @access  public
     * @param   string  $key
     * @return  bool
     */
    public function __isset($key)
    {
        return isset($this->data[$key]);
    }

    /**
     * Magic __unset method
     *
     * @access  public
     * @param   string  $key
     * @return  void
     */
    public function __unset($key)
    {
        unset($this->data[$key]);
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     */
    public function serialize()
    {
        return serialize($this->toArray());
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return void
     * @throws  \Exception
     */
    public function unserialize($serialized)
    {
        $this->data = $this->loadConfigFromArray(unserialize($serialized));
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     */
    public function current()
    {
        return current($this->data);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        next($this->data);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        return key($this->data);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid()
    {
        $key = key($this->data);

        return ($key !== null and $key !== false);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        reset($this->data);
    }

    /**
     * Register all available file handlers
     *
     * @access  private
     * @param   void
     * @return  void
     */
    private function registerHandlers()
    {
        $this->drivers = array(
            'ini'   => new IniDriver(),
            'php'   => new PhpDriver()
        );

        if (class_exists('SimpleXMLElement')) {
            $this->drivers['xml'] = new XmlDriver();
        }

        if (function_exists('json_decode')) {
            $this->drivers['json'] = new JsonDriver();
        }

        if (class_exists('Symfony\\Component\\Yaml\\Parser')) {
            $this->drivers['yml'] = new YamlDriver();
            $this->drivers['yaml'] = new YamlDriver();
        }
    }

    /**
     * Load data from a file or array
     *
     * @access  private
     * @param   string|array    $input
     * @return  Config[]
     * @throws  \Exception
     */
    private function load($input)
    {
        if (is_string($input) and file_exists($input)) {
            if (is_dir($input)) {
                return $this->loadConfigFromDirectory($input);
            } else {
                return $this->loadConfigFromFile($input);
            }
        } elseif (is_array($input)) {
            return $this->loadConfigFromArray($input);
        } elseif (is_object($input) and $input instanceof Config) {
            return $this->loadConfigFromArray($input->toArray());
        }

        throw new \Exception(sprintf(
            'Unsupported type provided.  Must be a path to a file / directory, array, or Config object'
        ));
    }

    /**
     * Load config from a directory
     *
     * @access  private
     * @param   string  $path
     * @return  Config[]
     * @throws  \Exception
     */
    private function loadConfigFromDirectory($path)
    {
        $return = array();

        if (($dh = @opendir($path)) === false) {
            throw new \Exception(sprintf(
                'Unable to open the directory %s',
                $path
            ));
        }

        while (($file = readdir($dh)) !== false) {
            if (preg_match("/^\.{1,2}$/", $file)) {
                continue;
            }

            $info = pathinfo($path . DIRECTORY_SEPARATOR . $file);

            if (is_dir($path . DIRECTORY_SEPARATOR . $file)) {
                if (isset($return[$info['filename']]) and $return[$info['filename']] instanceof Config) {
                    $return[$info['filename']]->append(
                        $this->loadConfigFromDirectory($path . DIRECTORY_SEPARATOR . $file)
                    );
                } else {
                    $return[$info['filename']] = new Config(
                        $this->loadConfigFromDirectory($path . DIRECTORY_SEPARATOR . $file)
                    );
                }

                continue;
            }

            try {
                if (isset($return[$info['filename']]) and $return[$info['filename']] instanceof Config) {
                    $return[$info['filename']]->append(
                        $this->loadConfigFromFile($path . DIRECTORY_SEPARATOR . $file)
                    );
                } else {
                    $return[$info['filename']] = new Config(
                        $this->loadConfigFromFile($path . DIRECTORY_SEPARATOR . $file)
                    );
                }
            } catch (InvalidFileException $e) {
                continue;
            }
        }

        return $return;
    }

    /**
     * Load config from a file
     *
     * @access  private
     * @param   string  $path
     * @return  Config[]
     * @throws  InvalidFileException|\Exception
     */
    private function loadConfigFromFile($path)
    {
        $info = pathinfo($path);

        if ( ! empty($info['extension'])) {
            if (array_key_exists($info['extension'], $this->drivers)) {
                return $this->loadConfigFromArray(
                    $this->drivers[$info['extension']]->load(
                        $path
                    )
                );
            }
        }

        throw new InvalidFileException(sprintf(
            'No valid file interpreters exist for the input file %s',
            $path
        ));
    }

    /**
     * Load config from an array
     *
     * @access  private
     * @param   array   $data
     * @return  Config[]
     * @throws  \Exception
     */
    private function loadConfigFromArray(array $data = array())
    {
        $return = array();

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $return[$key] = new Config($value);
            } else {
                $return[$key] = $value;
            }
        }

        return $return;
    }
}