<?php

namespace Alchemy\Tests\BinaryDriver;

use Alchemy\BinaryDriver\AbstractBinary;
use Alchemy\BinaryDriver\BinaryDriverTestCase;
use Alchemy\BinaryDriver\Configuration;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class AbstractBinaryTest extends BinaryDriverTestCase
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

    public function testTimeoutIsSetOnConstruction()
    {
        $imp = Implementation::load('php', null, array('timeout' => 42));
        $this->assertEquals(42, $imp->getProcessBuilderFactory()->getTimeout());
    }

    public function testTimeoutIsSetOnConfigurationSetting()
    {
        $imp = Implementation::load('php', null);
        $imp->setConfiguration(new Configuration(array('timeout' => 42)));
        $this->assertEquals(42, $imp->getProcessBuilderFactory()->getTimeout());
    }

    public function testTimeoutIsSetOnProcessBuilderSetting()
    {
        $imp = Implementation::load('php', null, array('timeout' => 42));

        $factory = $this->getMock('Alchemy\BinaryDriver\ProcessBuilderFactoryInterface');
        $factory->expects($this->once())
            ->method('setTimeout')
            ->with(42);

        $imp->setProcessBuilderFactory($factory);
    }

    public function testListenRegistersAListener()
    {
        $imp = Implementation::load('php');

        $listeners = $this->getMockBuilder('Alchemy\BinaryDriver\Listeners\Listeners')
            ->disableOriginalConstructor()
            ->getMock();

        $listener = $this->getMock('Alchemy\BinaryDriver\Listeners\ListenerInterface');

        $listeners->expects($this->once())
            ->method('register')
            ->with($this->equalTo($listener), $this->equalTo($imp));

        $reflexion = new \ReflectionClass('Alchemy\BinaryDriver\AbstractBinary');
        $prop = $reflexion->getProperty('listenersManager');
        $prop->setAccessible(true);
        $prop->setValue($imp, $listeners);

        $imp->listen($listener);
    }

    public function testUnlistenUnregistersAListener()
    {
        $imp = Implementation::load('php');

        $listeners = $this->getMockBuilder('Alchemy\BinaryDriver\Listeners\Listeners')
            ->disableOriginalConstructor()
            ->getMock();

        $listener = $this->getMock('Alchemy\BinaryDriver\Listeners\ListenerInterface');

        $listeners->expects($this->once())
            ->method('unregister')
            ->with($this->equalTo($listener), $this->equalTo($imp));

        $reflexion = new \ReflectionClass('Alchemy\BinaryDriver\AbstractBinary');
        $prop = $reflexion->getProperty('listenersManager');
        $prop->setAccessible(true);
        $prop->setValue($imp, $listeners);

        $imp->unlisten($listener);
    }
}

class Implementation extends AbstractBinary
{
    public function getName()
    {
        return 'Implementation';
    }
}
