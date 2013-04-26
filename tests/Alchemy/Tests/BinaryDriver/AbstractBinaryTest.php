<?php

namespace Alchemy\Tests\BinaryDriver;

use Alchemy\BinaryDriver\AbstractBinary;
use Alchemy\BinaryDriver\BinaryDriverTestCase;
use Alchemy\BinaryDriver\Configuration;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Alchemy\BinaryDriver\Exception\ExecutionFailureException;
use Symfony\Component\Process\Exception\RuntimeException as ProcessRuntimeException;

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

    public function testRunSuccessFullProcess()
    {
        $factory = $this->createProcessBuilderFactoryMock();
        $logger = $this->createLoggerMock();
        $configuration = $this->createConfigurationMock();

        $process = $this->createProcessMock(1, true, '--helloworld--', "Kikoo Romain");

        $logger
            ->expects($this->never())
            ->method('error');
        $logger
            ->expects($this->exactly(2))
            ->method('info');

        $implementation = new Implementation($factory, $logger, $configuration);
        $this->assertEquals('Kikoo Romain', $implementation->doRun($process));
    }

    public function testRunSuccessFullProcessBypassingErrors()
    {
        $factory = $this->createProcessBuilderFactoryMock();
        $logger = $this->createLoggerMock();
        $configuration = $this->createConfigurationMock();

        $process = $this->createProcessMock(1, true, '--helloworld--', "Kikoo Romain");

        $logger
            ->expects($this->never())
            ->method('error');
        $logger
            ->expects($this->exactly(2))
            ->method('info');

        $implementation = new Implementation($factory, $logger, $configuration);
        $this->assertEquals('Kikoo Romain', $implementation->doRunAndBypassErrors($process));
    }

    public function testRunFailingProcess()
    {
        $factory = $this->createProcessBuilderFactoryMock();
        $logger = $this->createLoggerMock();
        $configuration = $this->createConfigurationMock();

        $process = $this->createProcessMock(1, false, '--helloworld--');

        $logger
            ->expects($this->once())
            ->method('error');
        $logger
            ->expects($this->once())
            ->method('info');

        $implementation = new Implementation($factory, $logger, $configuration);
        try {
            $implementation->doRun($process);
            $this->fail('An exception should have been raised');
        } catch (ExecutionFailureException $e) {

        }
    }

    public function testRunFailingProcessWithException()
    {
        $factory = $this->createProcessBuilderFactoryMock();
        $logger = $this->createLoggerMock();
        $configuration = $this->createConfigurationMock();

        $exception = new ProcessRuntimeException('Process Failed');
        $process = $this->getMockBuilder('Symfony\Component\Process\Process')
            ->disableOriginalConstructor()
            ->getMock();
        $process->expects($this->once())
            ->method('run')
            ->will($this->throwException($exception));

        $logger
            ->expects($this->once())
            ->method('error');
        $logger
            ->expects($this->once())
            ->method('info');

        $implementation = new Implementation($factory, $logger, $configuration);
        try {
            $implementation->doRun($process);
            $this->fail('An exception should have been raised');
        } catch (ExecutionFailureException $e) {
            $this->assertEquals($exception, $e->getPrevious());
        }
    }

    public function testRunfailingProcessBypassingErrors()
    {
        $factory = $this->createProcessBuilderFactoryMock();
        $logger = $this->createLoggerMock();
        $configuration = $this->createConfigurationMock();

        $process = $this->createProcessMock(1, false, '--helloworld--', 'Hello output');

        $logger
            ->expects($this->once())
            ->method('error');
        $logger
            ->expects($this->once())
            ->method('info');

        $implementation = new Implementation($factory, $logger, $configuration);
        $this->assertNull($implementation->doRunAndBypassErrors($process));
    }

    public function testRunFailingProcessWithExceptionBypassingErrors()
    {
        $factory = $this->createProcessBuilderFactoryMock();
        $logger = $this->createLoggerMock();
        $configuration = $this->createConfigurationMock();

        $exception = new ProcessRuntimeException('Process Failed');
        $process = $this->getMockBuilder('Symfony\Component\Process\Process')
            ->disableOriginalConstructor()
            ->getMock();
        $process->expects($this->once())
            ->method('run')
            ->will($this->throwException($exception));

        $logger
            ->expects($this->once())
            ->method('error');
        $logger
            ->expects($this->once())
            ->method('info');

        $implementation = new Implementation($factory, $logger, $configuration);
        $this->assertNull($implementation->doRunAndBypassErrors($process));
    }
}

class Implementation extends AbstractBinary
{
    public function getName()
    {
        return 'Implementation';
    }

    public function doRun(Process $process)
    {
        return $this->run($process, false);
    }

    public function doRunAndBypassErrors(Process $process)
    {
        return $this->run($process, true);
    }
}
