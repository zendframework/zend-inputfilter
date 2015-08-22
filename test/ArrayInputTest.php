<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\InputFilter;

use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Zend\Filter;
use Zend\InputFilter\ArrayInput;
use Zend\InputFilter\Exception\InvalidArgumentException;
use Zend\InputFilter\Input;
use Zend\Validator;

/**
 * @covers Zend\InputFilter\ArrayInput
 */
class ArrayInputTest extends InputTest
{
    public function testIsASubclassOfInput()
    {
        $this->assertInstanceOf(Input::class, $this->createDefaultInput());
    }

    public function testMerge()
    {
        $this->markTestSkipped('ArrayInput::merge is not compatible with InputInterface::merge');
    }

    public function testInputMerge()
    {
        $this->markTestSkipped('ArrayInput::merge is not compatible with Input::merge');
    }

    /**
     * Specific ArrayInput::merge behavior
     */
    public function testArrayInputMergeWithInput()
    {
        $source = new Input('bazInput');
        $source->setName('bazInput');
        $source->setErrorMessage('bazErrorMessage');
        $source->setBreakOnFailure(true);
        $source->setRequired(true);
        $source->setValue(['bazRawValue']);
        $source->setFilterChain($this->createFilterChainMock());
        $source->setValidatorChain($this->createValidatorChainMock());
        $source->setContinueIfEmpty(true);

        $targetFilterChain = $this->createFilterChainMock();
        $targetFilterChain->expects($this->once())
            ->method('merge')
            ->with($source->getFilterChain())
        ;

        $targetValidatorChain = $this->createValidatorChainMock();
        $targetValidatorChain->expects($this->once())
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
        $target->setContinueIfEmpty(false);

        $return = $target->merge($source);
        $this->assertSame($target, $return, 'merge() must return it self');

        $this->assertEquals('bazInput', $target->getName(), 'getName() value not match');
        $this->assertEquals('bazErrorMessage', $target->getErrorMessage(), 'getErrorMessage() value not match');
        $this->assertEquals(true, $target->breakOnFailure(), 'breakOnFailure() value not match');
        $this->assertEquals(true, $target->isRequired(), 'isRequired() value not match');
        $this->assertEquals(['bazRawValue'], $target->getRawValue(), 'getRawValue() value not match');
        $this->assertEquals(true, $target->continueIfEmpty(), 'continueIfEmpty() value not match');
    }

    public function testNotEmptyValidatorNotInjectedIfContinueIfEmptyIsTrue()
    {
        $this->markTestIncomplete('Parent test does did not verify ArrayInput object. Pending review');
    }

    public function testValueIsNullByDefault()
    {
        $this->markTestSkipped('Test is not enabled in ArrayInputTest');
    }

    public function testValueIsEmptyArrayByDefault()
    {
        $input = $this->createDefaultInput();

        $this->assertCount(0, $input->getValue());
    }

    public function testSetValueWithInvalidInputTypeThrowsInvalidArgumentException()
    {
        $input = $this->createDefaultInput();

        $this->setExpectedException(
            InvalidArgumentException::class,
            'Value must be an array, string given'
        );
        $input->setValue('bar');
    }

    public function testSetValue($value = null)
    {
        $this->markTestSkipped('ArrayInput::setValue is not compatible with InputInterface::setValue');
    }

    /**
     * Specific ArrayInput::setValue behavior
     *
     * @dataProvider setValueProvider
     */
    public function testArrayInputSetValue($value)
    {
        $arrayValue = [$value];

        $filterChain = $this->createFilterChainMock();
        $filterChain->expects($this::atLeastOnce())
            ->method('filter')
            ->with($value)
            ->willReturn('*filter*')
        ;

        $input = $this->createDefaultInput();
        $input->setFilterChain($filterChain);

        $return = $input->setValue($arrayValue);
        $this->assertSame($input, $return, 'setValue() must return it self');

        $this->assertEquals($arrayValue, $input->getRawValue(), 'getRawValue() value not match');
        $this->assertEquals(['*filter*'], $input->getValue(), 'getValue() value not match');
    }

