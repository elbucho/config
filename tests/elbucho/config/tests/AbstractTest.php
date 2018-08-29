<?php

namespace Elbucho\Config\Tests;
use Elbucho\Config\InvalidFileException;
use Elbucho\Config\InvalidTypeException;
use PHPUnit\Framework\TestCase;
use Elbucho\Config\Config;

abstract class AbstractTest extends TestCase
{
    const CONFIG_DIR = __DIR__ . '/../docs';
    const TYPE_STRING = 'string';
    const TYPE_INT = 'int';
    const TYPE_FLOAT = 'float';
    const TYPE_CONFIG = 'config';
    const TYPE_BOOL = 'boolean';

    /**
     * Config object
     *
     * @static
     * @access  protected
     * @var     Config
     */
    static protected $config;

    /**
     * Return a valid config file path
     *
     * @abstract
     * @access  protected
     * @param   void
     * @return  string
     */
    abstract protected function getValidConfigPath();

    /**
     * Return an invalid config file path
     *
     * @abstract
     * @access  protected
     * @param   void
     * @return  string
     */
    abstract protected function getInvalidConfigPath();

    /**
     * Return an array of keys / scalar values that exist at top level of config
     *
     * @abstract
     * @access  protected
     * @param   void
     * @return  array   // ['test1' => 'foo', 'test2' => 'bar']
     */
    abstract protected function getTopLevelScalars();

    /**
     * Return an array of nested scalar values that exist in the config
     *
     * @abstract
     * @access  protected
     * @param   void
     * @return  array   // ['test1.test2' => 'foo', 'test2.test3.test4' => 'bar']
     */
    abstract protected function getNestedScalars();

    /**
     * Return an array of keys / types that exist at top level of config
     *
     * @abstract
     * @access  protected
     * @param   void
     * @return  array   // ['test1' => self::TYPE_STRING, 'test2' => self::TYPE_BOOL]
     */
    abstract protected function getTopLevelTypes();

    /**
     * Return an array of keys / types that exist at top level of config
     *
     * @abstract
     * @access  protected
     * @param   void
     * @return  array   // ['test1.test2' => self::TYPE_STRING, 'test2.test3.test4' => self::TYPE_BOOL]
     */
    abstract protected function getNestedTypes();

    /**
     * Load an invalid config type
     *
     * @access  public
     * @param   void
     */
    public function testLoadOfInvalidType()
    {
        $error = false;

        try {
            new Config(self::CONFIG_DIR . '/test_invalid_type.xyz');
        } catch (InvalidTypeException $e) {
            $error = true;
        } catch (InvalidFileException $e) {
            $this->fail('Received an invalid file exception instead of invalid type');
        }

        $this->assertTrue($error);
    }

    /**
     * Load an invalid config file
     *
     * @access  public
     * @param   void
     */
    public function testLoadOfInvalidFile()
    {
        $error = false;

        try {
            new Config($this->getInvalidConfigPath());
        } catch (InvalidFileException $e) {
            $error = true;
        } catch (InvalidTypeException $e) {
            $this->fail('Received an invalid type exception instead of invalid file');
        }

        $this->assertTrue($error);
    }

    /**
     * Load the requested config
     *
     * @access  public
     * @param   void
     */
    public function testLoadValidFile()
    {
        try {
            self::$config = new Config($this->getValidConfigPath());
        } catch (InvalidFileException $e) {
            $this->fail($e->getMessage());
        } catch (InvalidTypeException $e) {
            $this->fail($e->getMessage());
        }

        $this->assertNotNull(self::$config);
        $this->assertEquals(Config::class, get_class(self::$config));
    }

    /**
     * Test the magic __get function
     *
     * @access  public
     * @param   void
     */
    public function testMagicGet()
    {
        foreach ($this->getTopLevelScalars() as $key => $value) {
            $this->assertEquals($value, self::$config->$key);
        }

        foreach ($this->getTopLevelTypes() as $key => $value) {
            switch($value) {
                case self::TYPE_STRING:
                    $this->assertTrue(is_string(self::$config->$key));
                    break;
                case self::TYPE_INT:
                    $this->assertTrue(is_int(self::$config->$key));
                    break;
                case self::TYPE_FLOAT:
                    $this->assertTrue(is_float(self::$config->$key));
                    break;
                case self::TYPE_BOOL:
                    $this->assertTrue(is_bool(self::$config->$key));
                    break;
                case self::TYPE_CONFIG:
                    $this->assertTrue(is_object(self::$config->$key));
                    $this->assertEquals(Config::class, get_class(self::$config->$key));
                    break;
                default:
                    $this->fail(sprintf(
                        'Undefined type: %s',
                        $value
                    ));
            }
        }

        foreach ($this->getNestedScalars() as $key => $value) {
            $pointer = clone(self::$config);

            foreach (explode('.', $key) as $part) {
                $this->assertTrue(isset($pointer->$part));
                $pointer = $pointer->$part;
            }

            $this->assertEquals($value, $pointer);
        }

        foreach ($this->getNestedTypes() as $key => $value) {
            $pointer = clone(self::$config);

            foreach (explode('.', $key) as $part) {
                $this->assertTrue(isset($pointer->$part));
                $pointer = $pointer->$part;
            }

            switch($value) {
                case self::TYPE_STRING:
                    $this->assertTrue(is_string($pointer));
                    break;
                case self::TYPE_INT:
                    $this->assertTrue(is_int($pointer));
                    break;
                case self::TYPE_FLOAT:
                    $this->assertTrue(is_float($pointer));
                    break;
                case self::TYPE_BOOL:
                    $this->assertTrue(is_bool($pointer));
                    break;
                case self::TYPE_CONFIG:
                    $this->assertTrue(is_object($pointer));
                    $this->assertEquals(Config::class, get_class($pointer));
                    break;
                default:
                    $this->fail(sprintf(
                        'Undefined type: %s',
                        $value
                    ));
            }
        }
    }

    /**
     * Test the magic __set function
     *
     * @access  public
     * @param   void
     */
    public function testMagicSet()
    {
        self::$config->{'foo'} = 'bar';

        $this->assertTrue(isset(self::$config->{'foo'}));
        $this->assertEquals('bar', self::$config->{'foo'});

        self::$config->{'foo'} = 1;

        $this->assertEquals(1, self::$config->{'foo'});

        try {
            self::$config->{'foo'} = new Config(array('bar' => 1234));
        } catch (\Exception $e) {
            $this->fail('Unable to create a new Config object');
        }

        $this->assertTrue(is_object(self::$config->{'foo'}));
        $this->assertTrue(isset(self::$config->{'foo'}->{'bar'}));
        $this->assertEquals(1234, self::$config->{'foo'}->{'bar'});

        self::$config->{'foo'}->{'bar'} = false;

        $this->assertFalse(self::$config->{'foo'}->{'bar'});
    }
}