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
use Zend\InputFilter\FileInput;
use Zend\Validator;

/**
 * @covers Zend\InputFilter\FileInput
 */
class FileInputTest extends InputTest
{
    /** @var FileInput */
    protected $input;

    public function setUp()
    {
        $this->input = new FileInput('foo');
        // Upload validator does not work in CLI test environment, disable
        $this->input->setAutoPrependUploadValidator(false);
    }

    public function testValueMayBeInjected()
    {
        $value = ['tmp_name' => 'bar'];
        $this->input->setValue($value);
        $this->assertEquals($value, $this->input->getValue());
    }

    public function testRetrievingValueFiltersTheValue()
    {
        $this->markTestSkipped('Test are not enabled in FileInputTest');
    }

    public function testRetrievingValueFiltersTheValueOnlyAfterValidating()
    {
        $value = ['tmp_name' => 'bar'];
        $this->input->setValue($value);

        $newValue = ['tmp_name' => 'foo'];
        /** @var Filter\File\Rename|MockObject $filterMock */
        $filterMock = $this->getMockBuilder(Filter\File\Rename::class)
            ->disableOriginalConstructor()
            ->getMock();
        $filterMock->expects($this->any())
            ->method('filter')
            ->will($this->returnValue($newValue));

        // Why not attach mocked filter directly?
        // No worky without wrapping in a callback.
        // Missing something in mock setup?
        $this->input->getFilterChain()->attach(
            function ($value) use ($filterMock) {
                return $filterMock->filter($value);
            }
        );

        $this->assertEquals($value, $this->input->getValue());
        $this->assertTrue(
            $this->input->isValid(),
            'isValid() value not match. Detail . ' . json_encode($this->input->getMessages())
        );
        $this->assertEquals($newValue, $this->input->getValue());
    }

    public function testCanFilterArrayOfMultiFileData()
    {
        $values = [
            ['tmp_name' => 'foo'],
            ['tmp_name' => 'bar'],
            ['tmp_name' => 'baz'],
        ];
        $this->input->setValue($values);

        $newValue = ['tmp_name' => 'new'];
        /** @var Filter\File\Rename|MockObject $filterMock */
        $filterMock = $this->getMockBuilder(Filter\File\Rename::class)
            ->disableOriginalConstructor()
            ->getMock();
        $filterMock->expects($this->any())
            ->method('filter')
            ->will($this->returnValue($newValue));

        // Why not attach mocked filter directly?
        // No worky without wrapping in a callback.
        // Missing something in mock setup?
        $this->input->getFilterChain()->attach(
            function ($value) use ($filterMock) {
                return $filterMock->filter($value);
            }
        );

        $this->assertEquals($values, $this->input->getValue());
        $this->assertTrue(
            $this->input->isValid(),
            'isValid() value not match. Detail . ' . json_encode($this->input->getMessages())
        );
        $this->assertEquals(
            [$newValue, $newValue, $newValue],
            $this->input->getValue()
        );
    }

    public function testCanRetrieveRawValue()
    {
        $value = ['tmp_name' => 'bar'];
        $this->input->setValue($value);
        $filter = new Filter\StringToUpper();
        $this->input->getFilterChain()->attach($filter);
        $this->assertEquals($value, $this->input->getRawValue());
    }

    public function testValidationOperatesOnFilteredValue()
    {
        $this->markTestSkipped('Test is not enabled in FileInputTest');
    }