    public function testIsValidReturnsFalseIfValidationChainFails()
    {
        $input = $this->createDefaultInput();

        $input->setValue(['123', 'bar']);
        $validator = new Validator\Digits();
        $input->getValidatorChain()->attach($validator);
        $this->assertFalse($input->isValid());
    }

    public function testIsValidReturnsTrueIfValidationChainSucceeds()
    {
        $input = $this->createDefaultInput();

        $input->setValue(['123', '123']);
        $validator = new Validator\Digits();
        $input->getValidatorChain()->attach($validator);
        $this->assertTrue($input->isValid());
    }

    public function testValidationOperatesOnFilteredValue()
    {
        $input = $this->createDefaultInput();

        $input->setValue([' 123 ', '  123']);
        $filter = new Filter\StringTrim();
        $input->getFilterChain()->attach($filter);
        $validator = new Validator\Digits();
        $input->getValidatorChain()->attach($validator);
        $this->assertTrue($input->isValid());
    }

    public function testGetMessagesReturnsValidationMessages()
    {
        $input = $this->createDefaultInput();

        $input->setValue(['bar']);
        $validator = new Validator\Digits();
        $input->getValidatorChain()->attach($validator);
        $this->assertFalse($input->isValid());
        $messages = $input->getMessages();
        $this->assertArrayHasKey(Validator\Digits::NOT_DIGITS, $messages);
    }

    public function testSpecifyingMessagesToInputReturnsThoseOnFailedValidation()
    {
        $input = $this->createDefaultInput();

        $input->setValue(['bar']);
        $validator = new Validator\Digits();
        $input->getValidatorChain()->attach($validator);
        $input->setErrorMessage('Please enter only digits');
        $this->assertFalse($input->isValid());
        $messages = $input->getMessages();
        $this->assertArrayNotHasKey(Validator\Digits::NOT_DIGITS, $messages);
        $this->assertContains('Please enter only digits', $messages);
    }

    public function testNotEmptyValidatorAddedWhenIsValidIsCalled()
    {
        $input = $this->createDefaultInput();

        $this->assertTrue($input->isRequired());
        $input->setValue(['bar', '']);
        $validatorChain = $input->getValidatorChain();
        $this->assertEquals(0, count($validatorChain->getValidators()));

        $this->assertFalse($input->isValid());
        $messages = $input->getMessages();
        $this->assertArrayHasKey('isEmpty', $messages);
        $this->assertEquals(1, count($validatorChain->getValidators()));

        // Assert that NotEmpty validator wasn't added again
        $this->assertFalse($input->isValid());
        $this->assertEquals(1, count($validatorChain->getValidators()));
    }

    public function testRequiredNotEmptyValidatorNotAddedWhenOneExists()
    {
        $input = $this->createDefaultInput();

        $this->assertTrue($input->isRequired());
        $input->setValue(['bar', '']);

        /** @var Validator\NotEmpty|MockObject $notEmptyMock */
        $notEmptyMock = $this->getMock(Validator\NotEmpty::class, ['isValid']);
        $notEmptyMock->expects($this->exactly(1))
            ->method('isValid')
            ->will($this->returnValue(false));

        $validatorChain = $input->getValidatorChain();
        $validatorChain->prependValidator($notEmptyMock);
        $this->assertFalse($input->isValid());

        $validators = $validatorChain->getValidators();
        $this->assertEquals(1, count($validators));
        $this->assertEquals($notEmptyMock, $validators[0]['instance']);
    }

    public function testDoNotInjectNotEmptyValidatorIfAnywhereInChain()
    {
        $input = $this->createDefaultInput();

        $this->assertTrue($input->isRequired());
        $input->setValue(['bar', '']);

        /** @var Validator\NotEmpty|MockObject $notEmptyMock */
        $notEmptyMock = $this->getMock(Validator\NotEmpty::class, ['isValid']);
        $notEmptyMock->expects($this->exactly(1))
            ->method('isValid')
            ->will($this->returnValue(false));

        $validatorChain = $input->getValidatorChain();
        $validatorChain->attach(new Validator\Digits());
        $validatorChain->attach($notEmptyMock);
        $this->assertFalse($input->isValid());

        $validators = $validatorChain->getValidators();
        $this->assertEquals(2, count($validators));
        $this->assertEquals($notEmptyMock, $validators[1]['instance']);
    }

