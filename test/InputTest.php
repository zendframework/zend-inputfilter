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
use PHPUnit_Framework_TestCase as TestCase;
use RuntimeException;
use stdClass;
use Zend\Filter;
use Zend\InputFilter\Input;
use Zend\Validator;
use Zend\Validator\ValidatorChain;

/**
 * @covers Zend\InputFilter\Input
 */
class InputTest extends TestCase
{
    /**
     * @var Input
     */
    protected $input;

    public function setUp()
    {
        $this->input = new Input('foo');
    }

    public function testConstructorRequiresAName()
    {
        $this->assertEquals('foo', $this->input->getName());
    }

    public function testInputHasEmptyFilterChainByDefault()
    {
        $filters = $this->input->getFilterChain();
        $this->assertInstanceOf(Filter\FilterChain::class, $filters);
        $this->assertEquals(0, count($filters));
    }

    public function testInputHasEmptyValidatorChainByDefault()
    {
        $validators = $this->input->getValidatorChain();
        $this->assertInstanceOf(Validator\ValidatorChain::class, $validators);
        $this->assertEquals(0, count($validators));
    }

    public function testCanInjectFilterChain()
    {
        $chain = new Filter\FilterChain();
        $this->input->setFilterChain($chain);
        $this->assertSame($chain, $this->input->getFilterChain());
    }

    public function testCanInjectValidatorChain()
    {
        $chain = new Validator\ValidatorChain();
        $this->input->setValidatorChain($chain);
        $this->assertSame($chain, $this->input->getValidatorChain());
    }

    public function testInputIsMarkedAsRequiredByDefault()
    {
        $this->assertTrue($this->input->isRequired());
    }

    public function testRequiredFlagIsMutable()
    {
        $this->input->setRequired(false);
        $this->assertFalse($this->input->isRequired());
    }

    public function testInputDoesNotAllowEmptyValuesByDefault()
    {
        $this->assertFalse($this->input->allowEmpty());
    }

    public function testAllowEmptyFlagIsMutable()
    {
        $this->input->setAllowEmpty(true);
        $this->assertTrue($this->input->allowEmpty());
    }

    public function testContinueIfEmptyFlagIsFalseByDefault()
    {
        $input = new Input('foo');
        $this->assertFalse($input->continueIfEmpty());
    }

    public function testContinueIfEmptyFlagIsMutable()
    {
        $input = new Input('foo');
        $input->setContinueIfEmpty(true);
        $this->assertTrue($input->continueIfEmpty());
    }

    /**
     * @dataProvider setValueProvider
     */
    public function testSetFallbackValue($fallbackValue)
    {
        $input = $this->input;

        $return = $input->setFallbackValue($fallbackValue);
        $this->assertSame($input, $return, 'setFallbackValue() must return it self');

        $this->assertEquals($fallbackValue, $input->getFallbackValue(), 'getFallbackValue() value not match');
        $this->assertEquals(true, $input->hasFallback(), 'hasFallback() value not match');
    }

    /**
     * @dataProvider fallbackValueVsIsValidProvider
     */
    public function testFallbackValueVsIsValidRules($required, $fallbackValue, $originalValue, $isValid, $expectedValue)
    {
        $input = $this->input;
        $input->setContinueIfEmpty(true);

        $input->setRequired($required);
        $input->setValidatorChain($this->createValidatorChainMock($isValid));
        $input->setFallbackValue($fallbackValue);
        $input->setValue($originalValue);

        $this->assertTrue(
            $input->isValid(),
            'isValid() should be return always true when fallback value is set. Detail: ' .
            json_encode($input->getMessages())
        );
        $this->assertEquals([], $input->getMessages(), 'getMessages() should be empty because the input is valid');
        $this->assertSame($expectedValue, $input->getRawValue(), 'getRawValue() value not match');
        $this->assertSame($expectedValue, $input->getValue(), 'getValue() value not match');
    }

