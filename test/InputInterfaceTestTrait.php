<?php

namespace ZendTest\InputFilter;

use PHPUnit_Framework_Assert as Assert;
use Zend\InputFilter\InputInterface;

/**
 * Compliance test methods for `Zend\InputFilter\InputInterface` implementations.
 */
trait InputInterfaceTestTrait
{
    public function testImplementsInputInterface()
    {
        Assert::assertInstanceOf(InputInterface::class, $this->createDefaultInput());
    }

    /**
     * @return InputInterface
     */
    abstract protected function createDefaultInput();
}
