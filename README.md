# elbucho/config

This project provides an object-oriented configuration system for all of your config files.  This includes 
support for the following file types:

* INI files
* PHP files (return array())
* JSON files
* XML files
* YAML files

## Usage

The Config class is instantiated with an array of key => value pairs.  Nested keys are instantiated as 
separate Config classes.  

### Retrieving values

Keys can be accessed either through the magic __get() method, or via the
get() method.  The get() method supports dotted notation (see examples below).

`$config = new Elbucho\Config\Config(array('foo' => array('bar' => 1)));`

`$config->foo->bar == 1`

`$config->get('foo.bar') == 1`

`$config->get('foo')->bar == 1`

`$config->get('foo')->get('bar') == 1`

When using the magic __get() method, if a key does not exist, the Config class will return `false`.
When using the get() method, you can specify the return value if the key does not exist.

`$config->foo->bar1 == false`

`$config->get('foo.bar1', 2) == 2`

`$config->foo->get('bar1', 'missing') == 'missing'`

### Setting values

You can set values by using either the magic __set() method, or by using the set() method:

`$config->foo->bar = 2`

`$config->set('foo.bar', 2)`

`$config->foo->set('bar', 2)`

If you use the set() method, and specify a path that doesn't currently exist, that path 
will be created:

`$config->get('foo.bar.foobar') == false`

`$config->set('foo.bar.foobar.asdf', 'fdsa')`

### Un-setting values

You can unset values either by using the magic __unset() method, or by using remove():

`unset($config->foo->bar)`

`$config->remove('foo.bar')`

`$config->foo->remove('bar')`

### Finding whether a key exists

You can determine whether a key has been set via either the magic __isset() method, 
or with exists():

`isset($config->foo->bar)`

`$config->exists('foo.bar')`

`$config->foo->exists('bar')`

### Returning as an array

You can return the Config object as an array by using the toArray() method:

`$config->toArray() == ['foo' => ['bar' => 1]]`

### Appending values

If you wish to merge two Config classes, you can use the append function:

```
$config1 = new Config(array('foo' => array('bar' => 1)));
$config2 = new Config(array('foo' => array('baz' => 2)));
$config1->append($config2);
```

`$config1->toArray() == ['foo' => ['bar' => 1, 'baz' => 2]]`

## File loaders

You can load a configuration file into a Config object via the use of one of the Loader classes:

| File Type | Loader Class                                |
| --------- | ------------------------------------------- |
| INI       | `Elbucho\Config\Loader\File\IniFileLoader`  |
| JSON      | `Elbucho\Config\Loader\File\JsonFileLoader` |
| PHP       | `Elbucho\Config\Loader\File\PhpFileLoader`  |
| XML       | `Elbucho\Config\Loader\File\XmlFileLoader`  |
| YAML/YML  | `Elbucho\Config\Loader\File\YamlFileLoader` |

To load a file and create a Config-compatible array from the values stored therein, do this:

```
$loader = new YamlFileLoader();
$config = new Config($loader->load('/path/to/config.yaml'));
```

## Directory Loader

You can also load all of the files in a given directory with the directory loader:

```
$loader = new Elbucho\Config\Loader\DirectoryLoader();
$config = new Config($loader->load('/path/to/config/directory'));
```

File names (minus the extensions) will be listed as keys in the $config object.  For example,
let's assume this file exists in your config directory as "database.yml": 

```
host:   localhost
port:   3306
dbname: test
user:   test_user
pass:   test_password
```

When you run the code above to import this file, here is how your config object will look:

```
$config->toArray() == [
    'database'  => [
        'host'      => 'localhost',
        'port'      => 3306,
        'dbname'    => 'test',
        'user'      => 'test_user',
        'pass'      => 'test_password'
    ]
]
```

### Environment Overwriting

In many cases, you will want to have a set of config files that are applicable for all 
environments, and will want to overwrite specific keys with environment-specific values.

For example, let's say that we have a config folder format like this:

```
/config
    /environment
        /live
            database.yml
        /test
            database.yml
    database.yml
    framework.yml
```

In this example, we want the keys that are common to all environments in `/config/database.yml`
and `/config/framework.yml` loaded, but we want to overwrite certain ones with the values
in `/config/environment/live` since we're in the live environment.  Here's how you would do that:

```
$environment = 'live';
$configPath = '/config';
$environmentPath = $configPath . '/environment/' . $environment;

$loader = new Elbucho\Config\Loader\DirectoryLoader();
$config = new Config($loader->load($configPath));
$config->remove('environment');

$config->append(
    new Config($loader->load($environmentPath))
);

```

### Extending the class

Each loader must conform to the `Elbucho\Config\LoaderInterface` interface.  If you would like
to write a custom file loader, it should throw an `Elbucho\Config\InvalidFileException` exception
when encountering issues opening or parsing the file.

New loaders can also be registered with the DirectoryLoader via the registerLoader() method:

```
$loader = new Elbucho\Config\Loader\DirectoryLoader();

// Registers the CustomFileLoader() class to handle files with '.xyz' extensions
$loader->registerLoader(
    new CustomFileLoader(),
    'xyz'
);
```