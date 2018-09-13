<?php

namespace Elbucho\Config\Loader;
use Elbucho\Config\LoaderInterface;
use Elbucho\Config\Loader\File\IniFileLoader;
use Elbucho\Config\Loader\File\PhpFileLoader;
use Elbucho\Config\Loader\File\JsonFileLoader;
use Elbucho\Config\Loader\File\XmlFileLoader;
use Elbucho\Config\Loader\File\YamlFileLoader;

abstract class AbstractLoader implements LoaderInterface
{
    /**
     * File loaders
     *
     * @access  protected
     * @var     LoaderInterface[]
     */
    protected $loaders = array();

    /**
     * Class constructor
     *
     * @access  public
     * @param   void
     * @return  AbstractLoader
     */
    public function __construct()
    {
        $this->loaders = $this->getDefaultLoaders();

        return $this;
    }

    /**
     * Register a new file loader
     *
     * @access  public
     * @param   LoaderInterface $loader
     * @param   string          $extension
     * @return  AbstractLoader
     */
    public function registerLoader(LoaderInterface $loader, $extension)
    {
        $this->loaders[$extension] = $loader;

        return $this;
    }

    /**
     * Get the default loaders
     *
     * @access  protected
     * @param   void
     * @return  array
     */
    protected function getDefaultLoaders()
    {
        $return = array(
            'ini'   => new IniFileLoader(),
            'php'   => new PhpFileLoader()
        );

        if (class_exists('SimpleXMLElement')) {
            $return['xml'] = new XmlFileLoader();
        }

        if (function_exists('json_decode')) {
            $return['json'] = new JsonFileLoader();
        }

        if (class_exists('Symfony\\Component\\Yaml\\Parser')) {
            $return['yml'] = new YamlFileLoader();
            $return['yaml'] = new YamlFileLoader();
        }

        return $return;
    }

    /**
     * Return a structured array of info for each of the provided files
     *
     * @access  protected
     * @param   array   $files
     * @return  array
     */
    protected function getFileInfo(array $files)
    {
        $return = array();

        foreach ($files as $path) {
            $info = pathinfo($path);

            $return[] = array(
                'is_directory'  => is_dir($path),
                'key_name'      => $info['filename'],
                'full_path'     => $path,
                'extension'     => (array_key_exists('extension', $info) ? $info['extension'] : '')
            );
        }

        return $return;
    }
}