    public function testValidationOperatesBeforeFiltering()
    {
        $badValue = [
            'tmp_name' => ' ' . __FILE__ . ' ',
            'name'     => 'foo',
            'size'     => 1,
            'error'    => 0,
        ];
        $this->input->setValue($badValue);

        $filteredValue = ['tmp_name' => 'new'];
        /** @var Filter\File\Rename|MockObject $filterMock */
        $filterMock = $this->getMockBuilder(Filter\File\Rename::class)
            ->disableOriginalConstructor()
            ->getMock();
        $filterMock->expects($this->any())
            ->method('filter')
            ->will($this->returnValue($filteredValue));

        // Why not attach mocked filter directly?
        // No worky without wrapping in a callback.
        // Missing something in mock setup?
        $this->input->getFilterChain()->attach(
            function ($value) use ($filterMock) {
                return $filterMock->filter($value);
            }
        );

        $validator = new Validator\File\Exists();
        $this->input->getValidatorChain()->attach($validator);
        $this->assertFalse($this->input->isValid());
        $this->assertEquals($badValue, $this->input->getValue());

        $goodValue = [
            'tmp_name' => __FILE__,
            'name'     => 'foo',
            'size'     => 1,
            'error'    => 0,
        ];
        $this->input->setValue($goodValue);
        $this->assertTrue(
            $this->input->isValid(),
            'isValid() value not match. Detail . ' . json_encode($this->input->getMessages())
        );
        $this->assertEquals($filteredValue, $this->input->getValue());
    }

    public function testGetMessagesReturnsValidationMessages()
    {
        $this->input->setAutoPrependUploadValidator(true);
        $this->input->setValue([
            'tmp_name' => __FILE__,
            'name'     => 'foo',
            'size'     => 1,
            'error'    => 0,
        ]);
        $this->assertFalse($this->input->isValid());
        $messages = $this->input->getMessages();
        $this->assertArrayHasKey(Validator\File\UploadFile::ATTACK, $messages);
    }

    public function testCanValidateArrayOfMultiFileData()
    {
        $values = [
            [
                'tmp_name' => __FILE__,
                'name'     => 'foo',
            ],
            [
                'tmp_name' => __FILE__,
                'name'     => 'bar',
            ],
            [
                'tmp_name' => __FILE__,
                'name'     => 'baz',
            ],
        ];
        $this->input->setValue($values);
        $validator = new Validator\File\Exists();
        $this->input->getValidatorChain()->attach($validator);
        $this->assertTrue(
            $this->input->isValid(),
            'isValid() value not match. Detail . ' . json_encode($this->input->getMessages())
        );

        // Negative test
        $values[1]['tmp_name'] = 'file-not-found';
        $this->input->setValue($values);
        $this->assertFalse($this->input->isValid());
    }

    public function testSpecifyingMessagesToInputReturnsThoseOnFailedValidation()
    {
        $this->input->setValue(['tmp_name' => 'bar']);
        $validator = new Validator\Digits();
        $this->input->getValidatorChain()->attach($validator);
        $this->input->setErrorMessage('Please enter only digits');
        $this->assertFalse($this->input->isValid());
        $messages = $this->input->getMessages();
        $this->assertArrayNotHasKey(Validator\Digits::NOT_DIGITS, $messages);
        $this->assertContains('Please enter only digits', $messages);
    }

    public function testAutoPrependUploadValidatorIsOnByDefault()
    {
        $input = new FileInput('foo');
        $this->assertTrue($input->getAutoPrependUploadValidator());
    }

    public function testUploadValidatorIsAddedWhenIsValidIsCalled()
    {
        $this->input->setAutoPrependUploadValidator(true);
        $this->assertTrue($this->input->getAutoPrependUploadValidator());
        $this->assertTrue($this->input->isRequired());
        $this->input->setValue([
            'tmp_name' => __FILE__,
            'name'     => 'foo',
            'size'     => 1,
            'error'    => 0,
        ]);
        $validatorChain = $this->input->getValidatorChain();
        $this->assertEquals(0, count($validatorChain->getValidators()));

        $this->assertFalse($this->input->isValid());
        $validators = $validatorChain->getValidators();
        $this->assertEquals(1, count($validators));
        $this->assertInstanceOf(Validator\File\UploadFile::class, $validators[0]['instance']);
    }

