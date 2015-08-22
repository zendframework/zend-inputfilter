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
use Zend\Filter;
use Zend\InputFilter\Input;
use Zend\Validator;

/**
 * @covers Zend\InputFilter\Input
 */
class InputTest extends TestCase
{
    use EmptyContextInterfaceTestTrait;
    use InputInterfaceTestTrait;

    public function testConstructorRequiresAName()
    {
        $input = $this->createDefaultInput();

        $this->assertEquals('foo', $input->getName());
    }

    /**
     * Specific Input::merge extras
     */
    public function testInputMerge()
    {
        $source = new Input();
        $source->setContinueIfEmpty(true);

        $target = $this->createDefaultInput();
        $target->setContinueIfEmpty(false);

        $return = $target->merge($source);
        $this->assertSame($target, $return, 'merge() must return it self');

        $this->assertEquals(true, $target->continueIfEmpty(), 'continueIfEmpty() value not match');
    }

    public function testInputHasEmptyFilterChainByDefault()
    {
        $input = $this->createDefaultInput();

        $filters = $input->getFilterChain();
        $this->assertInstanceOf(Filter\FilterChain::class, $filters);
        $this->assertEquals(0, count($filters));
    }

    public function testInputHasEmptyValidatorChainByDefault()
    {
        $input = $this->createDefaultInput();

        $validators = $input->getValidatorChain();
        $this->assertInstanceOf(Validator\ValidatorChain::class, $validators);
        $this->assertEquals(0, count($validators));
    }

    public function testCanInjectFilterChain()
    {
        $input = $this->createDefaultInput();

        $chain = new Filter\FilterChain();
        $input->setFilterChain($chain);
        $this->assertSame($chain, $input->getFilterChain());
    }

    public function testCanInjectValidatorChain()
    {
        $input = $this->createDefaultInput();

        $chain = new Validator\ValidatorChain();
        $input->setValidatorChain($chain);
        $this->assertSame($chain, $input->getValidatorChain());
    }

    public function testInputIsMarkedAsRequiredByDefault()
    {
        $input = $this->createDefaultInput();

        $this->assertTrue($input->isRequired());
    }

    public function testRequiredFlagIsMutable()
    {
        $input = $this->createDefaultInput();

        $input->setRequired(false);
        $this->assertFalse($input->isRequired());
    }

    public function testInputDoesNotAllowEmptyValuesByDefault()
    {
        $input = $this->createDefaultInput();

        $this->assertFalse($input->allowEmpty());
    }

    public function testAllowEmptyFlagIsMutable()
    {
        $input = $this->createDefaultInput();

        $input->setAllowEmpty(true);
        $this->assertTrue($input->allowEmpty());
    }

    public function testContinueIfEmptyFlagIsFalseByDefault()
    {
        $input = $this->createDefaultInput();
        $this->assertFalse($input->continueIfEmpty());
    }

    public function testNotEmptyValidatorNotInjectedIfContinueIfEmptyIsTrue()
    {
        $input = $this->createDefaultInput();
        $input->setContinueIfEmpty(true);
        $input->setValue('');
        $input->isValid();
        $validators = $input->getValidatorChain()
                                ->getValidators();
        $this->assertEmpty($validators);
    }

    public function testValueIsNullByDefault()
    {
        $input = $this->createDefaultInput();

        $this->assertNull($input->getValue());
    }

    public function testValueMayBeInjected()
    {
        $input = $this->createDefaultInput();

        $input->setValue('bar');
        $this->assertEquals('bar', $input->getValue());
    }

    public function testRetrievingValueFiltersTheValue()
    {
        $input = $this->createDefaultInput();

        $input->setValue('bar');
        $filter = new Filter\StringToUpper();
        $input->getFilterChain()->attach($filter);
        $this->assertEquals('BAR', $input->getValue());
    }

    public function testCanRetrieveRawValue()
    {
        $input = $this->createDefaultInput();

        $input->setValue('bar');
        $filter = new Filter\StringToUpper();
        $input->getFilterChain()->attach($filter);
        $this->assertEquals('bar', $input->getRawValue());
    }

    public function testIsValidReturnsFalseIfValidationChainFails()
    {
        $input = $this->createDefaultInput();

        $input->setValue('bar');
        $validator = new Validator\Digits();
        $input->getValidatorChain()->attach($validator);
        $this->assertFalse($input->isValid());
    }

