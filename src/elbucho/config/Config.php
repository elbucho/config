<?php

namespace Elbucho\Config;

class Config implements \Serializable, \Iterator
{
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
     * @param   iterable    $data
     * @return  Config
     */
    public function __construct(iterable $data)
    {
        $this->data = $this->load($data);

        return $this;
    }

    /**
     * Append additional files / array to Config object
     *
     * @access  public
     * @param   iterable    $data
     * @return  Config
     */
    public function append(iterable $data)
    {
        $newData = $this->load($data);

        foreach ($newData as $key => $value) {
            if ($this->$key instanceof Config) {
                $this->$key->append($value);

                continue;
            }

            $this->$key = $value;
        }

        return $this;
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

        foreach ($this as $key => $value) {
            if ($value instanceof Config) {
                $data[$key] = $value->toArray();

                continue;
            }

            $data[$key] = $value;
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
     */
    public function unserialize($serialized)
    {
        $this->data = $this->load(unserialize($serialized));
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
     * Load config from an array
     *
     * @access  private
     * @param   iterable    $data
     * @return  Config[]
     */
    private function load(iterable $data)
    {
        $return = array();

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $return[$key] = new Config($value);

                continue;
            }

            $return[$key] = $value;
        }

        return $return;
    }
}