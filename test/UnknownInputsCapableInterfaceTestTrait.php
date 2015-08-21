<?php

namespace ZendTest\InputFilter;

use PHPUnit_Framework_Assert as Assert;
use Zend\InputFilter\UnknownInputsCapableInterface;

/**
 * Compliance test methods for `Zend\InputFilter\UnknownInputsCapableInterface` implementations.
 */
trait UnknownInputsCapableInterfaceTestTrait
{
    public function testImplementsUnknownInputsCapableInterface()
    {
        Assert::assertInstanceOf(UnknownInputsCapableInterface::class, $this->createDefaultUnknownInputsCapable());
    }

    /**
     * @return UnknownInputsCapableInterface
     */
    abstract protected function createDefaultUnknownInputsCapable();
}