    /**
     * @dataProvider fallbackValueVsIsValidProvider
     */
    public function testFallbackValueVsIsValidRulesWhenValueNotSet($required, $fallbackValue, $originalValue, $isValid)
    {
        $expectedValue = $fallbackValue; // Should always return the fallback value

        $input = $this->input;
        $input->setContinueIfEmpty(true);

        $input->setRequired($required);
        $input->setValidatorChain($this->createValidatorChainMock($isValid));
        $input->setFallbackValue($fallbackValue);

        $this->assertTrue(
            $input->isValid(),
            'isValid() should be return always true when fallback value is set. Detail: ' .
            json_encode($input->getMessages())
        );
        $this->assertEquals([], $input->getMessages(), 'getMessages() should be empty because the input is valid');
        $this->assertSame($expectedValue, $input->getRawValue(), 'getRawValue() value not match');
        $this->assertSame($expectedValue, $input->getValue(), 'getValue() value not match');
    }

    public function testRequiredWithoutFallbackAndValueNotSetThenFail()
    {
        $input = $this->input;
        $input->setRequired(true);
        $input->setContinueIfEmpty(true);

        $this->assertFalse(
            $input->isValid(),
            'isValid() should be return always false when no fallback value, is required, and not data is set.'
        );
        $this->assertEquals(['Value is required'], $input->getMessages(), 'getMessages() should be empty because the input is valid');
    }

    public function testNotEmptyValidatorNotInjectedIfContinueIfEmptyIsTrue()
    {
        $input = new Input('foo');
        $input->setContinueIfEmpty(true);
        $input->setValue('');
        $input->isValid();
        $validators = $input->getValidatorChain()
                                ->getValidators();
        $this->assertEmpty($validators);
    }

    public function testValueIsNullByDefault()
    {
        $this->assertNull($this->input->getValue());
    }

    public function testValueMayBeInjected()
    {
        $this->input->setValue('bar');
        $this->assertEquals('bar', $this->input->getValue());
    }

    public function testRetrievingValueFiltersTheValue()
    {
        $this->input->setValue('bar');
        $filter = new Filter\StringToUpper();
        $this->input->getFilterChain()->attach($filter);
        $this->assertEquals('BAR', $this->input->getValue());
    }

    public function testCanRetrieveRawValue()
    {
        $this->input->setValue('bar');
        $filter = new Filter\StringToUpper();
        $this->input->getFilterChain()->attach($filter);
        $this->assertEquals('bar', $this->input->getRawValue());
    }

    public function testValidationOperatesOnFilteredValue()
    {
        $this->input->setValue(' 123 ');
        $filter = new Filter\StringTrim();
        $this->input->getFilterChain()->attach($filter);
        $validator = new Validator\Digits();
        $this->input->getValidatorChain()->attach($validator);
        $this->assertTrue($this->input->isValid());
    }

    public function testGetMessagesReturnsValidationMessages()
    {
        $this->input->setValue('bar');
        $validator = new Validator\Digits();
        $this->input->getValidatorChain()->attach($validator);
        $this->assertFalse($this->input->isValid());
        $messages = $this->input->getMessages();
        $this->assertArrayHasKey(Validator\Digits::NOT_DIGITS, $messages);
    }

    public function testSpecifyingMessagesToInputReturnsThoseOnFailedValidation()
    {
        $this->input->setValue('bar');
        $validator = new Validator\Digits();
        $this->input->getValidatorChain()->attach($validator);
        $this->input->setErrorMessage('Please enter only digits');
        $this->assertFalse($this->input->isValid());
        $messages = $this->input->getMessages();
        $this->assertArrayNotHasKey(Validator\Digits::NOT_DIGITS, $messages);
        $this->assertContains('Please enter only digits', $messages);
    }

    public function testBreakOnFailureFlagIsOffByDefault()
    {
        $this->assertFalse($this->input->breakOnFailure());
    }

    public function testBreakOnFailureFlagIsMutable()
    {
        $this->input->setBreakOnFailure(true);
        $this->assertTrue($this->input->breakOnFailure());
    }

    public function testNotEmptyValidatorAddedWhenIsValidIsCalled()
    {
        $this->assertTrue($this->input->isRequired());
        $this->input->setValue('');
        $validatorChain = $this->input->getValidatorChain();
        $this->assertEquals(0, count($validatorChain->getValidators()));

        $this->assertFalse($this->input->isValid());
        $messages = $this->input->getMessages();
        $this->assertArrayHasKey('isEmpty', $messages);
        $this->assertEquals(1, count($validatorChain->getValidators()));

        // Assert that NotEmpty validator wasn't added again
        $this->assertFalse($this->input->isValid());
        $this->assertEquals(1, count($validatorChain->getValidators()));
    }

