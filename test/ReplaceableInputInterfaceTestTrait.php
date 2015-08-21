<?php

namespace ZendTest\InputFilter;

use PHPUnit_Framework_Assert as Assert;
use Zend\InputFilter\ReplaceableInputInterface;

/**
 * Compliance test methods for `Zend\InputFilter\ReplaceableInputInterface` implementations.
 */
trait ReplaceableInputInterfaceTestTrait
{
    public function testImplementsReplaceableInputInterface()
    {
        Assert::assertInstanceOf(ReplaceableInputInterface::class, $this->createDefaultReplaceableInput());
    }

    /**
     * @return ReplaceableInputInterface
     */
    abstract protected function createDefaultReplaceableInput();
}
