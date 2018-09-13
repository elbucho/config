<?php

namespace Elbucho\Config\Loader;
use Elbucho\Config\InvalidFileException;

final class DirectoryLoader extends AbstractLoader
{
    /**
     * Validate that the constructor argument is correct
     *
     * @access  public
     * @param   mixed   $input
     * @return  bool
     */
    public function isValid($input)
    {
        if ( ! is_string($input)) {
            return false;
        }

        if ( ! file_exists($input)) {
            return false;
        }

        if ( ! is_dir($input)) {
            return false;
        }

        if ( ! is_readable($input)) {
            return false;
        }

        return true;
    }

    /**
     * Generate an array of keys / values from the constructor argument
     *
     * @access  public
     * @param   mixed   $input
     * @return  array
     * @throws  InvalidFileException
     */
    public function load($input)
    {
        if ( ! $this->isValid($input)) {
            throw new InvalidFileException(sprintf(
                'The directory provided is not readable or does not exist: %s',
                print_r($input, true)
            ));
        }

        return $this->loadDirectory($input);
    }

    /**
     * Recursively load from a directory
     *
     * @access  private
     * @param   string  $directory
     * @return  array
     * @throws  InvalidFileException
     */
    private function loadDirectory($directory)
    {
        $return = array();

        foreach ($this->getFiles($directory) as $file) {
            if ($file['is_directory']) {
                $return[$file['key_name']] = $this->loadDirectory($file['full_path']);

                continue;
            }

            if ( ! array_key_exists($file['extension'], $this->loaders)) {
                continue;
            }

            $return[$file['key_name']] = $this->loaders[$file['extension']]->load($file['full_path']);
        }

        return $return;
    }

    /**
     * Return a structured array of files within a directory
     *
     * @access  private
     * @param   string  $directory
     * @return  array
     */
    private function getFiles($directory)
    {
        $dh = opendir($directory);
        $files = array();

        while (($file = readdir($dh)) !== false) {
            if (preg_match("/^\.{1,2}$/", $file)) {
                continue;
            }

            $files[] = $directory . DIRECTORY_SEPARATOR . $file;
        }

        return $this->getFileInfo($files);
    }
}