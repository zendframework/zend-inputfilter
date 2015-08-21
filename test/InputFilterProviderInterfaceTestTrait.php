<?php

namespace ZendTest\InputFilter;

use PHPUnit_Framework_Assert as Assert;
use Zend\InputFilter\InputFilterProviderInterface;

/**
 * Compliance test methods for `Zend\InputFilter\InputFilterProviderInterface` implementations.
 */
trait InputFilterProviderInterfaceTestTrait
{
    public function testImplementsInputFilterProviderInterface()
    {
        Assert::assertInstanceOf(InputFilterProviderInterface::class, $this->createDefaultInputFilterProvider());
    }

    /**
     * @return InputFilterProviderInterface
     */
    abstract protected function createDefaultInputFilterProvider();
}
