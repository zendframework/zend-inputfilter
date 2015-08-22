<?php

namespace ZendTest\InputFilter;

use Maks3w\PhpUnitMethodsTrait\Framework\TestCaseTrait;
use PHPUnit_Framework_Assert as Assert;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\Filter\FilterChain;
use Zend\InputFilter\InputInterface;
use Zend\Validator\ValidatorChain;

/**
 * Compliance test methods for `Zend\InputFilter\InputInterface` implementations.
 */
trait InputInterfaceTestTrait
{
    use TestCaseTrait;

    public function testImplementsInputInterface()
    {
        Assert::assertInstanceOf(InputInterface::class, $this->createDefaultInput());
    }

    public function testMerge()
    {
        $source = $this->createInputInterfaceMock();
        $source->method('getName')->willReturn('bazInput');
        $source->method('getErrorMessage')->willReturn('bazErrorMessage');
        $source->method('breakOnFailure')->willReturn(true);
        $source->method('isRequired')->willReturn(true);
        $source->method('getRawValue')->willReturn('bazRawValue');
        $source->method('getFilterChain')->willReturn($this->createFilterChainMock());
        $source->method('getValidatorChain')->willReturn($this->createValidatorChainMock());

        $targetFilterChain = $this->createFilterChainMock();
        $targetFilterChain->expects(TestCase::once())
            ->method('merge')
            ->with($source->getFilterChain())
        ;

        $targetValidatorChain = $this->createValidatorChainMock();
        $targetValidatorChain->expects(TestCase::once())
            ->method('merge')
            ->with($source->getValidatorChain())
        ;

        $target = $this->createDefaultInput();
        $target->setName('fooInput');
        $target->setErrorMessage('fooErrorMessage');
        $target->setBreakOnFailure(false);
        $target->setRequired(false);
        $target->setFilterChain($targetFilterChain);
        $target->setValidatorChain($targetValidatorChain);

        $return = $target->merge($source);
        Assert::assertSame($target, $return, 'merge() must return it self');

        Assert::assertEquals('bazInput', $target->getName(), 'getName() value not match');
        Assert::assertEquals('bazErrorMessage', $target->getErrorMessage(), 'getErrorMessage() value not match');
        Assert::assertEquals(true, $target->breakOnFailure(), 'breakOnFailure() value not match');
        Assert::assertEquals(true, $target->isRequired(), 'isRequired() value not match');
        Assert::assertEquals('bazRawValue', $target->getRawValue(), 'getRawValue() value not match');
    }

    /**
     * @return InputInterface
     */
    abstract protected function createDefaultInput();

    /**
     * @return MockObject|InputInterface
     */
    protected function createInputInterfaceMock()
    {
        /** @var InputInterface|MockObject $source */
        $source = $this->getMock(InputInterface::class);

        return $source;
    }

    /**
     * @return MockObject|FilterChain
     */
    protected function createFilterChainMock()
    {
        /** @var FilterChain|MockObject $filterChain */
        $filterChain = $this->getMock(FilterChain::class);

        return $filterChain;
    }

    /**
     * @return MockObject|ValidatorChain
     */
    protected function createValidatorChainMock()
    {
        /** @var ValidatorChain|MockObject $validatorChain */
        $validatorChain = $this->getMock(ValidatorChain::class);

        return $validatorChain;
    }
}