    public function testIsValidReturnsTrueIfValidationChainSucceeds()
    {
        $input = $this->createDefaultInput();

        $input->setValue('123');
        $validator = new Validator\Digits();
        $input->getValidatorChain()->attach($validator);
        $this->assertTrue($input->isValid());
    }

    public function testValidationOperatesOnFilteredValue()
    {
        $input = $this->createDefaultInput();

        $input->setValue(' 123 ');
        $filter = new Filter\StringTrim();
        $input->getFilterChain()->attach($filter);
        $validator = new Validator\Digits();
        $input->getValidatorChain()->attach($validator);
        $this->assertTrue($input->isValid());
    }

    public function testGetMessagesReturnsValidationMessages()
    {
        $input = $this->createDefaultInput();

        $input->setValue('bar');
        $validator = new Validator\Digits();
        $input->getValidatorChain()->attach($validator);
        $this->assertFalse($input->isValid());
        $messages = $input->getMessages();
        $this->assertArrayHasKey(Validator\Digits::NOT_DIGITS, $messages);
    }

    public function testSpecifyingMessagesToInputReturnsThoseOnFailedValidation()
    {
        $input = $this->createDefaultInput();

        $input->setValue('bar');
        $validator = new Validator\Digits();
        $input->getValidatorChain()->attach($validator);
        $input->setErrorMessage('Please enter only digits');
        $this->assertFalse($input->isValid());
        $messages = $input->getMessages();
        $this->assertArrayNotHasKey(Validator\Digits::NOT_DIGITS, $messages);
        $this->assertContains('Please enter only digits', $messages);
    }

    public function testBreakOnFailureFlagIsOffByDefault()
    {
        $input = $this->createDefaultInput();

        $this->assertFalse($input->breakOnFailure());
    }

    public function testBreakOnFailureFlagIsMutable()
    {
        $input = $this->createDefaultInput();

        $input->setBreakOnFailure(true);
        $this->assertTrue($input->breakOnFailure());
    }