    public function testRequiredNotEmptyValidatorNotAddedWhenOneExists()
    {
        $this->assertTrue($this->input->isRequired());
        $this->input->setValue('');

        /** @var Validator\NotEmpty|MockObject $notEmptyMock */
        $notEmptyMock = $this->getMock(Validator\NotEmpty::class, ['isValid']);
        $notEmptyMock->expects($this->exactly(1))
                     ->method('isValid')
                     ->will($this->returnValue(false));

        $validatorChain = $this->input->getValidatorChain();
        $validatorChain->prependValidator($notEmptyMock);
        $this->assertFalse($this->input->isValid());

        $validators = $validatorChain->getValidators();
        $this->assertEquals(1, count($validators));
        $this->assertEquals($notEmptyMock, $validators[0]['instance']);
    }

    public function testNotAllowEmptyWithFilterConvertsNonemptyToEmptyIsNotValid()
    {
        $this->input->setValue('nonempty')
                    ->getFilterChain()->attach(new Filter\Callback(function () {
                        return '';
                    }));
        $this->assertFalse($this->input->isValid());
    }

    public function testNotAllowEmptyWithFilterConvertsEmptyToNonEmptyIsValid()
    {
        $this->input->setValue('')
                    ->getFilterChain()->attach(new Filter\Callback(function () {
                        return 'nonempty';
                    }));
        $this->assertTrue($this->input->isValid());
    }

    public function testMerge()
    {
        $input = new Input('foo');
        $input->setValue(' 123 ');
        $filter = new Filter\StringTrim();
        $input->getFilterChain()->attach($filter);
        $validator = new Validator\Digits();
        $input->getValidatorChain()->attach($validator);

        $input2 = new Input('bar');
        $input2->merge($input);
        $validatorChain = $input->getValidatorChain();
        $filterChain    = $input->getFilterChain();

        $this->assertEquals(' 123 ', $input2->getRawValue());
        $this->assertEquals(1, $validatorChain->count());
        $this->assertEquals(1, $filterChain->count());

        $validators = $validatorChain->getValidators();
        $this->assertInstanceOf(Validator\Digits::class, $validators[0]['instance']);

        $filters = $filterChain->getFilters()->toArray();
        $this->assertInstanceOf(Filter\StringTrim::class, $filters[0]);
    }

    public function testDoNotInjectNotEmptyValidatorIfAnywhereInChain()
    {
        $this->assertTrue($this->input->isRequired());
        $this->input->setValue('');

        /** @var Validator\NotEmpty|MockObject $notEmptyMock */
        $notEmptyMock = $this->getMock(Validator\NotEmpty::class, ['isValid']);
        $notEmptyMock->expects($this->exactly(1))
                     ->method('isValid')
                     ->will($this->returnValue(false));

        $validatorChain = $this->input->getValidatorChain();
        $validatorChain->attach(new Validator\Digits());
        $validatorChain->attach($notEmptyMock);
        $this->assertFalse($this->input->isValid());

        $validators = $validatorChain->getValidators();
        $this->assertEquals(2, count($validators));
        $this->assertEquals($notEmptyMock, $validators[1]['instance']);
    }

    public function testMergeRetainsContinueIfEmptyFlag()
    {
        $input = new Input('foo');
        $input->setContinueIfEmpty(true);

        $input2 = new Input('bar');
        $input2->merge($input);
        $this->assertTrue($input2->continueIfEmpty());
    }

    public function testMergeRetainsAllowEmptyFlag()
    {
        $input = new Input('foo');
        $input->setRequired(true);
        $input->setAllowEmpty(true);

        $input2 = new Input('bar');
        $input2->setRequired(true);
        $input2->setAllowEmpty(false);
        $input2->merge($input);

        $this->assertTrue($input2->isRequired());
        $this->assertTrue($input2->allowEmpty());
    }

