<?php

namespace ZendTest\InputFilter;

use PHPUnit_Framework_Assert as Assert;
use Zend\InputFilter\InputFilterInterface;

/**
 * Compliance test methods for `Zend\InputFilter\InputFilterInterface` implementations.
 */
trait InputFilterInterfaceTestTrait
{
    public function testImplementsInputFilterInterface()
    {
        Assert::assertInstanceOf(InputFilterInterface::class, $this->createDefaultInputFilter());
    }

    /**
     * @return InputFilterInterface
     */
    abstract protected function createDefaultInputFilter();
}