    public function testUploadValidatorIsNotAddedWhenIsValidIsCalled()
    {
        $this->assertFalse($this->input->getAutoPrependUploadValidator());
        $this->assertTrue($this->input->isRequired());
        $this->input->setValue(['tmp_name' => 'bar']);
        $validatorChain = $this->input->getValidatorChain();
        $this->assertEquals(0, count($validatorChain->getValidators()));

        $this->assertTrue(
            $this->input->isValid(),
            'isValid() value not match. Detail . ' . json_encode($this->input->getMessages())
        );
        $this->assertEquals(0, count($validatorChain->getValidators()));
    }

    public function testRequiredUploadValidatorValidatorNotAddedWhenOneExists()
    {
        $this->input->setAutoPrependUploadValidator(true);
        $this->assertTrue($this->input->getAutoPrependUploadValidator());
        $this->assertTrue($this->input->isRequired());
        $this->input->setValue(['tmp_name' => 'bar']);

        /** @var Validator\File\UploadFile|MockObject $uploadMock */
        $uploadMock = $this->getMock(Validator\File\UploadFile::class, ['isValid']);
        $uploadMock->expects($this->exactly(1))
                     ->method('isValid')
                     ->will($this->returnValue(true));

        $validatorChain = $this->input->getValidatorChain();
        $validatorChain->prependValidator($uploadMock);
        $this->assertTrue(
            $this->input->isValid(),
            'isValid() value not match. Detail . ' . json_encode($this->input->getMessages())
        );

        $validators = $validatorChain->getValidators();
        $this->assertEquals(1, count($validators));
        $this->assertEquals($uploadMock, $validators[0]['instance']);
    }

    public function testValidationsRunWithoutFileArrayDueToAjaxPost()
    {
        $this->input->setAutoPrependUploadValidator(true);
        $this->assertTrue($this->input->getAutoPrependUploadValidator());
        $this->assertTrue($this->input->isRequired());
        $this->input->setValue('');

        /** @var Validator\File\UploadFile|MockObject $uploadMock */
        $uploadMock = $this->getMock(Validator\File\UploadFile::class, ['isValid']);
        $uploadMock->expects($this->exactly(1))
            ->method('isValid')
            ->will($this->returnValue(false));

        $validatorChain = $this->input->getValidatorChain();
        $validatorChain->prependValidator($uploadMock);
        $this->assertFalse($this->input->isValid());

        $validators = $validatorChain->getValidators();
        $this->assertEquals(1, count($validators));
        $this->assertEquals($uploadMock, $validators[0]['instance']);
    }

    public function testNotEmptyValidatorAddedWhenIsValidIsCalled()
    {
        $this->markTestSkipped('Test is not enabled in FileInputTest');
    }

    public function testRequiredNotEmptyValidatorNotAddedWhenOneExists()
    {
        $this->markTestSkipped('Test is not enabled in FileInputTest');
    }

    public function testFallbackValueVsIsValidRules(
        $required = null,
        $fallbackValue = null,
        $originalValue = null,
        $isValid = null,
        $expectedValue = null
    ) {
        $this->markTestSkipped('Input::setFallbackValue is not implemented on FileInput');
    }


    public function testFallbackValueVsIsValidRulesWhenValueNotSet(
        $required = null,
        $fallbackValue = null,
        $originalValue = null,
        $isValid = null,
        $expectedValue = null
    ) {
        $this->markTestSkipped('Input::setFallbackValue is not implemented on FileInput');
    }

    public function testIsEmptyFileNotArray()
    {
        $rawValue = 'file';
        $this->assertTrue($this->input->isEmptyFile($rawValue));
    }

    public function testIsEmptyFileUploadNoFile()
    {
        $rawValue = [
            'tmp_name' => '',
            'error' => \UPLOAD_ERR_NO_FILE,
        ];
        $this->assertTrue($this->input->isEmptyFile($rawValue));
    }

    public function testIsEmptyFileOk()
    {
        $rawValue = [
            'tmp_name' => 'name',
            'error' => \UPLOAD_ERR_OK,
        ];
        $this->assertFalse($this->input->isEmptyFile($rawValue));
    }