    /**
     * @group 7448
     * @dataProvider isRequiredVsAllowEmptyVsContinueIfEmptyVsIsValidProvider
     */
    public function testIsRequiredVsAllowEmptyVsContinueIfEmptyVsIsValid(
        $required,
        $allowEmpty,
        $continueIfEmpty,
        $validator,
        $value,
        $expectedIsValid,
        $expectedMessages
    ) {
        $this->input->setRequired($required);
        $this->input->setAllowEmpty($allowEmpty);
        $this->input->setContinueIfEmpty($continueIfEmpty);
        $this->input->getValidatorChain()
            ->attach($validator)
        ;
        $this->input->setValue($value);

        $this->assertEquals(
            $expectedIsValid,
            $this->input->isValid(),
            'isValid() value not match. Detail: ' . json_encode($this->input->getMessages())
        );
        $this->assertEquals($expectedMessages, $this->input->getMessages(), 'getMessages() value not match');
    }

    public function whenRequiredAndAllowEmptyAndNotContinueIfEmptyValidatorsAreNotRun()
    {
        $validator = new Validator\Callback(function ($value) {
            throw new RuntimeException('Validator executed when it should not be');
        });

        $requiredFirst = new Input('foo');
        $requiredFirst->setRequired(true)
            ->setAllowEmpty(true)
            ->setContinueIfEmpty(false)
            ->getValidatorChain()->attach($validator);

        return [
            'required-first-null'  => [$requiredFirst, null],
            'required-first-empty' => [$requiredFirst, ''],
            'required-first-array' => [$requiredFirst, []],
        ];
    }

    /**
     * @group 7448
     * @dataProvider whenRequiredAndAllowEmptyAndNotContinueIfEmptyValidatorsAreNotRun
     */
    public function testWhenRequiredAndAllowEmptyAndNotContinueIfEmptyValidatorsAreNotRun(Input $input, $value)
    {
        $input->setValue($value);
        $this->assertTrue($input->isValid());
    }

    public function whenRequiredAndAllowEmptyAndContinueIfEmptyValidatorsAreRun()
    {
        $alwaysInvalid = new Validator\Callback(function ($value) {
            if (! empty($value)) {
                throw new RuntimeException('Unexpected non-empty value provided to validate');
            }
            return false;
        });

        $emptyIsValid = new Validator\Callback(function ($value) {
            if (! empty($value)) {
                throw new RuntimeException('Unexpected non-empty value provided to validate');
            }
            return true;
        });

        $requiredFirstInvalid = new Input('foo');
        $requiredFirstInvalid->getValidatorChain()->attach($alwaysInvalid);
        $requiredFirstValid   = new Input('foo');
        $requiredFirstValid->getValidatorChain()->attach($emptyIsValid);
        foreach ([$requiredFirstValid, $requiredFirstInvalid] as $input) {
            $input->setRequired(true)
                ->setAllowEmpty(true)
                ->setContinueIfEmpty(true);
        }

        return [
            'required-first-null-valid'    => [$requiredFirstValid, null, 'assertTrue'],
            'required-first-null-invalid'  => [$requiredFirstInvalid, null, 'assertFalse'],
            'required-first-empty-valid'   => [$requiredFirstValid, '', 'assertTrue'],
            'required-first-empty-invalid' => [$requiredFirstInvalid, '', 'assertFalse'],
            'required-first-array-valid'   => [$requiredFirstValid, [], 'assertTrue'],
            'required-first-array-invalid' => [$requiredFirstInvalid, [], 'assertFalse'],
        ];
    }

    /**
     * @group 7448
     * @dataProvider whenRequiredAndAllowEmptyAndContinueIfEmptyValidatorsAreRun
     */
    public function testWhenRequiredAndAllowEmptyAndContinueIfEmptyValidatorsAreRun(Input $input, $value, $assertion)
    {
        $input->setValue($value);
        $this->{$assertion}($input->isValid());
    }

    public function whenRequiredAndNotAllowEmptyAndNotContinueIfEmptyValidatorsAreNotRun()
    {
        $validator = new Validator\Callback(function ($value) {
            throw new RuntimeException('Validator executed when it should not be');
        });

        $requiredFirst = new Input('foo');
        $requiredFirst->setRequired(true)
            ->setAllowEmpty(false)
            ->setContinueIfEmpty(false)
            ->getValidatorChain()->attach($validator);

        return [
            'required-first-null'  => [$requiredFirst, null],
            'required-first-empty' => [$requiredFirst, ''],
            'required-first-array' => [$requiredFirst, []],
        ];
    }

