# Binary Driver

Binary-Driver is a set of PHP tools to build binary drivers.

[![Build Status](https://travis-ci.org/alchemy-fr/BinaryDriver.png?branch=master)](https://travis-ci.org/alchemy-fr/BinaryDriver)

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

## License

This project is released under the MIT license.
