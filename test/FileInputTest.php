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
    public function testValueMayBeInjected()
    {
        $input = $this->createDefaultInput();

        $value = ['tmp_name' => 'bar'];
        $input->setValue($value);
        $this->assertEquals($value, $input->getValue());
    }

    public function testRetrievingValueFiltersTheValue()
    {
        $this->markTestSkipped('Test are not enabled in FileInputTest');
    }

    public function testRetrievingValueFiltersTheValueOnlyAfterValidating()
    {
        $input = $this->createDefaultInput();

        $value = ['tmp_name' => 'bar'];
        $input->setValue($value);

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
        $input->getFilterChain()->attach(
            function ($value) use ($filterMock) {
                return $filterMock->filter($value);
            }
        );

        $this->assertEquals($value, $input->getValue());
        $this->assertTrue($input->isValid());
        $this->assertEquals($newValue, $input->getValue());
    }

    public function testCanFilterArrayOfMultiFileData()
    {
        $input = $this->createDefaultInput();

        $values = [
            ['tmp_name' => 'foo'],
            ['tmp_name' => 'bar'],
            ['tmp_name' => 'baz'],
        ];
        $input->setValue($values);

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
        $input->getFilterChain()->attach(
            function ($value) use ($filterMock) {
                return $filterMock->filter($value);
            }
        );

        $this->assertEquals($values, $input->getValue());
        $this->assertTrue($input->isValid());
        $this->assertEquals(
            [$newValue, $newValue, $newValue],
            $input->getValue()
        );
    }

    public function testCanRetrieveRawValue()
    {
        $input = $this->createDefaultInput();

        $value = ['tmp_name' => 'bar'];
        $input->setValue($value);
        $filter = new Filter\StringToUpper();
        $input->getFilterChain()->attach($filter);
        $this->assertEquals($value, $input->getRawValue());
    }

    public function testIsValidReturnsFalseIfValidationChainFails()
    {
        $input = $this->createDefaultInput();

        $input->setValue(['tmp_name' => 'bar']);
        $validator = new Validator\Digits();
        $input->getValidatorChain()->attach($validator);
        $this->assertFalse($input->isValid());
    }

    public function testIsValidReturnsTrueIfValidationChainSucceeds()
    {
        $input = $this->createDefaultInput();

        $input->setValue(['tmp_name' => 'bar']);
        $validator = new Validator\NotEmpty();
        $input->getValidatorChain()->attach($validator);
        $this->assertTrue($input->isValid());
    }

    public function testValidationOperatesOnFilteredValue()
    {
        $this->markTestSkipped('Test is not enabled in FileInputTest');
    }

    public function testValidationOperatesBeforeFiltering()
    {
        $input = $this->createDefaultInput();

        $badValue = [
            'tmp_name' => ' ' . __FILE__ . ' ',
            'name'     => 'foo',
            'size'     => 1,
            'error'    => 0,
        ];
        $input->setValue($badValue);

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
        $input->getFilterChain()->attach(
            function ($value) use ($filterMock) {
                return $filterMock->filter($value);
            }
        );

        $validator = new Validator\File\Exists();
        $input->getValidatorChain()->attach($validator);
        $this->assertFalse($input->isValid());
        $this->assertEquals($badValue, $input->getValue());

        $goodValue = [
            'tmp_name' => __FILE__,
            'name'     => 'foo',
            'size'     => 1,
            'error'    => 0,
        ];
        $input->setValue($goodValue);
        $this->assertTrue($input->isValid());
        $this->assertEquals($filteredValue, $input->getValue());
    }

    public function testGetMessagesReturnsValidationMessages()
    {
        $input = $this->createDefaultInput();

        $input->setAutoPrependUploadValidator(true);
        $input->setValue([
            'tmp_name' => __FILE__,
            'name'     => 'foo',
            'size'     => 1,
            'error'    => 0,
        ]);
        $this->assertFalse($input->isValid());
        $messages = $input->getMessages();
        $this->assertArrayHasKey(Validator\File\UploadFile::ATTACK, $messages);
    }

    public function testCanValidateArrayOfMultiFileData()
    {
        $input = $this->createDefaultInput();

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
        $input->setValue($values);
        $validator = new Validator\File\Exists();
        $input->getValidatorChain()->attach($validator);
        $this->assertTrue($input->isValid());

        // Negative test
        $values[1]['tmp_name'] = 'file-not-found';
        $input->setValue($values);
        $this->assertFalse($input->isValid());
    }

    public function testSpecifyingMessagesToInputReturnsThoseOnFailedValidation()
    {
        $input = $this->createDefaultInput();

        $input->setValue(['tmp_name' => 'bar']);
        $validator = new Validator\Digits();
        $input->getValidatorChain()->attach($validator);
        $input->setErrorMessage('Please enter only digits');
        $this->assertFalse($input->isValid());
        $messages = $input->getMessages();
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
        $input = $this->createDefaultInput();

        $input->setAutoPrependUploadValidator(true);
        $this->assertTrue($input->getAutoPrependUploadValidator());
        $this->assertTrue($input->isRequired());
        $input->setValue([
            'tmp_name' => __FILE__,
            'name'     => 'foo',
            'size'     => 1,
            'error'    => 0,
        ]);
        $validatorChain = $input->getValidatorChain();
        $this->assertEquals(0, count($validatorChain->getValidators()));

        $this->assertFalse($input->isValid());
        $validators = $validatorChain->getValidators();
        $this->assertEquals(1, count($validators));
        $this->assertInstanceOf(Validator\File\UploadFile::class, $validators[0]['instance']);
    }

    public function testUploadValidatorIsNotAddedWhenIsValidIsCalled()
    {
        $input = $this->createDefaultInput();

        $this->assertFalse($input->getAutoPrependUploadValidator());
        $this->assertTrue($input->isRequired());
        $input->setValue(['tmp_name' => 'bar']);
        $validatorChain = $input->getValidatorChain();
        $this->assertEquals(0, count($validatorChain->getValidators()));

        $this->assertTrue($input->isValid());
        $this->assertEquals(0, count($validatorChain->getValidators()));
    }

    public function testRequiredUploadValidatorValidatorNotAddedWhenOneExists()
    {
        $input = $this->createDefaultInput();

        $input->setAutoPrependUploadValidator(true);
        $this->assertTrue($input->getAutoPrependUploadValidator());
        $this->assertTrue($input->isRequired());
        $input->setValue(['tmp_name' => 'bar']);

        /** @var Validator\File\UploadFile|MockObject $uploadMock */
        $uploadMock = $this->getMock(Validator\File\UploadFile::class, ['isValid']);
        $uploadMock->expects($this->exactly(1))
                     ->method('isValid')
                     ->will($this->returnValue(true));

        $validatorChain = $input->getValidatorChain();
        $validatorChain->prependValidator($uploadMock);
        $this->assertTrue($input->isValid());

        $validators = $validatorChain->getValidators();
        $this->assertEquals(1, count($validators));
        $this->assertEquals($uploadMock, $validators[0]['instance']);
    }

    public function testValidationsRunWithoutFileArrayDueToAjaxPost()
    {
        $input = $this->createDefaultInput();

        $input->setAutoPrependUploadValidator(true);
        $this->assertTrue($input->getAutoPrependUploadValidator());
        $this->assertTrue($input->isRequired());
        $input->setValue('');

        /** @var Validator\File\UploadFile|MockObject $uploadMock */
        $uploadMock = $this->getMock(Validator\File\UploadFile::class, ['isValid']);
        $uploadMock->expects($this->exactly(1))
            ->method('isValid')
            ->will($this->returnValue(false));

        $validatorChain = $input->getValidatorChain();
        $validatorChain->prependValidator($uploadMock);
        $this->assertFalse($input->isValid());

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

    public function testMerge()
    {
        $value  = ['tmp_name' => 'bar'];

        $input  = new FileInput('foo');
        $input->setAutoPrependUploadValidator(false);
        $input->setValue($value);
        $filter = new Filter\StringTrim();
        $input->getFilterChain()->attach($filter);
        $validator = new Validator\Digits();
        $input->getValidatorChain()->attach($validator);

        $input2 = new FileInput('bar');
        $input2->merge($input);
        $validatorChain = $input->getValidatorChain();
        $filterChain    = $input->getFilterChain();

        $this->assertFalse($input2->getAutoPrependUploadValidator());
        $this->assertEquals($value, $input2->getRawValue());
        $this->assertEquals(1, $validatorChain->count());
        $this->assertEquals(1, $filterChain->count());

        $validators = $validatorChain->getValidators();
        $this->assertInstanceOf(Validator\Digits::class, $validators[0]['instance']);

        $filters = $filterChain->getFilters()->toArray();
        $this->assertInstanceOf(Filter\StringTrim::class, $filters[0]);
    }

    public function testFallbackValue($fallbackValue = null)
    {
        $this->markTestSkipped('Not use fallback value');
    }

    public function testIsEmptyFileNotArray()
    {
        $input = $this->createDefaultInput();

        $rawValue = 'file';
        $this->assertTrue($input->isEmptyFile($rawValue));
    }

    public function testIsEmptyFileUploadNoFile()
    {
        $input = $this->createDefaultInput();

        $rawValue = [
            'tmp_name' => '',
            'error' => \UPLOAD_ERR_NO_FILE,
        ];
        $this->assertTrue($input->isEmptyFile($rawValue));
    }

    public function testIsEmptyFileOk()
    {
        $input = $this->createDefaultInput();

        $rawValue = [
            'tmp_name' => 'name',
            'error' => \UPLOAD_ERR_OK,
        ];
        $this->assertFalse($input->isEmptyFile($rawValue));
    }

    public function testIsEmptyMultiFileUploadNoFile()
    {
        $input = $this->createDefaultInput();

        $rawValue = [[
            'tmp_name' => 'foo',
            'error'    => \UPLOAD_ERR_NO_FILE
        ]];
        $this->assertTrue($input->isEmptyFile($rawValue));
    }

    public function testIsEmptyFileMultiFileOk()
    {
        $input = $this->createDefaultInput();

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
        $this->assertFalse($input->isEmptyFile($rawValue));
    }

    public function emptyValuesProvider()
    {
        // Provide empty values specific for file input
        return [
            ['file'],
            [[
                'tmp_name' => '',
                'error' => \UPLOAD_ERR_NO_FILE,
            ]],
            [[[
                'tmp_name' => 'foo',
                'error'    => \UPLOAD_ERR_NO_FILE
            ]]],
        ];
    }

    /**
     * @dataProvider emptyValuesProvider
     */
    public function testAllowEmptyOptionSet($emptyValue, $input = null)
    {
        $input = $this->createDefaultInput();

        // UploadFile validator is disabled, pretend one
        $validator = new Validator\Callback(function () {
            return false; // This should never be called
        });
        $input->getValidatorChain()->attach($validator);
        parent::testAllowEmptyOptionSet($emptyValue, $input);
    }

    /**
     * @dataProvider emptyValuesProvider
     */
    public function testAllowEmptyOptionNotSet($emptyValue, $input = null)
    {
        $input = $this->createDefaultInput();

        // UploadFile validator is disabled, pretend one
        $message = 'pretend failing UploadFile validator';
        $validator = new Validator\Callback(function () {
            return false;
        });
        $validator->setMessage($message);
        $input->getValidatorChain()->attach($validator);
        parent::testAllowEmptyOptionNotSet($emptyValue, $input);
        $this->assertEquals(['callbackValue' => $message], $input->getMessages());
    }

    public function testNotAllowEmptyWithFilterConvertsNonemptyToEmptyIsNotValid()
    {
        $this->markTestSkipped('does not apply to FileInput');
    }

    public function testNotAllowEmptyWithFilterConvertsEmptyToNonEmptyIsValid()
    {
        $this->markTestSkipped('does not apply to FileInput');
    }

    protected function createDefaultInput()
    {
        $input = new FileInput('foo');
        // Upload validator does not work in CLI test environment, disable
        $input->setAutoPrependUploadValidator(false);

        return $input;
    }
}