    /**
     * @group 7448
     * @dataProvider whenRequiredAndNotAllowEmptyAndNotContinueIfEmptyValidatorsAreNotRun
     */
    public function testWhenRequiredAndNotAllowEmptyAndNotContinueIfEmptyValidatorsAreNotRun(Input $input, $value)
    {
        $input->setValue($value);
        $this->assertFalse($input->isValid());
    }

    public function whenRequiredAndNotAllowEmptyAndContinueIfEmptyValidatorsAreRun()
    {
        $alwaysInvalid = new Validator\Callback(function ($value) {
            if (! empty($value)) {
                throw new RuntimeException('Unexpected non-empty value provided to validate');
            }
            return false;
        });

        $emptyIsValid = new Validator\Callback(function ($value) {
            if (! empty($value)) {
                throw new RuntimeException('Unexpected non-empty value provided to validate');
            }
            return true;
        });

        $requiredFirstInvalid = new Input('foo');
        $requiredFirstInvalid->getValidatorChain()->attach($alwaysInvalid);
        $requiredFirstValid   = new Input('foo');
        $requiredFirstValid->getValidatorChain()->attach($emptyIsValid);
        foreach ([$requiredFirstValid, $requiredFirstInvalid] as $input) {
            $input->setRequired(true)
                ->setAllowEmpty(false)
                ->setContinueIfEmpty(true);
        }

        return [
            'required-first-null-valid'    => [$requiredFirstValid, null, 'assertTrue'],
            'required-first-null-invalid'  => [$requiredFirstInvalid, null, 'assertFalse'],
            'required-first-empty-valid'   => [$requiredFirstValid, '', 'assertTrue'],
            'required-first-empty-invalid' => [$requiredFirstInvalid, '', 'assertFalse'],
            'required-first-array-valid'   => [$requiredFirstValid, [], 'assertTrue'],
            'required-first-array-invalid' => [$requiredFirstInvalid, [], 'assertFalse'],
        ];
    }

    /**
     * @group 7448
     * @dataProvider whenRequiredAndNotAllowEmptyAndContinueIfEmptyValidatorsAreRun
     */
    public function testWhenRequiredAndNotAllowEmptyAndContinueIfEmptyValidatorsAreRun(Input $input, $value, $assertion)
    {
        $input->setValue($value);
        $this->{$assertion}($input->isValid());
    }

    public function whenNotRequiredAndAllowEmptyAndNotContinueIfEmptyValidatorsAreNotRun()
    {
        $validator = new Validator\Callback(function ($value) {
            throw new RuntimeException('Validator executed when it should not be');
        });

        $requiredFirst = new Input('foo');
        $requiredFirst->setRequired(false)
            ->setAllowEmpty(true)
            ->setContinueIfEmpty(false)
            ->getValidatorChain()->attach($validator);

        return [
            'required-first-null'  => [$requiredFirst, null],
            'required-first-empty' => [$requiredFirst, ''],
            'required-first-array' => [$requiredFirst, []],
        ];
    }

    /**
     * @group 7448
     * @dataProvider whenNotRequiredAndAllowEmptyAndNotContinueIfEmptyValidatorsAreNotRun
     */
    public function testWhenNotRequiredAndAllowEmptyAndNotContinueIfEmptyValidatorsAreNotRun(Input $input, $value)
    {
        $input->setValue($value);
        $this->assertTrue($input->isValid());
    }

    public function whenNotRequiredAndNotAllowEmptyAndNotContinueIfEmptyValidatorsAreNotRun()
    {
        $validator = new Validator\Callback(function ($value) {
            throw new RuntimeException('Validator executed when it should not be');
        });

        $requiredFirst = new Input('foo');
        $requiredFirst->setRequired(false)
            ->setAllowEmpty(false)
            ->setContinueIfEmpty(false)
            ->getValidatorChain()->attach($validator);

        return [
            'required-first-null'  => [$requiredFirst, null],
            'required-first-empty' => [$requiredFirst, ''],
            'required-first-array' => [$requiredFirst, []],
        ];
    }

    /**
     * @group 7448
     * @dataProvider whenNotRequiredAndNotAllowEmptyAndNotContinueIfEmptyValidatorsAreNotRun
     */
    public function testWhenNotRequiredAndNotAllowEmptyAndNotContinueIfEmptyValidatorsAreNotRun(Input $input, $value)
    {
        $input->setValue($value);
        $this->assertTrue($input->isValid());
    }

