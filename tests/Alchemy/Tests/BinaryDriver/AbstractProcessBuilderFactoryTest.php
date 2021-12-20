<?php

namespace Alchemy\Tests\BinaryDriver;

use Alchemy\BinaryDriver\Exception\InvalidArgumentException;
use Alchemy\BinaryDriver\ProcessBuilderFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\ExecutableFinder;

abstract class AbstractProcessBuilderFactoryTest extends TestCase
{
    public static $phpBinary;

    private $original;
    /**
     * @return ProcessBuilderFactory
     */
    abstract protected function getProcessBuilderFactory($binary);

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $finder            = new ExecutableFinder();
        static::$phpBinary = $finder->find('php');
    }

    public function testThatBinaryIsSetOnConstruction()
    {
        $factory = $this->getProcessBuilderFactory(static::$phpBinary);
        $this->assertEquals(static::$phpBinary, $factory->getBinary());
    }

    public function testGetSetBinary()
    {
        $finder = new ExecutableFinder();
        $php    = $finder->find('php');

        $factory = $this->getProcessBuilderFactory(static::$phpBinary);
        $factory->useBinary($php);
        $this->assertEquals($php, $factory->getBinary());
    }

    public function testUseNonExistantBinary()
    {
        $this->expectException(InvalidArgumentException::class);
        $factory = $this->getProcessBuilderFactory(static::$phpBinary);
        $factory->useBinary('itissureitdoesnotexist');
    }

    public function testCreateShouldReturnAProcess()
    {
        $factory = $this->getProcessBuilderFactory(static::$phpBinary);
        $process = $factory->create();

        $this->assertInstanceOf('Symfony\Component\Process\Process', $process);
        $this->assertEquals("'" . static::$phpBinary . "'", $process->getCommandLine());
    }

    public function testCreateWithStringArgument()
    {
        $factory = $this->getProcessBuilderFactory(static::$phpBinary);
        $process = $factory->create('-v');

        $this->assertInstanceOf('Symfony\Component\Process\Process', $process);
        $this->assertEquals("'" . static::$phpBinary . "' '-v'", $process->getCommandLine());
    }

    public function testCreateWithArrayArgument()
    {
        $factory = $this->getProcessBuilderFactory(static::$phpBinary);
        $process = $factory->create(['-r', 'echo "Hello !";']);

        $this->assertInstanceOf('Symfony\Component\Process\Process', $process);
        $this->assertEquals("'" . static::$phpBinary . "' '-r' 'echo \"Hello !\";'", $process->getCommandLine());
    }

    public function testCreateWithTimeout()
    {
        $factory = $this->getProcessBuilderFactory(static::$phpBinary);
        $factory->setTimeout(200);
        $process = $factory->create(['-i']);

        $this->assertInstanceOf('Symfony\Component\Process\Process', $process);
        $this->assertEquals(200, $process->getTimeout());
    }
}
