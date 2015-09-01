<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\InputFilter;

use ArrayObject;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use PHPUnit_Framework_TestCase as TestCase;
use stdClass;
use Zend\Filter;
use Zend\InputFilter\ArrayInput;
use Zend\InputFilter\BaseInputFilter as InputFilter;
use Zend\InputFilter\Exception\InvalidArgumentException;
use Zend\InputFilter\Exception\RuntimeException;
use Zend\InputFilter\FileInput;
use Zend\InputFilter\Input;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\InputInterface;
use Zend\Validator;

/**
 * @covers Zend\InputFilter\BaseInputFilter
 */
class BaseInputFilterTest extends TestCase
{
    use InputFilterInterfaceTestTrait;
    use ReplaceableInputInterfaceTestTrait;
    use UnknownInputsCapableInterfaceTestTrait;

    public function testInputFilterIsEmptyByDefault()
    {
        $filter = $this->createDefaultInputFilter();
        $this->assertEquals(0, count($filter));
    }

    public function testAddWithInvalidInputTypeThrowsInvalidArgumentException()
    {
        $filter = new InputFilter();

        $this->setExpectedException(
            InvalidArgumentException::class,
            'expects an instance of Zend\InputFilter\InputInterface or Zend\InputFilter\InputFilterInterface '.
            'as its first argument; received "stdClass"'
        );
        /** @noinspection PhpParamsInspection */
        $filter->add(new stdClass());
    }

    public function testGetThrowExceptionIfInputDoesNotExists()
    {
        $inputFilter = $this->createDefaultInputFilter();

        $this->setExpectedException(
            InvalidArgumentException::class,
            'no input found matching "not exists"'
        );
        $inputFilter->get('not exists');
    }

    public function testReplaceWithInvalidInputTypeThrowsInvalidArgumentException()
    {
        $inputFilter = $this->createDefaultInputFilter();
        $inputFilter->add(new Input('foo'), 'replace_me');

        $this->setExpectedException(
            InvalidArgumentException::class,
            'expects an instance of Zend\InputFilter\InputInterface or Zend\InputFilter\InputFilterInterface '.
            'as its first argument; received "stdClass"'
        );
        /** @noinspection PhpParamsInspection */
        $inputFilter->replace(new stdClass(), 'replace_me');
    }

    public function testReplaceThrowExceptionIfInputToReplaceDoesNotExists()
    {
        $inputFilter = $this->createDefaultInputFilter();

        $this->setExpectedException(
            InvalidArgumentException::class,
            'no input found matching "not exists"'
        );
        $inputFilter->replace(new Input('foo'), 'not exists');
    }

    public function testGetValueThrowExceptionIfInputDoesNotExists()
    {
        $inputFilter = $this->createDefaultInputFilter();

        $this->setExpectedException(
            InvalidArgumentException::class,
            '"not exists" was not found in the filter'
        );
        $inputFilter->getValue('not exists');
    }

    public function testGetRawValueThrowExceptionIfInputDoesNotExists()
    {
        $inputFilter = $this->createDefaultInputFilter();

        $this->setExpectedException(
            InvalidArgumentException::class,
            '"not exists" was not found in the filter'
        );
        $inputFilter->getRawValue('not exists');
    }

    public function testSetDataWithInvalidDataTypeThrowsInvalidArgumentException()
    {
        $filter = $this->createDefaultInputFilter();

        $this->setExpectedException(
            InvalidArgumentException::class,
            'expects an array or Traversable argument; received stdClass'
        );
        /** @noinspection PhpParamsInspection */
        $filter->setData(new stdClass());
    }

    public function testIsValidThrowExceptionIfDataWasNotSetYet()
    {
        $filter = $this->createDefaultInputFilter();

        $this->setExpectedException(
            RuntimeException::class,
            'no data present to validate'
        );
        $filter->isValid();
    }

    public function testSetValidationGroupThrowExceptionIfInputIsNotAnInputFilter()
    {
        $filter = $this->getInputFilter();

        /** @var InputInterface|MockObject $nestedInput */
        $nestedInput = $this->getMock(InputInterface::class);
        $filter->add($nestedInput, 'fooInput');

        $this->setExpectedException(
            InvalidArgumentException::class,
            'Input "fooInput" must implement InputFilterInterface'
        );
        $filter->setValidationGroup(['fooInput' => 'foo']);
    }

    public function testSetValidationGroupThrowExceptionIfInputFilterNotExists()
    {
        $filter = $this->getInputFilter();

        $this->setExpectedException(
            InvalidArgumentException::class,
            'expects a list of valid input names; "anotherNotExistsInputFilter" was not found'
        );
        $filter->setValidationGroup(['notExistInputFilter' => 'anotherNotExistsInputFilter']);
    }

