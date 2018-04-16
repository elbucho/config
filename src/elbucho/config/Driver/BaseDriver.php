<?php

namespace Elbucho\Config\Driver;
use Elbucho\Config\DriverInterface;

abstract class BaseDriver implements DriverInterface
{
    /**
     * Transform any boolean or numeric values in the source XML
     *
     * @access  protected
     * @param   string  $value
     * @return  mixed
     */
    protected function makeValue($value)
    {
        if (strtolower($value) === 'true') {
            return true;
        }

        if (strtolower($value) === 'false') {
            return false;
        }

        if (is_numeric($value)) {
            return $value + 0;
        }

        return $value;
    }

    /**
     * Transform all array values to strings for the destination XML
     *
     * @access  protected
     * @param   mixed   $value
     * @return  string
     */
    protected function makeString($value)
    {
        if ($value === true) {
            return 'true';
        }

        if ($value === false) {
            return 'false';
        }

        if ($value instanceof \DateTime) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_object($value)) {
            return serialize($value);
        }

        if (is_resource($value)) {
            return 'RESOURCE';
        }

        return (string) $value;
    }

    /**
     * Traverse an array, converting all relevant strings to the appropriate data types
     *
     * @access  protected
     * @param   array   $input
     * @return  array
     */
    protected function convertStrings(array $input = array())
    {
        $return = array();

        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $return[$key] = $this->convertStrings($value);
            } else {
                $return[$key] = $this->makeValue($value);
            }
        }

        return $return;
    }

    /**
     * Traverse an array, converting all non-string values to strings
     *
     * @access  protected
     * @param   array   $input
     * @return  array
     */
    protected function convertValues(array $input = array())
    {
        $return = array();

        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $return[$key] = $this->convertValues($value);
            } else {
                $return[$key] = $this->makeString($value);
            }
        }

        return $return;
    }
}