    public function testNotEmptyValidatorAddedWhenIsValidIsCalled()
    {
        $input = $this->createDefaultInput();

        $this->assertTrue($input->isRequired());
        $input->setValue('');
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
        $input->setValue('');

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

    public function emptyValuesProvider()
    {
        return [
            [null],
            [''],
            [[]],
        ];
    }

    /**
     * @dataProvider emptyValuesProvider
     */
    public function testValidatorSkippedIfValueIsEmptyAndAllowedAndNotContinue($emptyValue)
    {
        $input = $this->createDefaultInput();

        $validator = function () {
            return false;
        };
        $input->setAllowEmpty(true)
            ->setContinueIfEmpty(false)
            ->setValue($emptyValue)
            ->getValidatorChain()->attach(new Validator\Callback($validator));

        $this->assertTrue($input->isValid());
    }

    /**
     * @dataProvider emptyValuesProvider
     */
    public function testAllowEmptyOptionSet($emptyValue, $input = null)
    {
        if (empty($input)) {
            $input = $this->createDefaultInput();
        }

        $input->setAllowEmpty(true);
        $input->setValue($emptyValue);
        $this->assertTrue($input->isValid());
    }

    /**
     * @dataProvider emptyValuesProvider
     */
    public function testAllowEmptyOptionNotSet($emptyValue, $input = null)
    {
        if (empty($input)) {
            $input = $this->createDefaultInput();
        }

        $input->setAllowEmpty(false);
        $input->setValue($emptyValue);
        $this->assertFalse($input->isValid());
    }

    /**
     * @dataProvider emptyValuesProvider
     */
    public function testValidatorInvokedIfValueIsEmptyAndAllowedAndContinue($emptyValue)
    {
        $input = $this->createDefaultInput();

        $message = 'failure by explicit validator';
        $validator = new Validator\Callback(function ($value) {
            return false;
        });
        $validator->setMessage($message);
        $input->setAllowEmpty(true)
                    ->setContinueIfEmpty(true)
                    ->setValue($emptyValue)
                    ->getValidatorChain()->attach($validator);
        $this->assertFalse($input->isValid());
        // Test reason for validation failure; ensures that failure was not
        // caused by accidentally injected NotEmpty validator
        $this->assertEquals(['callbackValue' => $message], $input->getMessages());
    }

    public function testNotAllowEmptyWithFilterConvertsNonemptyToEmptyIsNotValid()
    {
        $input = $this->createDefaultInput();

        $input->setValue('nonempty')
                    ->getFilterChain()->attach(new Filter\Callback(function () {
                        return '';
                    }));
        $this->assertFalse($input->isValid());
    }

    public function testNotAllowEmptyWithFilterConvertsEmptyToNonEmptyIsValid()
    {
        $input = $this->createDefaultInput();

        $input->setValue('')
                    ->getFilterChain()->attach(new Filter\Callback(function () {
                        return 'nonempty';
                    }));
        $this->assertTrue($input->isValid());
    }

    public function testDoNotInjectNotEmptyValidatorIfAnywhereInChain()
    {
        $input = $this->createDefaultInput();

        $this->assertTrue($input->isRequired());
        $input->setValue('');

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
                'fallbackValue' => null
            ],
            [
                'fallbackValue' => ''
            ],
            [
                'fallbackValue' => 'some value'
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
        $input->setValue('123'); // not a date

        $this->assertTrue($input->isValid());
        $this->assertEmpty($input->getMessages());
        $this->assertSame($fallbackValue, $input->getValue());
    }

    /**
     * @group 7445
     */
    public function testInputIsValidWhenUsingSetRequiredAtStart()
    {
        $input = $this->createDefaultInput();
        $input->setName('foo')
              ->setRequired(false)
              ->setAllowEmpty(false)
              ->setContinueIfEmpty(false);

        $this->assertTrue($input->isValid());
    }

    /**
     * @group 7445
     */
    public function testInputIsValidWhenUsingSetRequiredAtEnd()
    {
        $input = $this->createDefaultInput();
        $input->setName('foo')
              ->setAllowEmpty(false)
              ->setContinueIfEmpty(false)
              ->setRequired(false);

        $this->assertTrue($input->isValid());
    }

    public function whenRequiredAndAllowEmptyAndNotContinueIfEmptyValidatorsAreNotRun()
    {
        $validator = new Validator\Callback(function ($value) {
            throw new RuntimeException('Validator executed when it should not be');
        });

        $requiredFirst = $this->createDefaultInput();
        $requiredFirst->setRequired(true)
            ->setAllowEmpty(true)
            ->setContinueIfEmpty(false)
            ->getValidatorChain()->attach($validator);

        $requiredLast = $this->createDefaultInput();
        $requiredLast->setAllowEmpty(true)
            ->setContinueIfEmpty(false)
            ->setRequired(true)
            ->getValidatorChain()->attach($validator);

        return [
            'required-first-null'  => [$requiredFirst, null],
            'required-last-null'   => [$requiredLast, null],
            'required-first-empty' => [$requiredFirst, ''],
            'required-last-empty'  => [$requiredLast, ''],
            'required-first-array' => [$requiredFirst, []],
            'required-last-array'  => [$requiredLast, []],
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

        $requiredFirstInvalid = $this->createDefaultInput();
        $requiredFirstValid   = $this->createDefaultInput();
        foreach ([$requiredFirstValid, $requiredFirstInvalid] as $input) {
            $input->setRequired(true)
                ->setAllowEmpty(true)
                ->setContinueIfEmpty(true);
        }

        $requiredLastInvalid = $this->createDefaultInput();
        $requiredLastValid   = $this->createDefaultInput();
        foreach ([$requiredLastValid, $requiredLastInvalid] as $input) {
            $input->setAllowEmpty(true)
                ->setContinueIfEmpty(true)
                ->setRequired(true);
        }

        foreach ([$requiredFirstValid, $requiredLastValid] as $input) {
            $input->getValidatorChain()->attach($emptyIsValid);
        }

        foreach ([$requiredFirstInvalid, $requiredLastInvalid] as $input) {
            $input->getValidatorChain()->attach($alwaysInvalid);
        }

        return [
            'required-first-null-valid'    => [$requiredFirstValid, null, 'assertTrue'],
            'required-first-null-invalid'  => [$requiredFirstInvalid, null, 'assertFalse'],
            'required-last-null-valid'     => [$requiredLastValid, null, 'assertTrue'],
            'required-last-null-invalid'   => [$requiredLastInvalid, null, 'assertFalse'],
            'required-first-empty-valid'   => [$requiredFirstValid, '', 'assertTrue'],
            'required-first-empty-invalid' => [$requiredFirstInvalid, '', 'assertFalse'],
            'required-last-empty-valid'    => [$requiredLastValid, '', 'assertTrue'],
            'required-last-empty-invalid'  => [$requiredLastInvalid, '', 'assertFalse'],
            'required-first-array-valid'   => [$requiredFirstValid, [], 'assertTrue'],
            'required-first-array-invalid' => [$requiredFirstInvalid, [], 'assertFalse'],
            'required-last-array-valid'    => [$requiredLastValid, [], 'assertTrue'],
            'required-last-array-invalid'  => [$requiredLastInvalid, [], 'assertFalse'],
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

        $requiredFirst = $this->createDefaultInput();
        $requiredFirst->setRequired(true)
            ->setAllowEmpty(false)
            ->setContinueIfEmpty(false)
            ->getValidatorChain()->attach($validator);

        $requiredLast = $this->createDefaultInput();
        $requiredLast->setAllowEmpty(false)
            ->setContinueIfEmpty(false)
            ->setRequired(true)
            ->getValidatorChain()->attach($validator);

        return [
            'required-first-null'  => [$requiredFirst, null],
            'required-last-null'   => [$requiredLast, null],
            'required-first-empty' => [$requiredFirst, ''],
            'required-last-empty'  => [$requiredLast, ''],
            'required-first-array' => [$requiredFirst, []],
            'required-last-array'  => [$requiredLast, []],
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

        $requiredFirstInvalid = $this->createDefaultInput();
        $requiredFirstValid   = $this->createDefaultInput();
        foreach ([$requiredFirstValid, $requiredFirstInvalid] as $input) {
            $input->setRequired(true)
                ->setAllowEmpty(false)
                ->setContinueIfEmpty(true);
        }

        $requiredLastInvalid = $this->createDefaultInput();
        $requiredLastValid   = $this->createDefaultInput();
        foreach ([$requiredLastValid, $requiredLastInvalid] as $input) {
            $input->setAllowEmpty(false)
                ->setContinueIfEmpty(true)
                ->setRequired(true);
        }

        foreach ([$requiredFirstValid, $requiredLastValid] as $input) {
            $input->getValidatorChain()->attach($emptyIsValid);
        }

        foreach ([$requiredFirstInvalid, $requiredLastInvalid] as $input) {
            $input->getValidatorChain()->attach($alwaysInvalid);
        }

        return [
            'required-first-null-valid'    => [$requiredFirstValid, null, 'assertTrue'],
            'required-first-null-invalid'  => [$requiredFirstInvalid, null, 'assertFalse'],
            'required-last-null-valid'     => [$requiredLastValid, null, 'assertTrue'],
            'required-last-null-invalid'   => [$requiredLastInvalid, null, 'assertFalse'],
            'required-first-empty-valid'   => [$requiredFirstValid, '', 'assertTrue'],
            'required-first-empty-invalid' => [$requiredFirstInvalid, '', 'assertFalse'],
            'required-last-empty-valid'    => [$requiredLastValid, '', 'assertTrue'],
            'required-last-empty-invalid'  => [$requiredLastInvalid, '', 'assertFalse'],
            'required-first-array-valid'   => [$requiredFirstValid, [], 'assertTrue'],
            'required-first-array-invalid' => [$requiredFirstInvalid, [], 'assertFalse'],
            'required-last-array-valid'    => [$requiredLastValid, [], 'assertTrue'],
            'required-last-array-invalid'  => [$requiredLastInvalid, [], 'assertFalse'],
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

        $requiredFirst = $this->createDefaultInput();
        $requiredFirst->setRequired(false)
            ->setAllowEmpty(true)
            ->setContinueIfEmpty(false)
            ->getValidatorChain()->attach($validator);

        $requiredLast = $this->createDefaultInput();
        $requiredLast->setAllowEmpty(true)
            ->setContinueIfEmpty(false)
            ->setRequired(false)
            ->getValidatorChain()->attach($validator);

        return [
            'required-first-null'  => [$requiredFirst, null],
            'required-last-null'   => [$requiredLast, null],
            'required-first-empty' => [$requiredFirst, ''],
            'required-last-empty'  => [$requiredLast, ''],
            'required-first-array' => [$requiredFirst, []],
            'required-last-array'  => [$requiredLast, []],
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

        $requiredFirst = $this->createDefaultInput();
        $requiredFirst->setRequired(false)
            ->setAllowEmpty(false)
            ->setContinueIfEmpty(false)
            ->getValidatorChain()->attach($validator);

        $requiredLast = $this->createDefaultInput();
        $requiredLast->setAllowEmpty(false)
            ->setContinueIfEmpty(false)
            ->setRequired(false)
            ->getValidatorChain()->attach($validator);

        return [
            'required-first-null'  => [$requiredFirst, null],
            'required-last-null'   => [$requiredLast, null],
            'required-first-empty' => [$requiredFirst, ''],
            'required-last-empty'  => [$requiredLast, ''],
            'required-first-array' => [$requiredFirst, []],
            'required-last-array'  => [$requiredLast, []],
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

        $requiredFirstInvalid = $this->createDefaultInput();
        $requiredFirstValid   = $this->createDefaultInput();
        foreach ([$requiredFirstValid, $requiredFirstInvalid] as $input) {
            $input->setRequired(false)
                ->setAllowEmpty(true)
                ->setContinueIfEmpty(true);
        }

        $requiredLastInvalid = $this->createDefaultInput();
        $requiredLastValid   = $this->createDefaultInput();
        foreach ([$requiredLastValid, $requiredLastInvalid] as $input) {
            $input->setAllowEmpty(true)
                ->setContinueIfEmpty(true)
                ->setRequired(false);
        }

        foreach ([$requiredFirstValid, $requiredLastValid] as $input) {
            $input->getValidatorChain()->attach($emptyIsValid);
        }

        foreach ([$requiredFirstInvalid, $requiredLastInvalid] as $input) {
            $input->getValidatorChain()->attach($alwaysInvalid);
        }

        return [
            'required-first-null-valid'    => [$requiredFirstValid, null, 'assertTrue'],
            'required-first-null-invalid'  => [$requiredFirstInvalid, null, 'assertFalse'],
            'required-last-null-valid'     => [$requiredLastValid, null, 'assertTrue'],
            'required-last-null-invalid'   => [$requiredLastInvalid, null, 'assertFalse'],
            'required-first-empty-valid'   => [$requiredFirstValid, '', 'assertTrue'],
            'required-first-empty-invalid' => [$requiredFirstInvalid, '', 'assertFalse'],
            'required-last-empty-valid'    => [$requiredLastValid, '', 'assertTrue'],
            'required-last-empty-invalid'  => [$requiredLastInvalid, '', 'assertFalse'],
            'required-first-array-valid'   => [$requiredFirstValid, [], 'assertTrue'],
            'required-first-array-invalid' => [$requiredFirstInvalid, [], 'assertFalse'],
            'required-last-array-valid'    => [$requiredLastValid, [], 'assertTrue'],
            'required-last-array-invalid'  => [$requiredLastInvalid, [], 'assertFalse'],
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

        $requiredFirstInvalid = $this->createDefaultInput();
        $requiredFirstValid   = $this->createDefaultInput();
        foreach ([$requiredFirstValid, $requiredFirstInvalid] as $input) {
            $input->setRequired(false)
                ->setAllowEmpty(false)
                ->setContinueIfEmpty(true);
        }

        $requiredLastInvalid = $this->createDefaultInput();
        $requiredLastValid   = $this->createDefaultInput();
        foreach ([$requiredLastValid, $requiredLastInvalid] as $input) {
            $input->setAllowEmpty(false)
                ->setContinueIfEmpty(true)
                ->setRequired(false);
        }

        foreach ([$requiredFirstValid, $requiredLastValid] as $input) {
            $input->getValidatorChain()->attach($emptyIsValid);
        }

        foreach ([$requiredFirstInvalid, $requiredLastInvalid] as $input) {
            $input->getValidatorChain()->attach($alwaysInvalid);
        }

        return [
            'required-first-null-valid'    => [$requiredFirstValid, null, 'assertTrue'],
            'required-first-null-invalid'  => [$requiredFirstInvalid, null, 'assertFalse'],
            'required-last-null-valid'     => [$requiredLastValid, null, 'assertTrue'],
            'required-last-null-invalid'   => [$requiredLastInvalid, null, 'assertFalse'],
            'required-first-empty-valid'   => [$requiredFirstValid, '', 'assertTrue'],
            'required-first-empty-invalid' => [$requiredFirstInvalid, '', 'assertFalse'],
            'required-last-empty-valid'    => [$requiredLastValid, '', 'assertTrue'],
            'required-last-empty-invalid'  => [$requiredLastInvalid, '', 'assertFalse'],
            'required-first-array-valid'   => [$requiredFirstValid, [], 'assertTrue'],
            'required-first-array-invalid' => [$requiredFirstInvalid, [], 'assertFalse'],
            'required-last-array-valid'    => [$requiredLastValid, [], 'assertTrue'],
            'required-last-array-invalid'  => [$requiredLastInvalid, [], 'assertFalse'],
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

    protected function createDefaultEmptyContext()
    {
        return $this->createDefaultInput();
    }

    protected function createDefaultInput()
    {
        $input = new Input('foo');

        return $input;
    }
}
