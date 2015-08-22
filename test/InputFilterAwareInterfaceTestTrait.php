<?php

namespace ZendTest\InputFilter;

use Maks3w\PhpUnitMethodsTrait\Framework\TestCaseTrait;
use PHPUnit_Framework_Assert as Assert;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

/**
 * Compliance test methods for `Zend\InputFilter\InputFilterAwareInterface` implementations.
 */
trait InputFilterAwareInterfaceTestTrait
{
    use TestCaseTrait;

    public function testImplementsInputFilterAwareInterface()
    {
        Assert::assertInstanceOf(InputFilterAwareInterface::class, $this->createDefaultInputFilterAware());
    }

    public function testSetInputFilter()
    {
        $object = $this->createDefaultInputFilterAware();

        /** @var InputFilterInterface|MockObject $inputFilter */
        $inputFilter = $this->getMock(InputFilterInterface::class);

        $object->setInputFilter($inputFilter);

        Assert::assertAttributeSame($inputFilter, 'inputFilter', $object, '$inputFilter value not match');
        Assert::assertSame($inputFilter, $object->getInputFilter(), 'getInputFilter value not match');
    }

    /**
     * @return InputFilterAwareInterface
     */
    abstract protected function createDefaultInputFilterAware();
}
