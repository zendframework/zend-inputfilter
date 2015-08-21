<?php

namespace ZendTest\InputFilter;

use PHPUnit_Framework_Assert as Assert;
use Zend\InputFilter\EmptyContextInterface;

/**
 * Compliance test methods for `Zend\InputFilter\EmptyContextInterface` implementations.
 */
trait EmptyContextInterfaceTestTrait
{
    public function testImplementsEmptyContextInterface()
    {
        Assert::assertInstanceOf(EmptyContextInterface::class, $this->createDefaultEmptyContext());
    }

    /**
     * @return EmptyContextInterface
     */
    abstract protected function createDefaultEmptyContext();
}
