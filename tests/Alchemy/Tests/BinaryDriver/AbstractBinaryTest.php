<?php declare(strict_types=1);

namespace Alchemy\Tests\BinaryDriver;

use Alchemy\BinaryDriver\AbstractBinary;
use Alchemy\BinaryDriver\BinaryDriverTestCase;
use Alchemy\BinaryDriver\Configuration;
use Alchemy\BinaryDriver\Listeners\ListenerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Process\ExecutableFinder;

class AbstractBinaryTest extends BinaryDriverTestCase
{
    protected function getPhpBinary() : string 
    {
        $finder = new ExecutableFinder();
        $php = $finder->find('php');

        if (null === $php) {
            $this->markTestSkipped('Unable to find a php binary');
        }

        return $php;
    }

    public function testSimpleLoadWithBinaryPath() : void
    {
        $php = $this->getPhpBinary();
        $imp = Implementation::load($php);
        $this->assertInstanceOf(\Alchemy\Tests\BinaryDriver\Implementation::class, $imp);

        $this->assertEquals($php, $imp->getProcessBuilderFactory()->getBinary());
    }

    public function testMultipleLoadWithBinaryPath() : void
    {
        $php = $this->getPhpBinary();
        $imp = Implementation::load(['/zz/path/to/unexisting/command', $php]);
        $this->assertInstanceOf(\Alchemy\Tests\BinaryDriver\Implementation::class, $imp);

        $this->assertEquals($php, $imp->getProcessBuilderFactory()->getBinary());
    }

    public function testSimpleLoadWithBinaryName() : void
    {
        $php = $this->getPhpBinary();
        $imp = Implementation::load('php');
        $this->assertInstanceOf(\Alchemy\Tests\BinaryDriver\Implementation::class, $imp);

        $this->assertEquals($php, $imp->getProcessBuilderFactory()->getBinary());
    }

    public function testMultipleLoadWithBinaryName() : void
    {
        $php = $this->getPhpBinary();
        $imp = Implementation::load(['bachibouzouk', 'php']);
        $this->assertInstanceOf(\Alchemy\Tests\BinaryDriver\Implementation::class, $imp);

        $this->assertEquals($php, $imp->getProcessBuilderFactory()->getBinary());
    }

    /**
     * @return void
     */
    public function testLoadWithMultiplePathExpectingAFailure() : void
    {
        $this->expectException(\Alchemy\BinaryDriver\Exception\ExecutableNotFoundException::class);
        Implementation::load(['bachibouzouk', 'moribon']);
    }

    /**
     * @return void
     */
    public function testLoadWithUniquePathExpectingAFailure() : void
    {
        $this->expectException(\Alchemy\BinaryDriver\Exception\ExecutableNotFoundException::class);
        Implementation::load('bachibouzouk');
    }

    public function testLoadWithCustomLogger() : void
    {
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $imp = Implementation::load('php', $logger);

        $this->assertEquals($logger, $imp->getProcessRunner()->getLogger());
    }

    public function testLoadWithCustomConfigurationAsArray() : void
    {
        $conf = ['timeout' => 200];
        $imp = Implementation::load('php', null, $conf);

        $this->assertEquals($conf, $imp->getConfiguration()->all());
    }

    public function testLoadWithCustomConfigurationAsObject() : void
    {
        $conf = $this->createMock(\Alchemy\BinaryDriver\ConfigurationInterface::class);
        $imp = Implementation::load('php', null, $conf);

        $this->assertEquals($conf, $imp->getConfiguration());
    }

    public function testProcessBuilderFactoryGetterAndSetters() : void
    {
        $imp = Implementation::load('php');
        $factory = $this->createMock(\Alchemy\BinaryDriver\ProcessBuilderFactoryInterface::class);

        $imp->setProcessBuilderFactory($factory);
        $this->assertEquals($factory, $imp->getProcessBuilderFactory());
    }

    public function testConfigurationGetterAndSetters() : void
    {
        $imp = Implementation::load('php');
        $conf = $this->createMock(\Alchemy\BinaryDriver\ConfigurationInterface::class);

        $imp->setConfiguration($conf);
        $this->assertEquals($conf, $imp->getConfiguration());
    }

    public function testTimeoutIsSetOnConstruction() : void
    {
        $imp = Implementation::load('php', null, ['timeout' => 42]);
        $this->assertEquals(42, $imp->getProcessBuilderFactory()->getTimeout());
    }

    public function testTimeoutIsSetOnConfigurationSetting() : void
    {
        $imp = Implementation::load('php', null);
        $imp->setConfiguration(new Configuration(['timeout' => 42]));
        $this->assertEquals(42, $imp->getProcessBuilderFactory()->getTimeout());
    }

    public function testTimeoutIsSetOnProcessBuilderSetting() : void
    {
        $imp = Implementation::load('php', null, ['timeout' => 42]);

        $factory = $this->createMock(\Alchemy\BinaryDriver\ProcessBuilderFactoryInterface::class);
        $factory->expects($this->once())
            ->method('setTimeout')
            ->with(42);

        $imp->setProcessBuilderFactory($factory);
    }

