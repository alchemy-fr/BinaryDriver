<?php

namespace Alchemy\Tests\BinaryDriver;

use Alchemy\BinaryDriver\AbstractBinary;
use Symfony\Component\Process\ExecutableFinder;

class AbstractBinaryTester extends \PHPUnit_Framework_TestCase
{
    protected function getPhpBinary()
    {
        $finder = new ExecutableFinder();
        $php = $finder->find('php');

        if (null === $php) {
            $this->markTestSkipped('Unable to find a php binary');
        }

        return $php;
    }

    public function testSimpleLoadWithBinaryPath()
    {
        $php = $this->getPhpBinary();
        $imp = Implementation::load($php);
        $this->assertInstanceOf('Alchemy\Tests\BinaryDriver\Implementation', $imp);

        $this->assertEquals($php, $imp->getProcessBuilderFactory()->getBinary());
    }

    public function testMultipleLoadWithBinaryPath()
    {
        $php = $this->getPhpBinary();
        $imp = Implementation::load(array('/zz/path/to/unexisting/command', $php));
        $this->assertInstanceOf('Alchemy\Tests\BinaryDriver\Implementation', $imp);

        $this->assertEquals($php, $imp->getProcessBuilderFactory()->getBinary());
    }

    public function testSimpleLoadWithBinaryName()
    {
        $php = $this->getPhpBinary();
        $imp = Implementation::load('php');
        $this->assertInstanceOf('Alchemy\Tests\BinaryDriver\Implementation', $imp);

        $this->assertEquals($php, $imp->getProcessBuilderFactory()->getBinary());
    }

    public function testMultipleLoadWithBinaryName()
    {
        $php = $this->getPhpBinary();
        $imp = Implementation::load(array('bachibouzouk', 'php'));
        $this->assertInstanceOf('Alchemy\Tests\BinaryDriver\Implementation', $imp);

        $this->assertEquals($php, $imp->getProcessBuilderFactory()->getBinary());
    }

    /**
     * @expectedException Alchemy\BinaryDriver\Exception\ExecutableNotFoundException
     */
    public function testLoadWithMultiplePathExpectingAFailure()
    {
        Implementation::load(array('bachibouzouk', 'moribon'));
    }

    /**
     * @expectedException Alchemy\BinaryDriver\Exception\ExecutableNotFoundException
     */
    public function testLoadWithUniquePathExpectingAFailure()
    {
        Implementation::load('bachibouzouk');
    }

    public function testLoadWithCustomLogger()
    {
        $logger = $this->getMock('Psr\Log\LoggerInterface');
        $imp = Implementation::load('php', $logger);

        $this->assertEquals($logger, $imp->getLogger());
    }

    public function testLoadWithCustomConfigurationAsArray()
    {
        $conf = array('timeout' => 200);
        $imp = Implementation::load('php', null, $conf);

        $this->assertEquals($conf, $imp->getConfiguration()->all());
    }

    public function testLoadWithCustomConfigurationAsObject()
    {
        $conf = $this->getMock('Alchemy\BinaryDriver\ConfigurationInterface');
        $imp = Implementation::load('php', null, $conf);

        $this->assertEquals($conf, $imp->getConfiguration());
    }

    public function testProcessBuilderFactoryGetterAndSetters()
    {
        $imp = Implementation::load('php');
        $factory = $this->getMock('Alchemy\BinaryDriver\ProcessBuilderFactoryInterface');

        $imp->setProcessBuilderFactory($factory);
        $this->assertEquals($factory, $imp->getProcessBuilderFactory());
    }

    public function testConfigurationGetterAndSetters()
    {
        $imp = Implementation::load('php');
        $conf = $this->getMock('Alchemy\BinaryDriver\ConfigurationInterface');

        $imp->setConfiguration($conf);
        $this->assertEquals($conf, $imp->getConfiguration());
    }

    public function testLoggerGetterAndSetters()
    {
        $imp = Implementation::load('php');
        $logger = $this->getMock('Psr\Log\LoggerInterface');

        $imp->setLogger($logger);
        $this->assertEquals($logger, $imp->getLogger());
    }
}

class Implementation extends AbstractBinary
{
}
