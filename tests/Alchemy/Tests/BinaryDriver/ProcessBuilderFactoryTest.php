<?php declare(strict_types=1);

namespace Alchemy\Tests\BinaryDriver;

use Alchemy\BinaryDriver\ProcessBuilderFactory;

class ProcessBuilderFactoryTest extends AbstractProcessBuilderFactoryTest
{
    protected function getProcessBuilderFactory($binary) : ProcessBuilderFactory
    {
        return new ProcessBuilderFactory($binary);
    }
}