    public function whenNotRequiredAndAllowEmptyAndContinueIfEmptyValidatorsAreRun()
    {
        $alwaysInvalid = new Validator\Callback(function ($value) {
            if (! empty($value)) {
                throw new RuntimeException('Unexpected non-empty value provided to validate');
            }
            return false;
        });

        $emptyIsValid = new Validator\Callback(function ($value) {
            if (! empty($value)) {
                throw new RuntimeException('Unexpected non-empty value provided to validate');
            }
            return true;
        });

        $requiredFirstInvalid = new Input('foo');
        $requiredFirstInvalid->getValidatorChain()->attach($alwaysInvalid);
        $requiredFirstValid   = new Input('foo');
        $requiredFirstValid->getValidatorChain()->attach($emptyIsValid);
        foreach ([$requiredFirstValid, $requiredFirstInvalid] as $input) {
            $input->setRequired(false)
                ->setAllowEmpty(true)
                ->setContinueIfEmpty(true);
        }

        return [
            'required-first-null-valid'    => [$requiredFirstValid, null, 'assertTrue'],
            'required-first-null-invalid'  => [$requiredFirstInvalid, null, 'assertFalse'],
            'required-first-empty-valid'   => [$requiredFirstValid, '', 'assertTrue'],
            'required-first-empty-invalid' => [$requiredFirstInvalid, '', 'assertFalse'],
            'required-first-array-valid'   => [$requiredFirstValid, [], 'assertTrue'],
            'required-first-array-invalid' => [$requiredFirstInvalid, [], 'assertFalse'],
        ];
    }

    /**
     * @group 7448
     * @dataProvider whenNotRequiredAndAllowEmptyAndContinueIfEmptyValidatorsAreRun
     */
    public function testWhenNotRequiredAndAllowEmptyAndContinueIfEmptyValidatorsAreRun(Input $input, $value, $assertion)
    {
        $input->setValue($value);
        $this->{$assertion}($input->isValid());
    }

    public function whenNotRequiredAndNotAllowEmptyAndContinueIfEmptyValidatorsAreRun()
    {
        $alwaysInvalid = new Validator\Callback(function ($value) {
            if (! empty($value)) {
                throw new RuntimeException('Unexpected non-empty value provided to validate');
            }
            return false;
        });

        $emptyIsValid = new Validator\Callback(function ($value) {
            if (! empty($value)) {
                throw new RuntimeException('Unexpected non-empty value provided to validate');
            }
            return true;
        });

        $requiredFirstInvalid = new Input('foo');
        $requiredFirstInvalid->getValidatorChain()->attach($alwaysInvalid);
        $requiredFirstValid   = new Input('foo');
        $requiredFirstValid->getValidatorChain()->attach($emptyIsValid);
        foreach ([$requiredFirstValid, $requiredFirstInvalid] as $input) {
            $input->setRequired(false)
                ->setAllowEmpty(false)
                ->setContinueIfEmpty(true);
        }

        return [
            'required-first-null-valid'    => [$requiredFirstValid, null, 'assertTrue'],
            'required-first-null-invalid'  => [$requiredFirstInvalid, null, 'assertFalse'],
            'required-first-empty-valid'   => [$requiredFirstValid, '', 'assertTrue'],
            'required-first-empty-invalid' => [$requiredFirstInvalid, '', 'assertFalse'],
            'required-first-array-valid'   => [$requiredFirstValid, [], 'assertTrue'],
            'required-first-array-invalid' => [$requiredFirstInvalid, [], 'assertFalse'],
        ];
    }

    /**
     * @group 7448
     * @dataProvider whenNotRequiredAndNotAllowEmptyAndContinueIfEmptyValidatorsAreRun
     */
    public function testWhenNotRequiredAndNotAllowEmptyAndContinueIfEmptyValidatorsAreRun(Input $input, $value, $assertion)
    {
        $input->setValue($value);
        $this->{$assertion}($input->isValid());
    }

    /**
     * @dataProvider setValueProvider
     */
    public function testSetValuePutInputInTheDesiredState($value)
    {
        $input = $this->input;
        $this->assertFalse($input->hasValue(), 'Input should not have value by default');

        $input->setValue($value);
        $this->assertTrue($input->hasValue(), "hasValue() didn't return true when value was set");
    }

