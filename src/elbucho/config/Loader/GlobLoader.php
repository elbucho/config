<?php

namespace Elbucho\Config\Loader;
use Elbucho\Config\InvalidFileException;

final class GlobLoader extends AbstractLoader
{
    /**
     * Base directory
     *
     * @access  private
     * @var     string
     */
    private $baseDirectory;

    /**
     * Validate that the constructor argument is correct
     *
     * @access  public
     * @param   mixed $input
     * @return  bool
     */
    public function isValid($input)
    {
        if ( ! $this->isValidRegex($input)) {
            return false;
        }

        $this->baseDirectory = $this->getBaseDirectory($input);

        if ($this->baseDirectory === false) {
            return false;
        }

        if ( ! is_dir($this->baseDirectory)) {
            return false;
        }

        if ( ! is_readable($this->baseDirectory)) {
            return false;
        }

        return true;
    }

    /**
     * Generate an array of keys / values from the constructor argument
     *
     * @access  public
     * @param   mixed $input
     * @return  array
     * @throws  InvalidFileException
     */
    public function load($input)
    {
        if ( ! $this->isValid($input)) {
            throw new InvalidFileException(sprintf(
                'The glob string provided is invalid: %s',
                print_r($input, true)
            ));
        }

        return $this->loadGlob($input);
    }

    /**
     * Determine if the glob regex is valid
     *
     * @access  private
     * @param   string  $pattern
     * @return  bool
     */
    private function isValidRegex($pattern)
    {
        set_error_handler(function() {}, E_WARNING);
        $isValid = preg_match($pattern, '') !== false;
        restore_error_handler();

        return $isValid;
    }

    /**
     * Return the base directory from a glob regular expression
     *
     * @access  private
     * @param   string  $pattern
     * @return  string|false
     */
    private function getBaseDirectory($pattern)
    {
        preg_match(
            '/^(?P<path>[\/a-z0-9\_\-\.]*)/',
            $pattern,
            $matches
        );

        if (empty($matches['path'])) {
            return __DIR__;
        }

        $lastSlash = strrpos($matches['path'], '/');

        if ($lastSlash === false or $lastSlash == 0) {
            return __DIR__;
        }

        $path = substr($matches['path'], 0, $lastSlash);

        if ( ! preg_match("/^\//", $path)) {
            $path = __DIR__ . '/' . $path;
        }

        return realpath($path);
    }

    /**
     * Return an array of data from the glob pattern
     *
     * @access  private
     * @param   string  $pattern
     * @return  array
     * @throws  InvalidFileException
     */
    private function loadGlob($pattern)
    {
        $directoryLoader = new DirectoryLoader();

        if (($glob = glob($pattern)) === false) {
            throw new InvalidFileException(sprintf(
                'The glob string provided is invalid: %s',
                print_r($pattern, true)
            ));
        }

        $files = $this->getFileInfo($glob);
        $return = array();

        foreach ($files as $file) {
            $path = $this->getNestedPath($file);

            if ($file['is_directory']) {
                $data = $directoryLoader->load($file['full_path']);
            } elseif (array_key_exists($file['extension'], $this->loaders)) {
                $data = $this->loaders[$file['extension']]->load($file['full_path']);
            } else {
                continue;
            }
        }
    }
}