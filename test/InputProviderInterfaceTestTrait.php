<?php

namespace ZendTest\InputFilter;

use PHPUnit_Framework_Assert as Assert;
use Zend\InputFilter\InputProviderInterface;

/**
 * Compliance test methods for `Zend\InputFilter\InputProviderInterface` implementations.
 */
trait InputProviderInterfaceTestTrait
{
    public function testImplementsInputProviderInterface()
    {
        Assert::assertInstanceOf(InputProviderInterface::class, $this->createDefaultInputProvider());
    }

    /**
     * @return InputProviderInterface
     */
    abstract protected function createDefaultInputProvider();
}