    /**
     * @dataProvider setValueProvider
     */
    public function testResetValueReturnsInputValueToDefaultValue($value)
    {
        $input = $this->input;
        $originalInput = clone $input;
        $this->assertFalse($input->hasValue(), 'Input should not have value by default');

        $input->setValue($value);
        $this->assertTrue($input->hasValue(), "hasValue() didn't return true when value was set");

        $return = $input->resetValue();
        $this->assertSame($input, $return, 'resetValue() must return itself');
        $this->assertEquals($originalInput, $input, 'Input was not reset to the default value state');
    }

    public function fallbackValueVsIsValidProvider()
    {
        $required = true;
        $isValid = true;

        $originalValue = 'fooValue';
        $fallbackValue = 'fooFallbackValue';

        // @codingStandardsIgnoreStart
        return [
            // Description => [$inputIsRequired, $fallbackValue, $originalValue, $isValid, $expectedValue]
            'Required: T, Input: Invalid. getValue: fallback' => [ $required, $fallbackValue, $originalValue, !$isValid, $fallbackValue],
            'Required: T, Input: Valid. getValue: original' =>   [ $required, $fallbackValue, $originalValue,  $isValid, $originalValue],
            'Required: F, Input: Invalid. getValue: fallback' => [!$required, $fallbackValue, $originalValue, !$isValid, $fallbackValue],
            'Required: F, Input: Valid. getValue: original' =>   [!$required, $fallbackValue, $originalValue,  $isValid, $originalValue],
        ];
        // @codingStandardsIgnoreEnd
    }

    public function setValueProvider()
    {
        $emptyValues = $this->emptyValueProvider();
        $mixedValues = $this->mixedValueProvider();

        $values = array_merge($emptyValues, $mixedValues);

        return $values;
    }

    public function isRequiredVsAllowEmptyVsContinueIfEmptyVsIsValidProvider()
    {
        $emptyValues = $this->emptyValueProvider();

        $isRequired = true;
        $aEmpty = true;
        $cIEmpty = true;
        $isValid = true;

        $validatorMsg = ['FooValidator' => 'Invalid Value'];
        $notEmptyMsg = ['isEmpty' => "Value is required and can't be empty"];

        $validatorNotCall = function ($value, $context = null) {
            return $this->createValidatorMock(null, $value, $context);
        };
        $validatorInvalid = function ($value, $context = null) use ($validatorMsg) {
            return $this->createValidatorMock(false, $value, $context, $validatorMsg);
        };
        $validatorValid = function ($value, $context = null) {
            return $this->createValidatorMock(true, $value, $context);
        };

        // @codingStandardsIgnoreStart
        $dataTemplates=[
            // Description => [$isRequired, $allowEmpty, $continueIfEmpty, $validator, [$values], $expectedIsValid, $expectedMessages]
            'Required: T; AEmpty: T; CIEmpty: T; Validator: T' => [ $isRequired,  $aEmpty,  $cIEmpty, $validatorValid  , $emptyValues,  $isValid, []],
            'Required: T; AEmpty: T; CIEmpty: T; Validator: F' => [ $isRequired,  $aEmpty,  $cIEmpty, $validatorInvalid, $emptyValues, !$isValid, $validatorMsg],
            'Required: T; AEmpty: T; CIEmpty: F; Validator: X' => [ $isRequired,  $aEmpty, !$cIEmpty, $validatorNotCall, $emptyValues,  $isValid, []],
            'Required: T; AEmpty: F; CIEmpty: T; Validator: T' => [ $isRequired, !$aEmpty,  $cIEmpty, $validatorValid  , $emptyValues,  $isValid, []],
            'Required: T; AEmpty: F; CIEmpty: T; Validator: F' => [ $isRequired, !$aEmpty,  $cIEmpty, $validatorInvalid, $emptyValues, !$isValid, $validatorMsg],
            'Required: T; AEmpty: F; CIEmpty: F; Validator: X' => [ $isRequired, !$aEmpty, !$cIEmpty, $validatorNotCall, $emptyValues, !$isValid, $notEmptyMsg],
            'Required: F; AEmpty: T; CIEmpty: T; Validator: T' => [!$isRequired,  $aEmpty,  $cIEmpty, $validatorValid  , $emptyValues,  $isValid, []],
            'Required: F; AEmpty: T; CIEmpty: T; Validator: F' => [!$isRequired,  $aEmpty,  $cIEmpty, $validatorInvalid, $emptyValues, !$isValid, $validatorMsg],
            'Required: F; AEmpty: T; CIEmpty: F; Validator: X' => [!$isRequired,  $aEmpty, !$cIEmpty, $validatorNotCall, $emptyValues,  $isValid, []],
            'Required: F; AEmpty: F; CIEmpty: T; Validator: T' => [!$isRequired, !$aEmpty,  $cIEmpty, $validatorValid  , $emptyValues,  $isValid, []],
            'Required: F; AEmpty: F; CIEmpty: T; Validator: F' => [!$isRequired, !$aEmpty,  $cIEmpty, $validatorInvalid, $emptyValues, !$isValid, $validatorMsg],
            'Required: F; AEmpty: F; CIEmpty: F; Validator: X' => [!$isRequired, !$aEmpty, !$cIEmpty, $validatorNotCall, $emptyValues,  $isValid, []],
        ];
        // @codingStandardsIgnoreEnd

        // Expand data template matrix for each possible input value.
        // Description => [$isRequired, $allowEmpty, $continueIfEmpty, $validator, $value, $expectedIsValid]
        $dataSets = [];
        foreach ($dataTemplates as $dataTemplateDescription => $dataTemplate) {
            foreach ($dataTemplate[4] as $valueDescription => $value) {
                $tmpTemplate = $dataTemplate;
                $tmpTemplate[3] = $dataTemplate[3]($value['filtered']); // Get validator mock for each data set
                $tmpTemplate[4] = $value['raw']; // expand value

                $dataSets[$dataTemplateDescription . ' / ' . $valueDescription] = $tmpTemplate;
            }
        }

        return $dataSets;
    }