    public function testSetValidationGroupThrowExceptionIfInputFilterInArgumentListNotExists()
    {
        $filter = $this->getInputFilter();

        $this->setExpectedException(
            InvalidArgumentException::class,
            'expects a list of valid input names; "notExistInputFilter" was not found'
        );
        $filter->setValidationGroup('notExistInputFilter');
    }

    /**
     * @dataProvider inputProvider
     *
     * @param mixed $input
     * @param string $inputName Name used to retrieve this input.
     * @param mixed $expectedInput
     */
    public function testReplace($input, $inputName, $expectedInput)
    {
        $inputFilter = new InputFilter();
        $nameToReplace = 'replace_me';
        $inputToReplace = new Input($nameToReplace);

        $inputFilter->add($inputToReplace);
        $currentNumberOfFilters = count($inputFilter);

        $return = $inputFilter->replace($input, $nameToReplace);
        $this->assertSame($inputFilter, $return, 'BaseInputFilter::replace() must return it self');
        $this->assertCount($currentNumberOfFilters, $inputFilter, "Number of filters shouldn't change");

        $returnInput = $inputFilter->get($nameToReplace);
        $this->assertEquals($expectedInput, $returnInput, 'InputFilter::get() does not match the expected input');
    }

    public function getInputFilter()
    {
        $filter = $this->createDefaultInputFilter();

        $foo = new Input();
        $foo->getFilterChain()->attachByName('stringtrim')
                              ->attachByName('alpha');
        $foo->getValidatorChain()->attach(new Validator\StringLength(3, 6));

        $bar = new Input();
        $bar->getFilterChain()->attachByName('stringtrim');
        $bar->getValidatorChain()->attach(new Validator\Digits());

        $baz = new Input();
        $baz->setRequired(false);
        $baz->getFilterChain()->attachByName('stringtrim');
        $baz->getValidatorChain()->attach(new Validator\StringLength(1, 6));

        $qux = new Input();
        $qux->setAllowEmpty(true);
        $qux->getFilterChain()->attachByName('stringtrim');
        $qux->getValidatorChain()->attach(new Validator\StringLength(5, 6));

        $filter->add($foo, 'foo')
               ->add($bar, 'bar')
               ->add($baz, 'baz')
               ->add($qux, 'qux')
               ->add($this->getChildInputFilter(), 'nest');

        return $filter;
    }

    public function getChildInputFilter()
    {
        $filter = $this->createDefaultInputFilter();

        $foo = new Input();
        $foo->getFilterChain()->attachByName('stringtrim')
                              ->attachByName('alpha');
        $foo->getValidatorChain()->attach(new Validator\StringLength(3, 6));

        $bar = new Input();
        $bar->getFilterChain()->attachByName('stringtrim');
        $bar->getValidatorChain()->attach(new Validator\Digits());

        $baz = new Input();
        $baz->setRequired(false);
        $baz->getFilterChain()->attachByName('stringtrim');
        $baz->getValidatorChain()->attach(new Validator\StringLength(1, 6));

        $filter->add($foo, 'foo')
               ->add($bar, 'bar')
               ->add($baz, 'baz');
        return $filter;
    }

    public function dataSets()
    {
        return [
            'valid-with-empty-and-null' => [
                [
                    'foo' => ' bazbat ',
                    'bar' => '12345',
                    'baz' => null,
                    'qux' => '',
                    'nest' => [
                        'foo' => ' bazbat ',
                        'bar' => '12345',
                        'baz' => null,
                    ],
                ],
                true,
            ],
            'valid-with-empty' => [
                [
                    'foo' => ' bazbat ',
                    'bar' => '12345',
                    'qux' => '',
                    'nest' => [
                        'foo' => ' bazbat ',
                        'bar' => '12345',
                    ],
                ],
                true,
            ],
            'invalid-with-empty-and-missing' => [
                [
                    'foo' => ' bazbat ',
                    'bar' => '12345',
                    'baz' => 'thisistoolong',
                    'nest' => [
                        'foo' => ' bazbat ',
                        'bar' => '12345',
                        'baz' => 'thisistoolong',
                    ],
                ],
                false,
            ],
            'invalid-with-empty' => [
                [
                    'foo' => ' baz bat ',
                    'bar' => 'abc45',
                    'baz' => ' ',
                    'qux' => ' ',
                    'nest' => [
                        'foo' => ' baz bat ',
                        'bar' => '123ab',
                        'baz' => ' ',
                    ],
                ],
                false,
            ],
        ];
    }

