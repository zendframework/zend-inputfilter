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

    public function testNotArrayValueCannotBeInjected()
    {
        $input = $this->createDefaultInput();

        $this->setExpectedException(InvalidArgumentException::class);
        $input->setValue('bar');
    }

    public function testValueMayBeInjected()
    {
        $input = $this->createDefaultInput();

        $input->setValue(['bar']);
        $this->assertEquals(['bar'], $input->getValue());
    }

    public function testRetrievingValueFiltersTheValue()
    {
        $input = $this->createDefaultInput();

        $input->setValue(['bar']);
        $filter = new Filter\StringToUpper();
        $input->getFilterChain()->attach($filter);
        $this->assertEquals(['BAR'], $input->getValue());
    }

    public function testCanRetrieveRawValue()
    {
        $input = $this->createDefaultInput();

        $input->setValue(['bar']);
        $filter = new Filter\StringToUpper();
        $input->getFilterChain()->attach($filter);
        $this->assertEquals(['bar'], $input->getRawValue());
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

    public function testMerge()
    {
        $input = $this->createDefaultInput();
        $input->setValue([' 123 ']);
        $filter = new Filter\StringTrim();
        $input->getFilterChain()->attach($filter);
        $validator = new Validator\Digits();
        $input->getValidatorChain()->attach($validator);

        $input2 = new ArrayInput('bar');
        $input2->merge($input);
        $validatorChain = $input->getValidatorChain();
        $filterChain    = $input->getFilterChain();

        $this->assertEquals([' 123 '], $input2->getRawValue());
        $this->assertEquals(1, $validatorChain->count());
        $this->assertEquals(1, $filterChain->count());

        $validators = $validatorChain->getValidators();
        $this->assertInstanceOf(Validator\Digits::class, $validators[0]['instance']);

        $filters = $filterChain->getFilters()->toArray();
        $this->assertInstanceOf(Filter\StringTrim::class, $filters[0]);
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
