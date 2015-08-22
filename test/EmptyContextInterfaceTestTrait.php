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

    public function setContinueIfEmptyProvider()
    {
        return [
            // Description => [$continueIfEmpty]
            'Enable' => [true],
            'Disable' => [false],
        ];
    }

    /**
     * @dataProvider setContinueIfEmptyProvider
     */
    public function testSetContinueIfEmpty($continueIfEmpty)
    {
        $input = $this->createDefaultEmptyContext();

        $return = $input->setContinueIfEmpty($continueIfEmpty);
        Assert::assertSame($input, $return, 'setContinueIfEmpty() must return it self');

        Assert::assertEquals($continueIfEmpty, $input->continueIfEmpty(), 'continueIfEmpty() value not match');
    }

    /**
     * @return EmptyContextInterface
     */
    abstract protected function createDefaultEmptyContext();
}
