<?php

namespace ZendTest\InputFilter;

use Exception;
use PHPUnit_Framework_Assert as Assert;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\InputFilter\Exception\RuntimeException;
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

    public function testHasUnknownThrowExceptionIfDataWasNotSetYet()
    {
        $filter = $this->createDefaultUnknownInputsCapable();
        $expectedExceptionType = RuntimeException::class;

        try {
            $filter->hasUnknown();
            TestCase::fail('Expected exception ' . $expectedExceptionType . ' was not thrown');
        } catch (Exception $e) {
            Assert::assertInstanceOf($expectedExceptionType, $e);
        }
    }

    public function testGetUnknownThrowExceptionIfDataWasNotSetYet()
    {
        $filter = $this->createDefaultUnknownInputsCapable();
        $expectedExceptionType = RuntimeException::class;

        try {
            $filter->getUnknown();
            TestCase::fail('Expected exception ' . $expectedExceptionType . ' was not thrown');
        } catch (Exception $e) {
            Assert::assertInstanceOf($expectedExceptionType, $e);
        }
    }

    /**
     * @return UnknownInputsCapableInterface
     */
    abstract protected function createDefaultUnknownInputsCapable();
}
