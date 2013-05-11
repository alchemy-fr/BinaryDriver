# Binary Driver

Binary-Driver is a set of PHP tools to build binary drivers.

[![Build Status](https://travis-ci.org/alchemy-fr/BinaryDriver.png?branch=master)](https://travis-ci.org/alchemy-fr/BinaryDriver)

## AbstractBinary

`AbstractBinary` provides an abstract class to build a binary driver. It implements
`BinaryInterface`.

Implementation example :

```php
use Alchemy\BinaryDriver\AbstractBinary;

class LsDriver extends AbstractBinary
{
    public function getName()
    {
        return 'my driver';
    }

    public function simpleListing()
    {
        // will return the output of `ls`
        return $this->run($this->factory->create());
    }

    public function detailledListing()
    {
        // will return the output of `ls -a -l`
        return $this->run($this->factory->create('-a', '-l'));
    }
}

$parser = new LsParser();

$driver = Driver::load('ls');
$parser->parse($driver->simpleListing());
```

## Listeners

You can add custom listeners on processes.
Listeners are built on top of [Evenement](https://github.com/igorw/evenement)
and must implement `Alchemy\BinaryDriver\ListenerInterface`.

```php
use Symfony\Component\Process\Process;

class DebugListener extends EventEmitter implements ListenerInterface
{
    public function handle($type, $data)
    {
        foreach (explode(PHP_EOL, $data) as $line) {
            $this->emit($type === Process::ERR ? 'error' : 'out', array($line));
        }
    }

    public function forwardedEvents()
    {
        // forward 'error' events to the BinaryInterface
        return array('error');
    }
}

$listener = new DebugListener();

$driver = CustomImplementation::load('php');

// adds listener
$driver->listen($listener);

$driver->on('error', function ($line) {
    echo '[ERROR] ' . $line . PHP_EOL;
});

// removes listener
$driver->unlisten($listener);
```

### Bundled listeners

The debug listener is a simple listener to catch `stderr` and `stdout` outputs ;
read the implementation for customization.

```php
use Alchemy\BinaryDriver\Listeners\DebugListener;

$driver = CustomImplementation::load('php');
$driver->listen(new DebugListener());

$driver->on('debug', function ($line) {
    echo $line;
});
```

## ProcessBuilderFactory

ProcessBuilderFactory ease spawning processes by generating Symfony [Process]
(http://symfony.com/doc/master/components/process.html) objects.

```php
use Alchemy\BinaryDriver\ProcessBuilderFactory;

$factory = new ProcessBuilderFactory('/usr/bin/php');

// return a Symfony\Component\Process\Process
$process = $factory->create('-v');

// echoes '/usr/bin/php' '-v'
echo $process->getCommandLine();

$process = $factory->create(array('-r', 'echo "Hello !";'));

// echoes '/usr/bin/php' '-r' 'echo "Hello !";'
echo $process->getCommandLine();
```

## Configuration

A simple configuration object, providing an `ArrayAccess` and `IteratorAggregate`
interface.

```php
use Alchemy\BinaryDriver\Configuration;

$conf = new Configuration(array('timeout' => 0));

echo $conf->get('timeout');

if ($conf->has('param')) {
    $conf->remove('param');
}

$conf->set('timeout', 20);

$conf->all();
```

Same example using the `ArrayAccess` interface :

```php
use Alchemy\BinaryDriver\Configuration;

$conf = new Configuration(array('timeout' => 0));

echo $conf['timeout'];

if (isset($conf['param'])) {
    unset($conf['param']);
}

$conf['timeout'] = 20;
```

## License

This project is released under the MIT license.