    /**
     * @dataProvider dataSets
     * @group fmlife
     */
    public function testCanValidateEntireDataset($dataset, $expected)
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('ext/intl not enabled');
        }

        $filter = $this->getInputFilter();
        $filter->setData($dataset);
        $this->assertSame($expected, $filter->isValid());
    }

    public function testCanValidatePartialDataset()
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('ext/intl not enabled');
        }

        $filter = $this->getInputFilter();
        $validData = [
            'foo' => ' bazbat ',
            'bar' => '12345',
        ];
        $filter->setValidationGroup('foo', 'bar');
        $filter->setData($validData);
        $this->assertTrue($filter->isValid());

        $invalidData = [
            'bar' => 'abc45',
            'nest' => [
                'foo' => ' 123bat ',
                'bar' => '123ab',
            ],
        ];
        $filter->setValidationGroup('bar', 'nest');
        $filter->setData($invalidData);
        $this->assertFalse($filter->isValid());
    }

    public function testResetEmptyValidationGroupRecursively()
    {
        $data = [
            'flat' => 'foo',
            'deep' => [
                'deep-input1' => 'deep-foo1',
                'deep-input2' => 'deep-foo2',
            ]
        ];
        $filter = $this->createDefaultInputFilter();
        $filter->add(new Input, 'flat');
        $deepInputFilter = $this->createDefaultInputFilter();
        $deepInputFilter->add(new Input, 'deep-input1');
        $deepInputFilter->add(new Input, 'deep-input2');
        $filter->add($deepInputFilter, 'deep');
        $filter->setData($data);
        $filter->setValidationGroup(['deep' => 'deep-input1']);
        // reset validation group
        $filter->setValidationGroup(InputFilter::VALIDATE_ALL);
        $this->assertEquals($data, $filter->getValues());
    }

    public function testCanRetrieveInvalidInputsOnFailedValidation()
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('ext/intl not enabled');
        }

        $filter = $this->getInputFilter();
        $invalidData = [
            'foo' => ' bazbat ',
            'bar' => 'abc45',
            'nest' => [
                'foo' => ' baz bat boo ',
                'bar' => '12345',
            ],
        ];
        $filter->setData($invalidData);
        $this->assertFalse($filter->isValid());
        $invalidInputs = $filter->getInvalidInput();
        $this->assertArrayNotHasKey('foo', $invalidInputs);
        $this->assertArrayHasKey('bar', $invalidInputs);
        $this->assertInstanceOf(Input::class, $invalidInputs['bar']);
        $this->assertArrayHasKey('nest', $invalidInputs/*, var_export($invalidInputs, 1)*/);
        $this->assertInstanceOf(InputFilterInterface::class, $invalidInputs['nest']);
        $nestInvalids = $invalidInputs['nest']->getInvalidInput();
        $this->assertArrayHasKey('foo', $nestInvalids);
        $this->assertInstanceOf(Input::class, $nestInvalids['foo']);
        $this->assertArrayNotHasKey('bar', $nestInvalids);
    }

    public function testCanRetrieveValidInputsOnFailedValidation()
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('ext/intl not enabled');
        }

        $filter = $this->getInputFilter();
        $invalidData = [
            'foo' => ' bazbat ',
            'bar' => 'abc45',
            'nest' => [
                'foo' => ' baz bat ',
                'bar' => '12345',
            ],
        ];
        $filter->setData($invalidData);
        $this->assertFalse($filter->isValid());
        $validInputs = $filter->getValidInput();
        $this->assertArrayHasKey('foo', $validInputs);
        $this->assertInstanceOf(Input::class, $validInputs['foo']);
        $this->assertArrayNotHasKey('bar', $validInputs);
        $this->assertArrayHasKey('nest', $validInputs);
        $this->assertInstanceOf(InputFilterInterface::class, $validInputs['nest']);
        $nestValids = $validInputs['nest']->getValidInput();
        $this->assertArrayHasKey('foo', $nestValids);
        $this->assertInstanceOf(Input::class, $nestValids['foo']);
        $this->assertArrayHasKey('bar', $nestValids);
        $this->assertInstanceOf(Input::class, $nestValids['bar']);
    }

    public function testValuesRetrievedAreFiltered()
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('ext/intl not enabled');
        }

        $filter = $this->getInputFilter();
        $validData = [
            'foo' => ' bazbat ',
            'bar' => '12345',
            'qux' => '',
            'nest' => [
                'foo' => ' bazbat ',
                'bar' => '12345',
            ],
        ];
        $filter->setData($validData);
        $this->assertTrue($filter->isValid());
        $expected = [
            'foo' => 'bazbat',
            'bar' => '12345',
            'baz' => null,
            'qux' => '',
            'nest' => [
                'foo' => 'bazbat',
                'bar' => '12345',
                'baz' => null,
            ],
        ];
        $this->assertEquals($expected, $filter->getValues());
    }

    public function testCanGetRawInputValues()
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('ext/intl not enabled');
        }

        $filter = $this->getInputFilter();
        $validData = [
            'foo' => ' bazbat ',
            'bar' => '12345',
            'baz' => null,
            'qux' => '',
            'nest' => [
                'foo' => ' bazbat ',
                'bar' => '12345',
                'baz' => null,
            ],
        ];
        $filter->setData($validData);
        $this->assertTrue($filter->isValid());
        $this->assertEquals($validData, $filter->getRawValues());
    }

    public function testCanGetValidationMessages()
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('ext/intl not enabled');
        }

        $filter = $this->getInputFilter();
        $filter->get('baz')->setRequired(true);
        $filter->get('nest')->get('baz')->setRequired(true);
        $invalidData = [
            'foo' => ' bazbat boo ',
            'bar' => 'abc45',
            'baz' => '',
            'nest' => [
                'foo' => ' baz bat boo ',
                'bar' => '123yz',
                'baz' => '',
            ],
        ];
        $filter->setData($invalidData);
        $this->assertFalse($filter->isValid());
        $messages = $filter->getMessages();
        foreach ($invalidData as $key => $value) {
            $this->assertArrayHasKey($key, $messages);
            $currentMessages = $messages[$key];
            switch ($key) {
                case 'foo':
                    $this->assertArrayHasKey(Validator\StringLength::TOO_LONG, $currentMessages);
                    break;
                case 'bar':
                    $this->assertArrayHasKey(Validator\Digits::NOT_DIGITS, $currentMessages);
                    break;
                case 'baz':
                    $this->assertArrayHasKey(Validator\NotEmpty::IS_EMPTY, $currentMessages);
                    break;
                case 'nest':
                    foreach ($value as $k => $v) {
                        $this->assertArrayHasKey($k, $messages[$key]);
                        $currentMessages = $messages[$key][$k];
                        switch ($k) {
                            case 'foo':
                                $this->assertArrayHasKey(Validator\StringLength::TOO_LONG, $currentMessages);
                                break;
                            case 'bar':
                                $this->assertArrayHasKey(Validator\Digits::NOT_DIGITS, $currentMessages);
                                break;
                            case 'baz':
                                $this->assertArrayHasKey(Validator\NotEmpty::IS_EMPTY, $currentMessages);
                                break;
                            default:
                                $this->fail(sprintf('Invalid key "%s" encountered in messages array', $k));
                        }
                    }
                    break;
                default:
                    $this->fail(sprintf('Invalid key "%s" encountered in messages array', $k));
            }
        }
    }

    /**
     * Idea for this one is that one input may only need to be validated if another input is present.
     *
     * Commenting out for now, as validation context may make this irrelevant, and unsure what API to expose.
    public function testCanConditionallyInvokeValidators()
    {
        $this->markTestIncomplete();
    }
     */

    /**
     * Idea for this one is that validation may need to rely on context -- e.g., a "password confirmation"
     * field may need to know what the original password entered was in order to compare.
     */
    public function testValidationCanUseContext()
    {
        $filter = $this->createDefaultInputFilter();

        $store = new stdClass;
        $foo   = new Input();
        $foo->getValidatorChain()->attach(new Validator\Callback(function ($value, $context) use ($store) {
            $store->value   = $value;
            $store->context = $context;
            return true;
        }));

        $bar = new Input();
        $bar->getValidatorChain()->attach(new Validator\Digits());

        $filter->add($foo, 'foo')
               ->add($bar, 'bar');

        $data = ['foo' => 'foo', 'bar' => 123];
        $filter->setData($data);

        $this->assertTrue($filter->isValid());
        $this->assertEquals('foo', $store->value);
        $this->assertEquals($data, $store->context);
    }

    /**
     * Idea here is that you can indicate that if validation for a particular input fails, we should not
     * attempt any further validation of any other inputs.
     */
    public function testInputBreakOnFailureFlagIsHonoredWhenValidating()
    {
        $filter = $this->createDefaultInputFilter();

        $store = new stdClass;
        $foo   = new Input();
        $foo->getValidatorChain()->attach(new Validator\Callback(function ($value, $context) use ($store) {
            $store->value   = $value;
            $store->context = $context;
            return true;
        }));

        $bar = new Input();
        $bar->getValidatorChain()->attach(new Validator\Digits());
        $bar->setBreakOnFailure(true);

        $filter->add($bar, 'bar')  // adding bar first, as we want it to validate first and break the chain
               ->add($foo, 'foo');

        $data = ['bar' => 'bar', 'foo' => 'foo'];
        $filter->setData($data);

        $this->assertFalse($filter->isValid());
        $this->assertObjectNotHasAttribute('value', $store);
        $this->assertObjectNotHasAttribute('context', $store);
    }

    public function testValidationSkipsFieldsMarkedNotRequiredWhenNoDataPresent()
    {
        $filter = $this->createDefaultInputFilter();

        $optionalInputName = 'fooOptionalInput';
        /** @var InputInterface|MockObject $optionalInput */
        $optionalInput = $this->getMock(InputInterface::class);
        $optionalInput->method('getName')
            ->willReturn($optionalInputName)
        ;
        $optionalInput->expects($this->never())
            ->method('isValid')
        ;
        $data = [];

        $filter->add($optionalInput);

        $filter->setData($data);

        $this->assertTrue($filter->isValid(), json_encode($filter->getMessages()));
        $this->assertArrayNotHasKey(
            $optionalInputName,
            $filter->getValidInput(),
            'Missing optional fields must not appear as valid input neither invalid input'
        );
        $this->assertArrayNotHasKey(
            $optionalInputName,
            $filter->getInvalidInput(),
            'Missing optional fields must not appear as valid input neither invalid input'
        );
    }

    public function testValidationSkipsFileInputsMarkedNotRequiredWhenNoFileDataIsPresent()
    {
        $filter = $this->createDefaultInputFilter();

        $foo   = new FileInput();
        $foo->getValidatorChain()->attach(new Validator\File\UploadFile());
        $foo->setRequired(false);

        $filter->add($foo, 'foo');

        $data = [
            'foo' => [
                'tmp_name' => '/tmp/barfile',
                'name'     => 'barfile',
                'type'     => 'text',
                'size'     => 0,
                'error'    => 4,  // UPLOAD_ERR_NO_FILE
            ]
        ];
        $filter->setData($data);
        $this->assertTrue($filter->isValid());

        // Negative test
        $foo->setRequired(true);
        $filter->setData($data);
        $this->assertFalse($filter->isValid());
    }

    public function testValidationSkipsFileInputsMarkedNotRequiredWhenNoMultiFileDataIsPresent()
    {
        $filter = $this->createDefaultInputFilter();
        $foo    = new FileInput();
        $foo->setRequired(false);
        $filter->add($foo, 'foo');

        $data = [
            'foo' => [[
                'tmp_name' => '/tmp/barfile',
                'name'     => 'barfile',
                'type'     => 'text',
                'size'     => 0,
                'error'    => 4,  // UPLOAD_ERR_NO_FILE
            ]],
        ];
        $filter->setData($data);
        $this->assertTrue($filter->isValid());

        // Negative test
        $foo->setRequired(true);
        $filter->setData($data);
        $this->assertFalse($filter->isValid());
    }

    public function testValidationAllowsEmptyValuesToRequiredInputWhenAllowEmptyFlagIsTrue()
    {
        $filter = $this->createDefaultInputFilter();

        $foo   = new Input('foo');
        $foo->getValidatorChain()->attach(new Validator\StringLength(3, 5));
        $foo->setRequired(true);
        $foo->setAllowEmpty(true);

        $bar = new Input();
        $bar->getValidatorChain()->attach(new Validator\Digits());
        $bar->setRequired(true);

        $filter->add($foo, '')
               ->add($bar, 'bar');

        $data = [
            'bar' => 124,
            'foo' => '',
        ];

        $filter->setData($data);

        $this->assertTrue($filter->isValid());
        $this->assertEquals('', $filter->getValue('foo'));
    }

    public function testValidationMarksInputInvalidWhenRequiredAndAllowEmptyFlagIsFalse()
    {
        $filter = $this->createDefaultInputFilter();

        $foo   = new Input();
        $foo->getValidatorChain()->attach(new Validator\StringLength(3, 5));
        $foo->setRequired(true);
        $foo->setAllowEmpty(false);

        $bar = new Input();
        $bar->getValidatorChain()->attach(new Validator\Digits());
        $bar->setRequired(true);

        $filter->add($foo, '')
               ->add($bar, 'bar');

        $data = ['bar' => 124];
        $filter->setData($data);

        $this->assertFalse($filter->isValid());
    }

    public static function contextDataProvider()
    {
        return [
            ['', 'y', true],
            ['', 'n', false],
        ];
    }

    /**
     * Idea here is that an empty field may or may not be valid based on
     * context.
     */
    /**
     * @dataProvider contextDataProvider()
     */
    // @codingStandardsIgnoreStart
    public function testValidationMarksInputValidWhenAllowEmptyFlagIsTrueAndContinueIfEmptyIsTrueAndContextValidatesEmptyField($allowEmpty, $blankIsValid, $valid)
    {
        // @codingStandardsIgnoreEnd
        $filter = $this->createDefaultInputFilter();

        $data = [
            'allowEmpty' => $allowEmpty,
            'blankIsValid' => $blankIsValid,
        ];

        $allowEmpty = new Input();
        $allowEmpty->setAllowEmpty(true)
                   ->setContinueIfEmpty(true);

        $blankIsValid = new Input();
        $blankIsValid->getValidatorChain()->attach(new Validator\Callback(function ($value, $context) {
            return ('y' === $value && empty($context['allowEmpty']));
        }));

        $filter->add($allowEmpty, 'allowEmpty')
               ->add($blankIsValid, 'blankIsValid');
        $filter->setData($data);

        $this->assertSame($valid, $filter->isValid());
    }

    public function testCanRetrieveRawValuesIndividuallyWithoutValidating()
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('ext/intl not enabled');
        }

        $filter = $this->getInputFilter();
        $data = [
            'foo' => ' bazbat ',
            'bar' => '12345',
            'nest' => [
                'foo' => ' bazbat ',
                'bar' => '12345',
            ],
        ];
        $filter->setData($data);
        $test = $filter->getRawValue('foo');
        $this->assertSame($data['foo'], $test);
    }

    public function testCanRetrieveUnvalidatedButFilteredInputValue()
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('ext/intl not enabled');
        }

        $filter = $this->getInputFilter();
        $data = [
            'foo' => ' baz 2 bat ',
            'bar' => '12345',
            'nest' => [
                'foo' => ' bazbat ',
                'bar' => '12345',
            ],
        ];
        $filter->setData($data);
        $test = $filter->getValue('foo');
        $this->assertSame('bazbat', $test);
    }

    public function testGetRequiredNotEmptyValidationMessages()
    {
        $filter = $this->createDefaultInputFilter();

        $foo   = new Input();
        $foo->setRequired(true);
        $foo->setAllowEmpty(false);

        $filter->add($foo, 'foo');

        $data = ['foo' => null];
        $filter->setData($data);

        $this->assertFalse($filter->isValid());
        $messages = $filter->getMessages();
        $this->assertArrayHasKey('foo', $messages);
        $this->assertNotEmpty($messages['foo']);
    }

    public function testHasUnknown()
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('ext/intl not enabled');
        }

        $filter = $this->getInputFilter();
        $validData = [
            'foo' => ' bazbat ',
            'bar' => '12345',
            'baz' => ''
        ];
        $filter->setData($validData);
        $this->assertFalse($filter->hasUnknown());

        $filter = $this->getInputFilter();
        $invalidData = [
            'bar' => '12345',
            'baz' => '',
            'gru' => '',
        ];
        $filter->setData($invalidData);
        $this->assertTrue($filter->hasUnknown());
    }
    public function testGetUknown()
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('ext/intl not enabled');
        }

        $filter = $this->getInputFilter();
        $unknown = [
            'bar' => '12345',
            'baz' => '',
            'gru' => 10,
            'test' => 'ok',
        ];
        $filter->setData($unknown);
        $unknown = $filter->getUnknown();
        $this->assertEquals(2, count($unknown));
        $this->assertArrayHasKey('gru', $unknown);
        $this->assertEquals(10, $unknown['gru']);
        $this->assertArrayHasKey('test', $unknown);
        $this->assertEquals('ok', $unknown['test']);

        $filter = $this->getInputFilter();
        $validData = [
            'foo' => ' bazbat ',
            'bar' => '12345',
            'baz' => ''
        ];
        $filter->setData($validData);
        $unknown = $filter->getUnknown();
        $this->assertEquals(0, count($unknown));
    }

    public function testValidateUseExplodeAndInstanceOf()
    {
        $filter = $this->createDefaultInputFilter();

        $input = new Input();
        $input->setRequired(true);

        $input->getValidatorChain()->attach(
            new Validator\Explode(
                [
                    'validator' => new Validator\IsInstanceOf(
                        [
                            'className' => Input::class
                        ]
                    )
                ]
            )
        );

        $filter->add($input, 'example');

        $data = [
            'example' => [
                $input
            ]
        ];

        $filter->setData($data);
        $this->assertTrue($filter->isValid());
    }

    public function testGetInputs()
    {
        $filter = $this->createDefaultInputFilter();

        $foo = new Input('foo');
        $bar = new Input('bar');

        $filter->add($foo);
        $filter->add($bar);

        $filters = $filter->getInputs();

        $this->assertCount(2, $filters);
        $this->assertEquals('foo', $filters['foo']->getName());
        $this->assertEquals('bar', $filters['bar']->getName());
    }

    /**
     * @group 4996
     */
    public function testAddingExistingInputWillMergeIntoExisting()
    {
        $filter = $this->createDefaultInputFilter();

        $foo1    = new Input('foo');
        $foo1->setRequired(true);
        $filter->add($foo1);

        $foo2    = new Input('foo');
        $foo2->setRequired(false);
        $filter->add($foo2);

        $this->assertFalse($filter->get('foo')->isRequired());
    }

    /**
     * @group 5270
     * @requires extension intl
     */
    public function testIsValidWhenValuesSetOnFilters()
    {
        $filter = $this->createDefaultInputFilter();

        $foo = new Input();
        $foo->getFilterChain()->attachByName('stringtrim')
                              ->attachByName('alpha');
        $foo->getValidatorChain()->attach(new Validator\StringLength(15, 18));

        $filter->add($foo, 'foo');

        //test valid with setData
        $filter->setData(['foo' => 'invalid']);
        $this->assertFalse($filter->isValid());

        //test invalid with setData
        $filter->setData(['foo' => 'thisisavalidstring']);
        $this->assertTrue($filter->isValid());

        //test invalid when setting data on actual filter
        $filter->get('foo')->setValue('invalid');
        $this->assertFalse($filter->get('foo')->isValid(), 'Filtered value is valid, should be invalid');
        $this->assertFalse($filter->isValid(), 'Input filter did not return value from filter');

        //test valid when setting data on actual filter
        $filter->get('foo')->setValue('thisisavalidstring');
        $this->assertTrue($filter->get('foo')->isValid(), 'Filtered value is not valid');
        $this->assertTrue($filter->isValid(), 'Input filter did return value from filter');
    }

    /**
     * @group 5638
     */
    public function testPopulateSupportsArrayInputEvenIfDataMissing()
    {
        /** @var ArrayInput|MockObject $arrayInput */
        $arrayInput = $this->getMock(ArrayInput::class);
        $arrayInput
            ->expects($this->once())
            ->method('setValue')
            ->with([]);

        $filter = $this->createDefaultInputFilter();
        $filter->add($arrayInput, 'arrayInput');
        $filter->setData(['foo' => 'bar']);
    }

    /**
     * @group 6431
     */
    public function testMerge()
    {
        $inputFilter       = $this->createDefaultInputFilter();
        $originInputFilter = $this->createDefaultInputFilter();

        $inputFilter->add(new Input(), 'foo');
        $inputFilter->add(new Input(), 'bar');

        $originInputFilter->add(new Input(), 'baz');

        $inputFilter->merge($originInputFilter);

        $this->assertEquals(
            [
                'foo',
                'bar',
                'baz'
            ],
            array_keys($inputFilter->getInputs())
        );
    }

    public function testAllowEmptyTestsFilteredValueAndOverrulesValidatorChain()
    {
        $input = new Input('foo');
        $input->setAllowEmpty(true);
        $input->setContinueIfEmpty(false);
        // Filter chain produces empty value which should be evaluated instead of raw value
        $input->getFilterChain()->attach(new Filter\Callback(function () {
            return '';
        }));
        // Validator chain says "not valid", but should not be invoked at all
        $input->getValidatorChain()->attach(new Validator\Callback(function () {
            return false;
        }));

        $filter = $this->createDefaultInputFilter();
        $filter->add($input)
               ->setData(['foo' => 'nonempty']);

        $this->assertTrue($filter->isValid());
        $this->assertEquals(['foo' => ''], $filter->getValues());
    }

    public function testAllowEmptyTestsFilteredValueAndContinuesIfEmpty()
    {
        $input = new Input('foo');
        $input->setAllowEmpty(true);
        $input->setContinueIfEmpty(true);
        // Filter chain produces empty value which should be evaluated instead of raw value
        $input->getFilterChain()->attach(new Filter\Callback(function () {
            return '';
        }));
        // Validator chain says "not valid", explicitly requested despite empty input
        $input->getValidatorChain()->attach(new Validator\Callback(function () {
            return false;
        }));

        $filter = $this->createDefaultInputFilter();
        $filter->add($input)
               ->setData(['foo' => 'nonempty']);

        $this->assertFalse($filter->isValid());
    }

    /**
     * @group 7
     */
    public function testMissingRequiredAllowedEmptyValueShouldMarkInputFilterInvalid()
    {
        $foo = new Input('foo');
        $foo->setRequired(true);
        $foo->setAllowEmpty(false);

        $bar = new Input('bar');
        $bar->setRequired(true);
        $bar->setAllowEmpty(true);

        $filter = $this->createDefaultInputFilter();
        $filter->add($foo);
        $filter->add($bar);

        $filter->setData(['foo' => 'xyz']);
        $this->assertFalse($filter->isValid(), 'Missing required value should mark input filter as invalid');
    }

    public function emptyValuesForValidation()
    {
        return [
            'null'         => [null],
            'empty-string' => [''],
        ];
    }

    /**
     * @group 7
     * @dataProvider emptyValuesForValidation
     */
    public function testEmptyValuePassedForRequiredButAllowedEmptyInputShouldMarkInputFilterValid($value)
    {
        $foo = new Input('foo');
        $foo->setRequired(true);
        $foo->setAllowEmpty(false);

        $bar = new Input('bar');
        $bar->setRequired(true);
        $bar->setAllowEmpty(true);

        $filter = $this->createDefaultInputFilter();
        $filter->add($foo);
        $filter->add($bar);

        $filter->setData([
            'foo' => 'xyz',
            'bar' => $value,
        ]);
        $this->assertTrue($filter->isValid(), 'Empty value should mark input filter as valid');
    }

    /**
     * @group 10
     */
    public function testMissingRequiredWithFallbackShouldMarkInputValid()
    {
        $foo = new Input('foo');
        $foo->setRequired(true);
        $foo->setAllowEmpty(false);

        $bar = new Input('bar');
        $bar->setRequired(true);
        $bar->setFallbackValue('baz');

        $filter = $this->createDefaultInputFilter();
        $filter->add($foo);
        $filter->add($bar);

        $filter->setData(['foo' => 'xyz']);
        $this->assertTrue($filter->isValid(), 'Missing input with fallback value should mark input filter as valid');
        $data = $filter->getValues();
        $this->assertArrayHasKey('bar', $data);
        $this->assertEquals($bar->getFallbackValue(), $data['bar']);
    }

    /**
     * @group 10
     */
    public function testMissingRequiredThatAllowsEmptyWithFallbackShouldMarkInputValid()
    {
        $foo = new Input('foo');
        $foo->setRequired(true);
        $foo->setAllowEmpty(false);

        $bar = new Input('bar');
        $bar->setRequired(true);
        $bar->setAllowEmpty(true);
        $bar->setFallbackValue('baz');

        $filter = $this->createDefaultInputFilter();
        $filter->add($foo);
        $filter->add($bar);

        $filter->setData(['foo' => 'xyz']);
        $this->assertTrue($filter->isValid(), 'Missing input with fallback value should mark input filter as valid');
        $data = $filter->getValues();
        $this->assertArrayHasKey('bar', $data);
        $this->assertEquals($bar->getFallbackValue(), $data['bar']);
        $this->assertArrayNotHasKey('bar', $filter->getValidInput());
        $this->assertArrayNotHasKey('bar', $filter->getInvalidInput());
    }

    /**
     * @group 10
     */
    public function testEmptyRequiredValueWithFallbackShouldMarkInputValid()
    {
        $foo = new Input('foo');
        $foo->setRequired(true);
        $foo->setAllowEmpty(false);

        $bar = new Input('bar');
        $bar->setRequired(true);
        $bar->setFallbackValue('baz');

        $filter = $this->createDefaultInputFilter();
        $filter->add($foo);
        $filter->add($bar);

        $filter->setData([
            'foo' => 'xyz',
            'bar' => null,
        ]);
        $this->assertTrue($filter->isValid(), 'Empty input with fallback value should mark input filter as valid');
        $data = $filter->getValues();
        $this->assertArrayHasKey('bar', $data);
        $this->assertEquals($bar->getFallbackValue(), $data['bar']);
        $this->assertArrayHasKey('bar', $filter->getValidInput());
        $this->assertArrayNotHasKey('bar', $filter->getInvalidInput());
    }

    /**
     * @group 15
     */
    public function testAllowsValidatingArrayAccessData()
    {
        $filter = $this->createDefaultInputFilter();
        $foo = new Input();
        $foo->getFilterChain()->attachByName('stringtrim')
                              ->attachByName('alpha');
        $foo->getValidatorChain()->attach(new Validator\StringLength(3, 6));
        $filter->add($foo, 'foo');

        $data = new ArrayObject(['foo' => ' valid ']);
        $filter->setData($data);
        $this->assertTrue($filter->isValid());
    }

    protected function createDefaultInputFilter()
    {
        $inputFilter = new InputFilter();

        return $inputFilter;
    }

    protected function createDefaultReplaceableInput()
    {
        return $this->createDefaultInputFilter();
    }

    protected function createDefaultUnknownInputsCapable()
    {
        return $this->createDefaultInputFilter();
    }
}
