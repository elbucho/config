<?php

namespace Elbucho\Config\Tests;

class XmlTest extends AbstractTest
{
    /**
     * Return a valid config file path
     *
     * @access  protected
     * @param   void
     * @return  string
     */
    protected function getValidConfigPath()
    {
        return self::CONFIG_DIR . '/xml_test.xml';
    }

    /**
     * Return an invalid config file path
     *
     * @access  protected
     * @param   void
     * @return  string
     */
    protected function getInvalidConfigPath()
    {
        return self::CONFIG_DIR . '/xml_test_invalid.xml';
    }

    /**
     * Return an array of keys / scalar values that exist at top level of config
     *
     * @access  protected
     * @param   void
     * @return  array   // ['test1' => 'foo', 'test2' => 'bar']
     */
    protected function getTopLevelScalars()
    {
        return array(
            'test5' => false,
            'test9' => 'foo'
        );
    }

    /**
     * Return an array of nested scalar values that exist in the config
     *
     * @access  protected
     * @param   void
     * @return  array   // ['test1.test2' => 'foo', 'test2.test3.test4' => 'bar']
     */
    protected function getNestedScalars()
    {
        return array(
            'test1.test2'       => true,
            'test1.test3.test4' => 'asdf',
            'test1.test3.test5' => 'fdsa',
            'test6.test7'       => 0.75,
            'test6.test8'       => 15
        );
    }

    /**
     * Return an array of keys / types that exist at top level of config
     *
     * @access  protected
     * @param   void
     * @return  array   // ['test1' => self::TYPE_STRING, 'test2' => self::TYPE_BOOL]
     */
    protected function getTopLevelTypes()
    {
        return array(
            'test1' => self::TYPE_CONFIG,
            'test5' => self::TYPE_BOOL,
            'test9' => self::TYPE_STRING
        );
    }

    /**
     * Return an array of keys / types that exist at top level of config
     *
     * @access  protected
     * @param   void
     * @return  array   // ['test1.test2' => self::TYPE_STRING, 'test2.test3.test4' => self::TYPE_BOOL]
     */
    protected function getNestedTypes()
    {
        return array(
            'test1.test2'       => self::TYPE_BOOL,
            'test1.test3'       => self::TYPE_CONFIG,
            'test1.test3.test4' => self::TYPE_STRING,
            'test6.test7'       => self::TYPE_FLOAT,
            'test6.test8'       => self::TYPE_INT
        );
    }
}