    public function testListenRegistersAListener() : void
    {
        $imp = Implementation::load('php');

        $listeners = $this->createMock(\Alchemy\BinaryDriver\Listeners\Listeners::class);

        $listener = $this->createMock(ListenerInterface::class);

        $listeners->expects($this->once())
            ->method('register')
            ->with($this->equalTo($listener), $this->equalTo($imp));

        $reflexion = new \ReflectionClass(AbstractBinary::class);
        $prop = $reflexion->getProperty('listenersManager');
        $prop->setAccessible(true);
        $prop->setValue($imp, $listeners);

        $imp->listen($listener);
    }

    /**
     * @param array|string $parameters
     * @param bool $bypassErrors
     * @param array $expectedParameters
     * @param string $output
     *
     * @dataProvider provideCommandParameters
     */
    public function testCommandRunsAProcess($parameters, bool $bypassErrors, array $expectedParameters, string $output) : void
    {
        $imp = Implementation::load('php');
        $factory = $this->createMock(\Alchemy\BinaryDriver\ProcessBuilderFactoryInterface::class);
        $processRunner = $this->createMock(\Alchemy\BinaryDriver\ProcessRunnerInterface::class);

        $process = $this->createMock(\Symfony\Component\Process\Process::class);

        $processRunner->expects($this->once())
            ->method('run')
            ->with($this->equalTo($process), $this->isInstanceOf(\SplObjectStorage::class), $this->equalTo($bypassErrors))
            ->willReturn($output);

        $factory->expects($this->once())
            ->method('create')
            ->with($expectedParameters)
            ->willReturn($process);

        $imp->setProcessBuilderFactory($factory);
        $imp->setProcessRunner($processRunner);

        $this->assertEquals($output, $imp->command($parameters, $bypassErrors));
    }

    /**
     * @param string $parameters
     * @param bool $bypassErrors
     * @param array $expectedParameters
     * @param string $output
     * @param int $count
     * @param string|ListenerInterface $listeners
     *
     * @dataProvider provideCommandWithListenersParameters
     */
    public function testCommandWithTemporaryListeners(string $parameters, bool $bypassErrors, array $expectedParameters, string $output, int $count, $listeners) : void
    {
        $imp = Implementation::load('php');
        $factory = $this->createMock(\Alchemy\BinaryDriver\ProcessBuilderFactoryInterface::class);
        $processRunner = $this->createMock(\Alchemy\BinaryDriver\ProcessRunnerInterface::class);

        $process = $this->createMock(\Symfony\Component\Process\Process::class);

        $firstStorage = $secondStorage = null;

        $processRunner->expects($this->exactly(2))
            ->method('run')
            ->with($this->equalTo($process), $this->isInstanceOf(\SplObjectStorage::class), $this->equalTo($bypassErrors))
            ->willReturnCallback(
                function ($process, $storage, $errors) use ($output, &$firstStorage, &$secondStorage) {
                    if (null === $firstStorage) {
                        $firstStorage = $storage;
                    } else {
                        $secondStorage = $storage;
                    }

                    return $output;
                }
            );

        $factory->expects($this->exactly(2))
            ->method('create')
            ->with($expectedParameters)
            ->willReturn($process);

        $imp->setProcessBuilderFactory($factory);
        $imp->setProcessRunner($processRunner);

        $this->assertEquals($output, $imp->command($parameters, $bypassErrors, $listeners));
        $this->assertCount($count, $firstStorage);
        $this->assertEquals($output, $imp->command($parameters, $bypassErrors));
        $this->assertCount(0, $secondStorage);
    }

    public function provideCommandWithListenersParameters() : array
    {
        return [
            ['-a', false, ['-a'], 'loubda', 2, [$this->getMockListener(), $this->getMockListener()]],
            ['-a', false, ['-a'], 'loubda', 1, [$this->getMockListener()]],
            ['-a', false, ['-a'], 'loubda', 1, $this->getMockListener()],
            ['-a', false, ['-a'], 'loubda', 0, []],
        ];
    }

    public function provideCommandParameters() : array
    {
        return [
            ['-a', false, ['-a'], 'loubda'],
            ['-a', true, ['-a'], 'loubda'],
            ['-a -b', false, ['-a -b'], 'loubda'],
            [['-a'], false, ['-a'], 'loubda'],
            [['-a'], true, ['-a'], 'loubda'],
            [['-a', '-b'], false, ['-a', '-b'], 'loubda'],
        ];
    }

    public function testUnlistenUnregistersAListener() : void
    {
        $imp = Implementation::load('php');

        $listeners = $this->createMock(\Alchemy\BinaryDriver\Listeners\Listeners::class);

        $listener = $this->createMock(\Alchemy\BinaryDriver\Listeners\ListenerInterface::class);

        $listeners->expects($this->once())
            ->method('unregister')
            ->with($this->equalTo($listener));

        $reflexion = new \ReflectionClass(\Alchemy\BinaryDriver\AbstractBinary::class);
        $prop = $reflexion->getProperty('listenersManager');
        $prop->setAccessible(true);
        $prop->setValue($imp, $listeners);

        $imp->unlisten($listener);
    }

    /**
     * @return MockObject
     */
    private function getMockListener() : MockObject
    {
        $listener = $this->createMock(ListenerInterface::class);
        $listener->expects($this->any())
            ->method('forwardedEvents')
            ->willReturn([]);

        return $listener;
    }
}

class Implementation extends AbstractBinary
{
    public function getName() : string
    {
        return 'Implementation';
    }
}