    public function dataFallbackValue()
    {
        return [
            [
                'fallbackValue' => []
            ],
            [
                'fallbackValue' => [''],
            ],
            [
                'fallbackValue' => [null],
            ],
            [
                'fallbackValue' => ['some value'],
            ],
        ];
    }

    /**
     * @dataProvider dataFallbackValue
     */
    public function testFallbackValue($fallbackValue)
    {
        $input = $this->createDefaultInput();

        $input->setFallbackValue($fallbackValue);
        $validator = new Validator\Date();
        $input->getValidatorChain()->attach($validator);
        $input->setValue(['123']); // not a date

        $this->assertTrue($input->isValid());
        $this->assertEmpty($input->getMessages());
        $this->assertSame($fallbackValue, $input->getValue());
    }

    public function testWhenRequiredAndAllowEmptyAndNotContinueIfEmptyValidatorsAreNotRun(Input $input = null, $value = null)
    {
        $this->markTestIncomplete('Parent test does did not verify ArrayInput object. Pending review');
    }

    public function testWhenRequiredAndAllowEmptyAndContinueIfEmptyValidatorsAreRun(Input $input = null, $value = null, $assertion = null)
    {
        $this->markTestIncomplete('Parent test does did not verify ArrayInput object. Pending review');
    }

    public function testWhenRequiredAndNotAllowEmptyAndNotContinueIfEmptyValidatorsAreNotRun(Input $input = null, $value = null)
    {
        $this->markTestIncomplete('Parent test does did not verify ArrayInput object. Pending review');
    }

    public function testWhenRequiredAndNotAllowEmptyAndContinueIfEmptyValidatorsAreRun(Input $input = null, $value = null, $assertion = null)
    {
        $this->markTestIncomplete('Parent test does did not verify ArrayInput object. Pending review');
    }

    public function testWhenNotRequiredAndAllowEmptyAndNotContinueIfEmptyValidatorsAreNotRun(Input $input = null, $value = null)
    {
        $this->markTestIncomplete('Parent test does did not verify ArrayInput object. Pending review');
    }

    public function testWhenNotRequiredAndNotAllowEmptyAndNotContinueIfEmptyValidatorsAreNotRun(Input $input = null, $value = null)
    {
        $this->markTestIncomplete('Parent test does did not verify ArrayInput object. Pending review');
    }

    public function testWhenNotRequiredAndAllowEmptyAndContinueIfEmptyValidatorsAreRun(Input $input = null, $value = null, $assertion = null)
    {
        $this->markTestIncomplete('Parent test does did not verify ArrayInput object. Pending review');
    }

    public function testWhenNotRequiredAndNotAllowEmptyAndContinueIfEmptyValidatorsAreRun(
        Input $input = null,
        $value = null,
        $assertion = null
    ) {
        $this->markTestIncomplete('Parent test does did not verify ArrayInput object. Pending review');
    }

    public function emptyValuesProvider()
    {
        return [
            [[null]],
            [['']],
            [[[]]],
        ];
    }

    public function testNotAllowEmptyWithFilterConvertsNonemptyToEmptyIsNotValid()
    {
        $input = $this->createDefaultInput();

        $input->setValue(['nonempty'])
                    ->getFilterChain()->attach(new Filter\Callback(function () {
                        return '';
                    }));
        $this->assertFalse($input->isValid());
    }

    public function testNotAllowEmptyWithFilterConvertsEmptyToNonEmptyIsValid()
    {
        $input = $this->createDefaultInput();

        $input->setValue([''])
                    ->getFilterChain()->attach(new Filter\Callback(function () {
                        return 'nonempty';
                    }));
        $this->assertTrue($input->isValid());
    }

    protected function createDefaultInput()
    {
        $input = new ArrayInput('foo');

        return $input;
    }
}