    public function emptyValueProvider()
    {
        return [
            // Description => [$value]
            'null' => [
                'raw' => null,
                'filtered' => null,
            ],
            '""' => [
                'raw' => '',
                'filtered' => '',
            ],
//            '"0"' => ['0'],
//            '0' => [0],
//            '0.0' => [0.0],
//            'false' => [false],
            '[]' => [
                'raw' => [],
                'filtered' => [],
            ],
        ];
    }

    public function mixedValueProvider()
    {
        return [
            // Description => [$value]
            '"0"' => ['0'],
            '0' => [0],
            '0.0' => [0.0],
            'false' => [false],
            'php' => ['php'],
            'whitespace' => [' '],
            '1' => [1],
            '1.0' => [1.0],
            'true' => [true],
            '["php"]' => [['php']],
            'object' => [new stdClass()],
            // @codingStandardsIgnoreStart
            'callable' => [function () {}],
            // @codingStandardsIgnoreEnd
        ];
    }

    /**
     * @param null|bool $isValid If set stub isValid method for return the argument value.
     *
     * @return MockObject|ValidatorChain
     */
    protected function createValidatorChainMock($isValid = null)
    {
        /** @var ValidatorChain|MockObject $validatorChain */
        $validatorChain = $this->getMock(ValidatorChain::class);

        if ($isValid !== null) {
            $validatorChain->method('isValid')
                ->willReturn($isValid)
            ;
        }

        return $validatorChain;
    }

    /**
     * @param null|bool $isValid
     * @param mixed $value
     * @param mixed $context
     * @param string[] $messages
     *
     * @return Validator\ValidatorInterface|MockObject
     */
    protected function createValidatorMock($isValid, $value, $context = null, $messages = [])
    {
        /** @var Validator\ValidatorInterface|MockObject $validator */
        $validator = $this->getMock(Validator\ValidatorInterface::class);

        if (($isValid === false) || ($isValid === true)) {
            $validator->expects($this->once())
                ->method('isValid')
                ->with($value, $context)
                ->willReturn($isValid)
            ;
        } else {
            $validator->expects($this->never())
                ->method('isValid')
                ->with($value, $context)
            ;
        }

        $validator->method('getMessages')
            ->willReturn($messages)
        ;

        return $validator;
    }
}