    public function testIsEmptyMultiFileUploadNoFile()
    {
        $rawValue = [[
            'tmp_name' => 'foo',
            'error'    => \UPLOAD_ERR_NO_FILE
        ]];
        $this->assertTrue($this->input->isEmptyFile($rawValue));
    }

    public function testIsEmptyFileMultiFileOk()
    {
        $rawValue = [
            [
                'tmp_name' => 'foo',
                'error'    => \UPLOAD_ERR_OK
            ],
            [
                'tmp_name' => 'bar',
                'error'    => \UPLOAD_ERR_OK
            ],
        ];
        $this->assertFalse($this->input->isEmptyFile($rawValue));
    }

    public function testNotAllowEmptyWithFilterConvertsNonemptyToEmptyIsNotValid()
    {
        $this->markTestSkipped('does not apply to FileInput');
    }

    public function testNotAllowEmptyWithFilterConvertsEmptyToNonEmptyIsValid()
    {
        $this->markTestSkipped('does not apply to FileInput');
    }

    /**
     * Specific FileInput::merge extras
     */
    public function testFileInputMerge()
    {
        $source = new FileInput();
        $source->setAutoPrependUploadValidator(true);

        $target = $this->input;
        $target->setAutoPrependUploadValidator(false);

        $return = $target->merge($source);
        $this->assertSame($target, $return, 'merge() must return it self');

        $this->assertEquals(
            true,
            $target->getAutoPrependUploadValidator(),
            'getAutoPrependUploadValidator() value not match'
        );
    }

    public function isRequiredVsAllowEmptyVsContinueIfEmptyVsIsValidProvider()
    {
        $dataSets = parent::isRequiredVsAllowEmptyVsContinueIfEmptyVsIsValidProvider();

        // FileInput do not use NotEmpty validator so the only validator present in the chain is the custom one.
        unset($dataSets['Required: T; AEmpty: F; CIEmpty: F; Validator: X, Value: Empty / tmp_name']);
        unset($dataSets['Required: T; AEmpty: F; CIEmpty: F; Validator: X, Value: Empty / single']);
        unset($dataSets['Required: T; AEmpty: F; CIEmpty: F; Validator: X, Value: Empty / multi']);
        unset($dataSets['Required: F; AEmpty: F; CIEmpty: F; Validator: X, Value: Empty / tmp_name']);
        unset($dataSets['Required: F; AEmpty: F; CIEmpty: F; Validator: X, Value: Empty / single']);
        unset($dataSets['Required: F; AEmpty: F; CIEmpty: F; Validator: X, Value: Empty / multi']);

        return $dataSets;
    }

    public function emptyValueProvider()
    {
        return [
            'tmp_name' => [
                'raw' => 'file',
                'filtered' => [
                    'tmp_name' => 'file',
                    'name' => 'file',
                    'size' => 0,
                    'type' => '',
                    'error' => UPLOAD_ERR_NO_FILE,
                ],
            ],
            'single' => [
                'raw' => [
                    'tmp_name' => '',
                    'error' => UPLOAD_ERR_NO_FILE,
                ],
                'filtered' => [
                    'tmp_name' => '',
                    'error' => UPLOAD_ERR_NO_FILE,
                ],
            ],
            'multi' => [
                'raw' => [
                    [
                        'tmp_name' => 'foo',
                        'error' => UPLOAD_ERR_NO_FILE,
                    ],
                ],
                'filtered' => [
                    'tmp_name' => 'foo',
                    'error' => UPLOAD_ERR_NO_FILE,
                ],
            ],
        ];
    }

    public function mixedValueProvider()
    {
        $fooUploadErrOk = [
            'tmp_name' => 'foo',
            'error' => UPLOAD_ERR_OK,
        ];

        return [
            'single' => [
                'raw' => $fooUploadErrOk,
                'filtered' => $fooUploadErrOk,
            ],
            'multi' => [
                'raw' => [
                    $fooUploadErrOk,
                ],
                'filtered' => $fooUploadErrOk,
            ],
        ];
    }
}
