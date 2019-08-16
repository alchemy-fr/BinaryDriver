<?php declare(strict_types=1);

namespace Alchemy\Tests\BinaryDriver;

use Symfony\Component\Process\ExecutableFinder;
use Alchemy\BinaryDriver\ProcessBuilderFactory;
use PHPUnit\Framework\TestCase;

abstract class AbstractProcessBuilderFactoryTest extends TestCase
{
    /**
     * @var string
     */
    public static $phpBinary;

    /**
     * @return ProcessBuilderFactory
     */
    abstract protected function getProcessBuilderFactory($binary) : ProcessBuilderFactory;

    public function setUp() : void
    {
        if (null === static::$phpBinary) {
            $this->markTestSkipped('Unable to detect php binary, skipping.');
            return;
        }

        parent::setUp();
    }

    public static function setUpBeforeClass()
    {
        $finder = new ExecutableFinder();
        static::$phpBinary = $finder->find('php');
    }

    public function testThatBinaryIsSetOnConstruction() : void
    {
        $factory = $this->getProcessBuilderFactory(static::$phpBinary);
        $this->assertEquals(static::$phpBinary, $factory->getBinary());
    }

    public function testGetSetBinary() : void
    {
        $finder = new ExecutableFinder();
        $phpUnit = $finder->find('php');

        if (null === $phpUnit) {
            $this->markTestSkipped('Unable to detect phpunit binary, skipping');
        }

        $factory = $this->getProcessBuilderFactory(static::$phpBinary);
        $factory->useBinary($phpUnit);
        $this->assertEquals($phpUnit, $factory->getBinary());
    }

    public function testUseNonExistantBinary() : void
    {
        $this->expectException(\Alchemy\BinaryDriver\Exception\InvalidArgumentException::class);
        $factory = $this->getProcessBuilderFactory(static::$phpBinary);
        $factory->useBinary('itissureitdoesnotexist');
    }

    /**
     * @requires OS Linux
     */
    public function testCreateShouldReturnAProcess() : void
    {
        $factory = $this->getProcessBuilderFactory(static::$phpBinary);
        $process = $factory->create();

        $this->assertInstanceOf(\Symfony\Component\Process\Process::class, $process);
        $this->assertEquals("'" . static::$phpBinary . "'", $process->getCommandLine());
    }

    /**
     * @requires OS Linux
     */
    public function testCreateWithStringArgument() : void
    {
        $factory = $this->getProcessBuilderFactory(static::$phpBinary);
        $process = $factory->create('-v');

        $this->assertInstanceOf(\Symfony\Component\Process\Process::class, $process);
        $this->assertEquals("'" . static::$phpBinary . "' '-v'", $process->getCommandLine());
    }

    /**
     * @requires OS Linux
     */
    public function testCreateWithArrayArgument() : void
    {
        $factory = $this->getProcessBuilderFactory(static::$phpBinary);
        $process = $factory->create(['-r', 'echo "Hello !";']);

        $this->assertInstanceOf(\Symfony\Component\Process\Process::class, $process);
        $this->assertEquals("'" . static::$phpBinary . "' '-r' 'echo \"Hello !\";'", $process->getCommandLine());
    }

    public function testCreateWithTimeout() : void
    {
        $factory = $this->getProcessBuilderFactory(static::$phpBinary);
        $factory->setTimeout(200.0);
        $process = $factory->create(['-i']);

        $this->assertInstanceOf(\Symfony\Component\Process\Process::class, $process);
        $this->assertEquals(200, $process->getTimeout());
    }
}
