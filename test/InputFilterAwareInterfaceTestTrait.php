<?php

namespace ZendTest\InputFilter;

use PHPUnit_Framework_Assert as Assert;
use Zend\InputFilter\InputFilterAwareInterface;

/**
 * Compliance test methods for `Zend\InputFilter\InputFilterAwareInterface` implementations.
 */
trait InputFilterAwareInterfaceTestTrait
{
    public function testImplementsInputFilterAwareInterface()
    {
        Assert::assertInstanceOf(InputFilterAwareInterface::class, $this->createDefaultInputFilterAware());
    }

    /**
     * @return InputFilterAwareInterface
     */
    abstract protected function createDefaultInputFilterAware();
}
