<?php

namespace Elbucho\Config\Tests;
use Elbucho\Config\InvalidFileException;
use Elbucho\Config\Loader\DirectoryLoader;
use Elbucho\Config\Loader\File\IniFileLoader;
use PHPUnit\Framework\TestCase;
use Elbucho\Config\Config;

class DirectoryTest extends TestCase
{
    /**
     * Directory data provider
     *
     * @access  public
     * @param   void
     * @return  array
     */
    public function directoryProvider()
    {
        return array(
            array(
                1, true
            ),
            array(
                dirname(__DIR__) . '/invalid_directory', true
            ),
            array(
                '/dev/null', true
            ),
            array(
                dirname(__DIR__) . '/docs', false
            )
        );
    }

    /**
     * Test the opening of various file paths
     *
     * @access          public
     * @param           mixed       $path
     * @param           bool        $isError
     * @dataProvider    directoryProvider
     */
    public function testLoadPath($path, $isError)
    {
        $loader = new DirectoryLoader();
        $error = false;

        try {
            $loader->load($path);
        } catch (InvalidFileException $e) {
            $error = true;
        }

        $this->assertEquals($isError, $error);
    }

    /**
     * Load the config directory, test that all valid keys exist and are Config classes
     *
     * @access  public
     * @param   void
     */
    public function testDirectoryLoad()
    {
        $configDir = dirname(__DIR__) . '/docs';
        $loader = new DirectoryLoader();

        try {
            $config = new Config(
                $loader->load($configDir)
            );
        } catch (InvalidFileException $e) {
            $this->fail($e->getMessage());
        }

        foreach (array('ini','json','php','xml','yaml') as $key) {
            $key = sprintf('%s_test', $key);

            $this->assertTrue(isset($config->$key));
            $this->assertInstanceOf(Config::class, $config->$key);
            $this->assertGreaterThan(1, $config->$key->count());
        }
    }

    /**
     * Test a nested directory load
     *
     * @access  public
     * @param   void
     */
    public function testNestedDirectoryLoad()
    {
        $configDir = dirname(__DIR__) . '/docs';
        $newDir = $configDir . '/footest';
        $loader = new DirectoryLoader();
        $exception = null;

        @mkdir($newDir, 0755);
        file_put_contents(
            $newDir . '/foo.ini',
            sprintf(
                "%s = %d\n",
                'bar',
                15
            )
        );

        try {
            $config = new Config(
                $loader->load($configDir)
            );

            $this->assertEquals(
                15,
                $config->get('footest.foo.bar')
            );
        } catch (InvalidFileException $e) {
            $exception = $e;
        }

        @unlink($newDir . '/foo.ini');
        @rmdir($newDir);

        if ( ! is_null($exception)) {
            $this->fail($exception->getMessage());
        }
    }

    /**
     * Test registering a new loader
     *
     * @access  public
     * @param   void
     */
    public function testRegisterLoader()
    {
        $exception = null;
        $configDir = dirname(__DIR__) . '/docs';
        $loader = new DirectoryLoader();
        $loader->registerLoader(
            new IniFileLoader(),
            'invalid'
        );

        file_put_contents(
            $configDir . '/foo_test_file.invalid',
            sprintf(
                "%s = %d\n",
                'bar',
                15
            )
        );

        try {
            $config = new Config(
                $loader->load($configDir)
            );

            $this->assertTrue(isset($config->{'foo_test_file'}));
            $this->assertInstanceOf(
                Config::class,
                $config->{'foo_test_file'}
            );
            $this->assertEquals(
                15,
                $config->get('foo_test_file.bar')
            );
        } catch (InvalidFileException $e) {
            $exception = $e;
        }

        @unlink($configDir . '/foo_test_file.invalid');

        if ( ! is_null($exception)) {
            $this->fail($exception->getMessage());
        }
    }
}