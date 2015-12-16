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
use Zend\Filter\FilterChain;
use Zend\InputFilter\Input;
use Zend\InputFilter\InputInterface;
use Zend\Validator\NotEmpty as NotEmptyValidator;
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

    public function assertRequiredValidationErrorMessage(Input $input, $message = '')
    {
        $message  = $message ?: 'Expected failure message for required input';
        $message .= ';';

        $messages = $input->getMessages();
        $this->assertInternalType('array', $messages, $message . ' non-array messages array');

        $notEmpty         = new NotEmptyValidator();
        $messageTemplates = $notEmpty->getOption('messageTemplates');
        $this->assertSame([
            NotEmptyValidator::IS_EMPTY => $messageTemplates[NotEmptyValidator::IS_EMPTY],
        ], $messages, $message . ' missing NotEmpty::IS_EMPTY key and/or contains additional messages');
    }

    public function testConstructorRequiresAName()
    {
        $this->assertEquals('foo', $this->input->getName());
    }

    public function testInputHasEmptyFilterChainByDefault()
    {
        $filters = $this->input->getFilterChain();
        $this->assertInstanceOf(FilterChain::class, $filters);
        $this->assertEquals(0, count($filters));
    }

    public function testInputHasEmptyValidatorChainByDefault()
    {
        $validators = $this->input->getValidatorChain();
        $this->assertInstanceOf(ValidatorChain::class, $validators);
        $this->assertEquals(0, count($validators));
    }

    public function testCanInjectFilterChain()
    {
        $chain = $this->createFilterChainMock();
        $this->input->setFilterChain($chain);
        $this->assertSame($chain, $this->input->getFilterChain());
    }

    public function testCanInjectValidatorChain()
    {
        $chain = $this->createValidatorChainMock();
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

    public function testSetFallbackValue()
    {
        $fallbackValue = $this->getDummyValue();
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

        $input->setRequired($required);
        $input->setValidatorChain($this->createValidatorChainMock([[$originalValue, null, $isValid]]));
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
    public function testFallbackValueVsIsValidRulesWhenValueNotSet($required, $fallbackValue)
    {
        $expectedValue = $fallbackValue; // Should always return the fallback value

        $input = $this->input;

        $input->setRequired($required);
        $input->setValidatorChain($this->createValidatorChainMock());
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

        $this->assertFalse(
            $input->isValid(),
            'isValid() should be return always false when no fallback value, is required, and not data is set.'
        );
        $this->assertRequiredValidationErrorMessage($input);
    }

    public function testRequiredWithoutFallbackAndValueNotSetThenFailReturnsCustomErrorMessageWhenSet()
    {
        $input = $this->input;
        $input->setRequired(true);
        $input->setErrorMessage('FAILED TO VALIDATE');

        $this->assertFalse(
            $input->isValid(),
            'isValid() should be return always false when no fallback value, is required, and not data is set.'
        );
        $this->assertSame(['FAILED TO VALIDATE'], $input->getMessages());
    }

    /**
     * @group 28
     * @group 60
     */
    public function testRequiredWithoutFallbackAndValueNotSetProvidesNotEmptyValidatorIsEmptyErrorMessage()
    {
        $input = $this->input;
        $input->setRequired(true);

        $this->assertFalse(
            $input->isValid(),
            'isValid() should always return false when no fallback value is present, '
            . 'the input is required, and no data is set.'
        );
        $this->assertRequiredValidationErrorMessage($input);
    }

    /**
     * @group 28
     * @group 60
     */
    public function testRequiredWithoutFallbackAndValueNotSetProvidesCustomErrorMessageWhenSet()
    {
        $input = $this->input;
        $input->setRequired(true);
        $input->setErrorMessage('FAILED TO VALIDATE');

        $this->assertFalse(
            $input->isValid(),
            'isValid() should always return false when no fallback value is present, '
            . 'the input is required, and no data is set.'
        );
        $this->assertSame(['FAILED TO VALIDATE'], $input->getMessages());
    }

    public function testNotRequiredWithoutFallbackAndValueNotSetThenIsValid()
    {
        $input = $this->input;
        $input->setRequired(false);

        // Validator should not to be called
        $input->setValidatorChain($this->createValidatorChainMock());
        $this->assertTrue(
            $input->isValid(),
            'isValid() should be return always true when is not required, and no data is set. Detail: ' .
            json_encode($input->getMessages())
        );
        $this->assertEquals([], $input->getMessages(), 'getMessages() should be empty because the input is valid');
    }

    public function testDefaultGetValue()
    {
        $this->assertNull($this->input->getValue());
    }

    public function testValueMayBeInjected()
    {
        $valueRaw = $this->getDummyValue();

        $this->input->setValue($valueRaw);
        $this->assertEquals($valueRaw, $this->input->getValue());
    }

    public function testRetrievingValueFiltersTheValue()
    {
        $valueRaw = $this->getDummyValue();
        $valueFiltered = $this->getDummyValue(false);

        $filterChain = $this->createFilterChainMock([[$valueRaw, $valueFiltered]]);

        $this->input->setFilterChain($filterChain);
        $this->input->setValue($valueRaw);

        $this->assertSame($valueFiltered, $this->input->getValue());
    }

    public function testCanRetrieveRawValue()
    {
        $valueRaw = $this->getDummyValue();

        $filterChain = $this->createFilterChainMock();

        $this->input->setFilterChain($filterChain);
        $this->input->setValue($valueRaw);

        $this->assertEquals($valueRaw, $this->input->getRawValue());
    }

    public function testValidationOperatesOnFilteredValue()
    {
        $valueRaw = $this->getDummyValue();
        $valueFiltered = $this->getDummyValue(false);

        $filterChain = $this->createFilterChainMock([[$valueRaw, $valueFiltered]]);

        $validatorChain = $this->createValidatorChainMock([[$valueFiltered, null, true]]);

        $this->input->setFilterChain($filterChain);
        $this->input->setValidatorChain($validatorChain);
        $this->input->setValue($valueRaw);

        $this->assertTrue(
            $this->input->isValid(),
            'isValid() value not match. Detail . ' . json_encode($this->input->getMessages())
        );
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

    /**
     * @group 7448
     * @dataProvider isRequiredVsIsValidProvider
     */
    public function testIsRequiredVsIsValid(
        $required,
        $validator,
        $expectedIsValid,
        $expectedMessages
    ) {
        $value = $this->getDummyValue();
        $this->input->setRequired($required);
        $this->input->setValidatorChain($validator);
        $this->input->setValue($value);

        $this->assertEquals(
            $expectedIsValid,
            $this->input->isValid(),
            'isValid() value not match. Detail: ' . json_encode($this->input->getMessages())
        );
        $this->assertEquals($expectedMessages, $this->input->getMessages(), 'getMessages() value not match');
        $this->assertEquals($value, $this->input->getRawValue(), 'getRawValue() must return the value always');
        $this->assertEquals($value, $this->input->getValue(), 'getValue() must return the filtered value always');
    }

    public function testSetValuePutInputInTheDesiredState()
    {
        $input = $this->input;
        $this->assertFalse($input->hasValue(), 'Input should not have value by default');

        $input->setValue($this->getDummyValue());
        $this->assertTrue($input->hasValue(), "hasValue() didn't return true when value was set");
    }

    public function testResetValueReturnsInputValueToDefaultValue()
    {
        $input = $this->input;
        $originalInput = clone $input;
        $this->assertFalse($input->hasValue(), 'Input should not have value by default');

        $input->setValue($this->getDummyValue());
        $this->assertTrue($input->hasValue(), "hasValue() didn't return true when value was set");

        $return = $input->resetValue();
        $this->assertSame($input, $return, 'resetValue() must return itself');
        $this->assertEquals($originalInput, $input, 'Input was not reset to the default value state');
    }

    public function testMerge()
    {
        $sourceRawValue = $this->getDummyValue();

        $source = $this->createInputInterfaceMock();
        $source->method('getName')->willReturn('bazInput');
        $source->method('getErrorMessage')->willReturn('bazErrorMessage');
        $source->method('breakOnFailure')->willReturn(true);
        $source->method('isRequired')->willReturn(true);
        $source->method('getRawValue')->willReturn($sourceRawValue);
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

        $target = $this->input;
        $target->setName('fooInput');
        $target->setErrorMessage('fooErrorMessage');
        $target->setBreakOnFailure(false);
        $target->setRequired(false);
        $target->setFilterChain($targetFilterChain);
        $target->setValidatorChain($targetValidatorChain);

        $return = $target->merge($source);
        $this->assertSame($target, $return, 'merge() must return it self');

        $this->assertEquals('bazInput', $target->getName(), 'getName() value not match');
        $this->assertEquals('bazErrorMessage', $target->getErrorMessage(), 'getErrorMessage() value not match');
        $this->assertEquals(true, $target->breakOnFailure(), 'breakOnFailure() value not match');
        $this->assertEquals(true, $target->isRequired(), 'isRequired() value not match');
        $this->assertEquals($sourceRawValue, $target->getRawValue(), 'getRawValue() value not match');
        $this->assertTrue($target->hasValue(), 'hasValue() value not match');
    }

    /**
     * Specific Input::merge extras
     */
    public function testInputMergeWithoutValues()
    {
        $source = new Input();
        $this->assertFalse($source->hasValue(), 'Source should not have a value');

        $target = $this->input;
        $this->assertFalse($target->hasValue(), 'Target should not have a value');

        $return = $target->merge($source);
        $this->assertSame($target, $return, 'merge() must return it self');

        $this->assertFalse($target->hasValue(), 'hasValue() value not match');
    }

    /**
     * Specific Input::merge extras
     */
    public function testInputMergeWithSourceValue()
    {
        $source = new Input();
        $source->setValue(['foo']);

        $target = $this->input;
        $this->assertFalse($target->hasValue(), 'Target should not have a value');

        $return = $target->merge($source);
        $this->assertSame($target, $return, 'merge() must return it self');

        $this->assertEquals(['foo'], $target->getRawValue(), 'getRawValue() value not match');
        $this->assertTrue($target->hasValue(), 'hasValue() value not match');
    }

    /**
     * Specific Input::merge extras
     */
    public function testInputMergeWithTargetValue()
    {
        $source = new Input();
        $this->assertFalse($source->hasValue(), 'Source should not have a value');

        $target = $this->input;
        $target->setValue(['foo']);

        $return = $target->merge($source);
        $this->assertSame($target, $return, 'merge() must return it self');

        $this->assertEquals(['foo'], $target->getRawValue(), 'getRawValue() value not match');
        $this->assertTrue($target->hasValue(), 'hasValue() value not match');
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

    public function isRequiredVsIsValidProvider()
    {
        $isRequired = true;
        $isValid = true;

        $validatorMsg = ['FooValidator' => 'Invalid Value'];

        $validatorInvalid = function ($value, $context = null) use ($validatorMsg) {
            return $this->createValidatorChainMock([[$value, $context, false]], $validatorMsg);
        };
        $validatorValid = function ($value, $context = null) {
            return $this->createValidatorChainMock([[$value, $context, true]]);
        };

        // @codingStandardsIgnoreStart
        $dataSets=[
            // Description => [$isRequired, $validator, $expectedIsValid, $expectedMessages]
            'Required: T; Validator: T' => [ $isRequired, $validatorValid  ,  $isValid, []],
            'Required: T; Validator: F' => [ $isRequired, $validatorInvalid, !$isValid, $validatorMsg],

            'Required: F; Validator: T' => [!$isRequired, $validatorValid  ,  $isValid, []],
            'Required: F; Validator: F' => [!$isRequired, $validatorInvalid, !$isValid, $validatorMsg],
        ];
        // @codingStandardsIgnoreEnd

        foreach ($dataSets as &$dataSet) {
            $dataSet[1] = $dataSet[1]($this->getDummyValue()); // Get validator mock for each data set
        }

        return $dataSets;
    }

    /**
     * @return InputInterface|MockObject
     */
    protected function createInputInterfaceMock()
    {
        /** @var InputInterface|MockObject $source */
        $source = $this->getMock(InputInterface::class);

        return $source;
    }

    /**
     * @param array $valueMap
     *
     * @return FilterChain|MockObject
     */
    protected function createFilterChainMock(array $valueMap = [])
    {
        /** @var FilterChain|MockObject $filterChain */
        $filterChain = $this->getMock(FilterChain::class);

        $filterChain->method('filter')
            ->willReturnMap($valueMap)
        ;

        return $filterChain;
    }

    /**
     * @param array $valueMap
     * @param string[] $messages
     *
     * @return ValidatorChain|MockObject
     */
    protected function createValidatorChainMock(array $valueMap = [], $messages = [])
    {
        /** @var ValidatorChain|MockObject $validatorChain */
        $validatorChain = $this->getMock(ValidatorChain::class);

        if (empty($valueMap)) {
            $validatorChain->expects($this->never())
                ->method('isValid')
            ;
        } else {
            $validatorChain->expects($this->atLeastOnce())
                ->method('isValid')
                ->willReturnMap($valueMap)
            ;
        }

        $validatorChain->method('getMessages')
            ->willReturn($messages)
        ;

        return $validatorChain;
    }

    protected function getDummyValue($raw = true)
    {
        if ($raw) {
            return 'foo';
        } else {
            return 'filtered';
        }
    }
}
