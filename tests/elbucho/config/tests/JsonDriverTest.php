<?php

declare(strict_types=1);
namespace Elbucho\Config\Tests;
use Elbucho\Config\InvalidFileException;
use PHPUnit\Framework\TestCase;
use Elbucho\Config\Config;

final class JsonDriverTest extends TestCase
{
    /* @var Config $config */
    protected static $config;

    public function testLoad()
    {
        try {
            static::$config = new Config(__DIR__ . '/../docs/json_test.json');
        } catch (\Exception $e) {
            self::fail($e->getMessage());
        }

        $this->assertInstanceOf(Config::class, static::$config);
        $this->assertInstanceOf(Config::class, static::$config->get('test1'));
        $this->assertInstanceOf(Config::class, static::$config->get('test6'));
        $this->assertInstanceOf(Config::class, static::$config->get('test1.test3'));
        $this->assertEquals('asdf', static::$config->get('test1.test3.test4'));
        $this->assertEquals('fdsa', static::$config->get('test1.test3.test5'));
        $this->assertEquals(0.75, static::$config->get('test6.test7'));
        $this->assertTrue(static::$config->get('test1.test2'));
        $this->assertFalse(static::$config->get('test5'));

        $error = false;

        try {
            static::$config = new Config(__DIR__ . '/../docs/text_test.txt');
        } catch (\Exception $e) {
            $error = true;
        }

        $this->assertTrue($error);

        $error = false;

        try {
            static::$config = new Config(new \DateTime('now'));
        } catch (\Exception $e) {
            $error = true;
        }

        $this->assertTrue($error);
    }

    public function testGet()
    {
        $this->assertFalse(static::$config->get('not_real_key'));
        $this->assertEquals('asdf', static::$config->get('not_real_key', 'asdf'));
    }

    public function testAppend()
    {
        try {
            static::$config->append(
                new Config(array('test8' => 'qwer', 'test9' => 'rewq'))
            );
        } catch (\Exception $e) {
            self::fail($e->getMessage());
        }

        $this->assertEquals('qwer', static::$config->get('test8'));
        $this->assertEquals('rewq', static::$config->get('test9'));

        try {
            static::$config->append(
                new Config(array('test9' => array('test10' => 'rewq')))
            );

            static::$config->append(
                new Config(array('test9' => array('test11' => 'asdf')))
            );
        } catch (\Exception $e) {
            self::fail($e->getMessage());
        }

        $this->assertEquals('rewq', static::$config->get('test9.test10'));
        $this->assertEquals('asdf', static::$config->get('test9.test11'));
    }

    public function testRemove()
    {
        $this->assertEquals('rewq', static::$config->get('test9.test10'));

        static::$config->remove('test9');

        $this->assertFalse(static::$config->get('test9'));

        static::$config->remove('test1.test3');

        $this->assertFalse(static::$config->{'test1'}->{'test3'});

        static::$config->remove('test1.test3.test4');
    }

    public function testSet()
    {
        $this->assertFalse(static::$config->get('test3.test4'));

        try {
            static::$config->{'test3'} = new Config(array('test4' => 'asdf'));
        } catch (\Exception $e) {
            self::fail($e->getMessage());
        }

        $this->assertEquals('asdf', static::$config->get('test3.test4'));
    }

    public function testCount()
    {
        $this->assertEquals(1, static::$config->{'test3'}->count());

        static::$config->{'test3'}->{'test10'} = 'fdsa';

        $this->assertEquals(2, static::$config->{'test3'}->count());
    }

    public function testIterate()
    {
        $count = 0;

        foreach (static::$config as $key => $value) {
            $count++;
        }

        $this->assertNotEquals(0, $count);
    }

    public function testLoadDirectory()
    {
        try {
            static::$config = new Config(__DIR__ . '/../docs/');
        } catch (\Exception $e) {
            self::fail($e->getMessage());
        }

        $this->assertInstanceOf(Config::class, static::$config->get('json_test'));
    }

    public function testSerialize()
    {
        $serial = unserialize(serialize(static::$config));

        $this->assertInstanceOf(Config::class, $serial);
        $this->assertInstanceOf(Config::class, $serial->{'json_test'});
        $this->assertInstanceOf(Config::class, $serial->get('json_test.test1'));
    }

    public function testSave()
    {
        try {
            static::$config->save(__DIR__ . '/../docs/json_test_1.json');
        } catch (InvalidFileException $e) {
            self::fail($e->getMessage());
        }

        $json = json_decode(file_get_contents(__DIR__ . '/../docs/json_test_1.json'), true);
        $this->assertNotEmpty($json);
        $this->assertNotEmpty($json['json_test']);
        $this->assertNotEmpty($json['json_test']['test1']);

        unlink(__DIR__ . '/../docs/json_test_1.json');
    }
}