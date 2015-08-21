<?php

namespace ZendTest\InputFilter;

use Maks3w\PhpUnitMethodsTrait\Framework\TestCaseTrait;
use PHPUnit_Framework_Assert as Assert;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Zend\InputFilter\Input;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\InputInterface;

/**
 * Compliance test methods for `Zend\InputFilter\InputFilterInterface` implementations.
 */
trait InputFilterInterfaceTestTrait
{
    use TestCaseTrait;

    public function testImplementsInputFilterInterface()
    {
        Assert::assertInstanceOf(InputFilterInterface::class, $this->createDefaultInputFilter());
    }

    /**
     * Verify the state of the input filter is the desired after change it using the method `add()`
     *
     * @dataProvider addMethodArgumentsProvider
     *
     * @param mixed $input
     * @param string $name
     * @param string $expectedInputName
     * @param mixed $expectedInput
     */
    public function testAddHasGet($input, $name, $expectedInputName, $expectedInput)
    {
        $inputFilter = $this->createDefaultInputFilter();
        Assert::assertFalse(
            $inputFilter->has($expectedInputName),
            "InputFilter shouldn't have an input with the name $expectedInputName yet"
        );
        $currentNumberOfFilters = count($inputFilter);

        $return = $inputFilter->add($input, $name);
        Assert::assertSame($inputFilter, $return, "InputFilter::add() must return it self");

        // **Check input collection state**
        Assert::assertTrue($inputFilter->has($expectedInputName), "There is no input with name $expectedInputName");
        Assert::assertCount($currentNumberOfFilters + 1, $inputFilter, 'Number of filters must be increased by 1');

        $returnInput = $inputFilter->get($expectedInputName);
        Assert::assertEquals($expectedInput, $returnInput, 'InputFilter::get() does not match the expected input');
    }

    public function testAddingInputWithNameDoesNotInjectNameInInput()
    {
        $inputFilter = $this->createDefaultInputFilter();

        $foo = new Input('foo');
        $inputFilter->add($foo, 'bar');

        $test = $inputFilter->get('bar');
        Assert::assertSame($foo, $test, 'InputFilter::get() does not match the input added');
        Assert::assertEquals('foo', $foo->getName(), 'Input name should not change');
    }

    /**
     * Verify the state of the input filter is the desired after change it using the method `add()` and `remove()`
     *
     * @dataProvider addMethodArgumentsProvider
     *
     * @param mixed $input
     * @param string $name
     * @param string $expectedInputName
     */
    public function testAddRemove($input, $name, $expectedInputName)
    {
        $inputFilter = $this->createDefaultInputFilter();

        $inputFilter->add($input, $name);
        $currentNumberOfFilters = count($inputFilter);

        $return = $inputFilter->remove($expectedInputName);
        Assert::assertSame($inputFilter, $return, 'InputFilter::remove() must return it self');

        Assert::assertFalse($inputFilter->has($expectedInputName), "There is no input with name $expectedInputName");
        Assert::assertCount($currentNumberOfFilters - 1, $inputFilter, 'Number of filters must be decreased by 1');
    }

    public function addMethodArgumentsProvider()
    {
        // Description => [$input argument, $name argument, $expectedName, $expectedInput]
        $tests = [];
        $inputTypes = $this->inputProvider();

        // Default $name argument (null)
        foreach ($inputTypes as $inputTypeDescription => $inputTypeData) {
            $description = $inputTypeDescription . ' - null';

            $tests[$description] = [$inputTypeData[0], null, $inputTypeData[1], $inputTypeData[2]];
        }

        // Custom $name argument
        foreach ($inputTypes as $inputTypeDescription => $inputTypeData) {
            static $customInputName = 'custom_name';

            $description = $inputTypeDescription . ' - ' . $customInputName;

            $tests[$description] = [$inputTypeData[0], $customInputName, $customInputName, $inputTypeData[2]];
        }

        return $tests;
    }

    public function inputProvider()
    {
        $input = $this->createInputInterfaceMock('inputFoo');
        $inputFilter = $this->createInputFilterInterfaceMock();

        return [
            // Description => [input, name, expected name, $expectedReturnInput]
            'InputInterface' => [$input, 'inputFoo', $input],
            'InputFilterInterface' => [$inputFilter, null, $inputFilter],
        ];
    }

    /**
     * @return InputFilterInterface
     */
    abstract protected function createDefaultInputFilter();

    /**
     * @return MockObject|InputFilterInterface
     */
    protected function createInputFilterInterfaceMock()
    {
        /** @var InputFilterInterface|MockObject $inputFilter */
        $inputFilter = $this->getMock(InputFilterInterface::class);

        return $inputFilter;
    }

    /**
     * @param null|string $name
     *
     * @return MockObject|InputInterface
     */
    protected function createInputInterfaceMock($name = null)
    {
        /** @var InputInterface|MockObject $input */
        $input = $this->getMock(InputInterface::class);

        $input->method('getName')
            ->willReturn($name)
        ;

        return $input;
    }
}
