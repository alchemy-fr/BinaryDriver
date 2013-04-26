<?php

namespace Alchemy\BinaryDriver;

use Psr\Log\LoggerInterface;

/**
 * Convenient PHPUnit methods for testing BinaryDriverInterface implementations.
 */
class BinaryDriverTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @return ProcessBuilderFactoryInterface
     */
    public function createProcessBuilderFactoryMock()
    {
        return $this->getMock('Alchemy\BinaryDriver\ProcessBuilderFactoryInterface');
    }

    /**
     * @return LoggerInterface
     */
    public function createLoggerMock()
    {
        return $this->getMock('Psr\Log\LoggerInterface');
    }

    /**
     * @return ConfigurationInterface
     */
    public function createConfigurationMock()
    {
        return $this->getMock('Alchemy\BinaryDriver\ConfigurationInterface');
    }
}
