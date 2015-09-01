<?php

namespace ZendTest\InputFilter;

use Maks3w\PhpUnitMethodsTrait\Framework\TestCaseTrait;
use PHPUnit_Framework_Assert as Assert;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use PHPUnit_Framework_TestCase as TestCase;
use stdClass;
use Zend\Filter\FilterChain;
use Zend\InputFilter\InputInterface;
use Zend\Validator\Callback as CallbackValidator;
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

    public function testSetFilterChain()
    {
        $input = $this->createDefaultInput();
        $filterChain = $this->createFilterChainMock();

        $return = $input->setFilterChain($filterChain);
        Assert::assertSame($input, $return, 'setFilterChain() must return it self');

        Assert::assertSame($filterChain, $input->getFilterChain(), 'getFilterChain() value not match');
    }

    public function testSetValidatorChain()
    {
        $input = $this->createDefaultInput();
        $validatorChain = $this->createValidatorChainMock();

        $return = $input->setValidatorChain($validatorChain);
        Assert::assertSame($input, $return, 'setValidatorChain() must return it self');

        Assert::assertSame($validatorChain, $input->getValidatorChain(), 'getValidatorChain() value not match');
    }

    public function setRequiredProvider()
    {
        return [
            // Description => [$isRequired]
            'Enable' => [true],
            'Disable' => [false],
        ];
    }

    /**
     * @dataProvider setRequiredProvider
     */
    public function testSetRequired($isRequired)
    {
        $input = $this->createDefaultInput();

        $return = $input->setRequired($isRequired);
        Assert::assertSame($input, $return, 'setRequired() must return it self');

        Assert::assertEquals($isRequired, $input->isRequired(), 'isRequired() value not match');
    }

    public function setAllowEmptyProvider()
    {
        return [
            // Description => [$allowEmpty]
            'Enable' => [true],
            'Disable' => [false],
        ];
    }

    /**
     * @dataProvider setAllowEmptyProvider
     */
    public function testSetAllowEmpty($allowEmpty)
    {
        $input = $this->createDefaultInput();

        $return = $input->setAllowEmpty($allowEmpty);
        Assert::assertSame($input, $return, 'setAllowEmpty() must return it self');

        Assert::assertEquals($allowEmpty, $input->allowEmpty(), 'allowEmpty() value not match');
    }

    public function setBreakOnFailureProvider()
    {
        return [
            // Description => [$breakOnFailure]
            'Enable' => [true],
            'Disable' => [false],
        ];
    }

    /**
     * @dataProvider setBreakOnFailureProvider
     */
    public function testSetBreakOnFailure($breakOnFailure)
    {
        $input = $this->createDefaultInput();

        $return = $input->setBreakOnFailure($breakOnFailure);
        Assert::assertSame($input, $return, 'setBreakOnFailure() must return it self');

        Assert::assertEquals($breakOnFailure, $input->breakOnFailure(), 'breakOnFailure() value not match');
    }

    public function testSetErrorMessage()
    {
        $input = $this->createDefaultInput();

        $return = $input->setErrorMessage('fooErrorMessage');
        Assert::assertSame($input, $return, 'setErrorMessage() must return it self');

        Assert::assertEquals('fooErrorMessage', $input->getErrorMessage(), 'getErrorMessage() value not match');
    }

    public function testSetName()
    {
        $input = $this->createDefaultInput();

        $return = $input->setName('fooName');
        Assert::assertSame($input, $return, 'setName() must return it self');

        Assert::assertEquals('fooName', $input->getName(), 'getName() value not match');
    }

    public function setValueProvider()
    {
        $emptyValues = $this->emptyValueProvider();
        $mixedValues = $this->mixedValueProvider();

        $values = array_merge($emptyValues, $mixedValues);

        return $values;
    }

    /**
     * @dataProvider setValueProvider
     */
    public function testSetValue($value)
    {
        $filterChain = $this->createFilterChainMock();
        $filterChain->expects(TestCase::atLeastOnce())
            ->method('filter')
            ->with($value)
            ->willReturn('*filter*')
        ;

        $input = $this->createDefaultInput();
        $input->setFilterChain($filterChain);

        $return = $input->setValue($value);
        Assert::assertSame($input, $return, 'setValue() must return it self');

        Assert::assertEquals($value, $input->getRawValue(), 'getRawValue() value not match');
        Assert::assertEquals('*filter*', $input->getValue(), 'getValue() value not match');
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

    public function allowEmptyVsIsRequiredVsIsValidProviderRules()
    {
        $allValues = $this->setValueProvider();
        $emptyValues = $this->emptyValueProvider();
        $notEmptyValues = array_diff_key($allValues, $emptyValues);

        $isEmpty = true;
        $isRequired = true;
        $isValid = true;
        $valid = true;

        $noMessages = [];
        $notEmptyMessages = ['isEmpty' => "Value is required and can't be empty"];
        $validatorMessages = ['callbackValue' => 'The input is not valid'];

        // @formatter:off
        $dataTemplates = [
            // Description => [$allowEmpty, $isRequired, $isValid, $valuesToTest, $expectedIsValid, $expectedMessages]
            'AEmpty: T, Required: T, ValidChain: T'                   => [$isEmpty,  $isRequired,  $isValid,  $allValues,      $valid,  $noMessages],
            'AEmpty: T, Required: T, ValidChain: F'                   => [$isEmpty,  $isRequired,  !$isValid, $allValues,      !$valid, $validatorMessages],

            'AEmpty: T, Required: F, ValidChain: T'                   => [$isEmpty,  !$isRequired, $isValid,  $allValues,      $valid,  $noMessages],
            'AEmpty: T, Required: F, ValidChain: F'                   => [$isEmpty,  !$isRequired, !$isValid, $allValues,      !$valid, $validatorMessages],

            'AEmpty: F, Required: T, ValidChain: T, Value: Empty'     => [!$isEmpty, $isRequired,  $isValid,  $emptyValues,    !$valid, $notEmptyMessages],
            'AEmpty: F, Required: T, ValidChain: T, Value: Not Empty' => [!$isEmpty, $isRequired,  $isValid,  $notEmptyValues, $valid,  $noMessages],
            'AEmpty: F, Required: T, ValidChain: F, Value: Empty'     => [!$isEmpty, $isRequired,  !$isValid, $emptyValues,    !$valid, $notEmptyMessages],
            'AEmpty: F, Required: T, ValidChain: F, Value: Not Empty' => [!$isEmpty, $isRequired,  !$isValid, $notEmptyValues, !$valid, $validatorMessages],

            'AEmpty: F, Required: F, ValidChain: T, Value: Empty'     => [!$isEmpty, !$isRequired, $isValid,  $emptyValues,    !$valid, $notEmptyMessages],
            'AEmpty: F, Required: F, ValidChain: T, Value: Not Empty' => [!$isEmpty, !$isRequired, $isValid,  $notEmptyValues, $valid,  $noMessages],
            'AEmpty: F, Required: F, ValidChain: F, Value: Empty'     => [!$isEmpty, !$isRequired, !$isValid, $emptyValues,    !$valid, $notEmptyMessages],
            'AEmpty: F, Required: F, ValidChain: F, Value: Not Empty' => [!$isEmpty, !$isRequired, !$isValid, $notEmptyValues, !$valid, $validatorMessages],
        ];
        // @formatter:on

        // Expand data template matrix for each possible input value.
        // Description => [$allowEmpty, $isRequired, $isValid, $value, $expectedIsValid, $expectedMessages]
        foreach ($dataTemplates as $dataTemplateDescription => $dataTemplate) {
            $temporalTemplate = $dataTemplate;
            foreach ($dataTemplate[3] as $valueDescription => $value) {
                $temporalTemplate[3] = current($value);
                yield $dataTemplateDescription . ' / ' . $valueDescription => $temporalTemplate;
            }
        }
    }

    /**
     * @dataProvider allowEmptyVsIsRequiredVsIsValidProviderRules
     */
    public function testAllowEmptyVsIsRequiredVsIsValidRules(
        $allowEmpty,
        $isRequired,
        $isValid,
        $value,
        $expectedIsValid,
        $expectedMessages
    ) {
        $input = $this->createDefaultInput();

        $input->setAllowEmpty($allowEmpty);
        $input->setRequired($isRequired);
        $input->setValidatorChain($this->createValidatorChainMock($isValid));
        $input->setValue($value);

        Assert::assertEquals(
            $expectedIsValid,
            $input->isValid(),
            'isValid() value not match. Detail: ' . json_encode($input->getMessages())
        );
        Assert::assertEquals($expectedMessages, $input->getMessages(), 'getMessages() value not match');
        Assert::assertEquals($value, $input->getRawValue(), 'getRawValue() must return the value always');
        Assert::assertEquals($value, $input->getValue(), 'getValue() must return the filtered value always');
    }

    public function emptyValueProvider()
    {
        return [
            // Description => [$value]
            'null' => [null],
            '""' => [''],
//            '"0"' => ['0'],
//            '0' => [0],
//            '0.0' => [0.0],
//            'false' => [false],
            '[]' => [[]],
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
            // @formatter:off
            'callable' => [function () {}],
            // @formatter:on
        ];
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
     * @param null|bool $isValid If set stub isValid method for return the argument value.
     *
     * @return MockObject|ValidatorChain
     */
    protected function createValidatorChainMock($isValid = null)
    {
        /** @var ValidatorChain|MockObject $validatorChain */
        $validatorChain = $this->getMockBuilder(ValidatorChain::class)
            ->enableProxyingToOriginalMethods()
            ->getMock()
        ;

        if ($isValid === false) {
            $validator = new CallbackValidator(
                function () use ($isValid) {
                    return $isValid;
                }
            );

            $validatorChain->attach($validator);
        }

        return $validatorChain;
    }
}
