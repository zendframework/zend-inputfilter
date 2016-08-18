<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\InputFilter;

use Zend\InputFilter\FileInput;

/**
 * @covers Zend\InputFilter\FileInput
 */
class FileInputTest extends InputTest
{
    public function setUp()
    {
        $this->input = new FileInput('foo');
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
        $this->input->setFilterChain($this->createFilterChainMock([[$value, $newValue]]));

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
        $filteredValue = [$newValue, $newValue, $newValue];
        $this->input->setFilterChain($this->createFilterChainMock([
            [$values[0], $newValue],
            [$values[1], $newValue],
            [$values[2], $newValue],
        ]));

        $this->assertEquals($values, $this->input->getValue());
        $this->assertTrue(
            $this->input->isValid(),
            'isValid() value not match. Detail . ' . json_encode($this->input->getMessages())
        );
        $this->assertEquals(
            $filteredValue,
            $this->input->getValue()
        );
    }

    public function testCanRetrieveRawValue()
    {
        $value = ['tmp_name' => 'bar'];
        $this->input->setValue($value);

        $newValue = ['tmp_name' => 'new'];
        $this->input->setFilterChain($this->createFilterChainMock([[$value, $newValue]]));

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
        $this->input->setFilterChain($this->createFilterChainMock([[$badValue, $filteredValue]]));
        $this->input->setValidatorChain($this->createValidatorChainMock([[$badValue, null, false]]));

        $this->assertFalse($this->input->isValid());
        $this->assertEquals($badValue, $this->input->getValue());
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
        $this->input->setValidatorChain($this->createValidatorChainMock([
            [$values[0], null, true],
            [$values[1], null, false],
            [$values[2], null, true],
        ]));

        $this->assertFalse(
            $this->input->isValid(),
            'isValid() value not match. Detail . ' . json_encode($this->input->getMessages())
        );
    }

    public function testValidationsRunWithoutFileArrayDueToAjaxPost()
    {
        $this->assertTrue($this->input->isRequired());
        $this->input->setValue('');

        $expectedNormalizedValue = [
            'tmp_name' => '',
            'name' => '',
            'size' => 0,
            'type' => '',
            'error' => UPLOAD_ERR_NO_FILE,
        ];
        $this->input->setValidatorChain($this->createValidatorChainMock([[$expectedNormalizedValue, null, false]]));
        $this->assertFalse($this->input->isValid());
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
        $fallbackValue = null
    ) {
        $this->markTestSkipped('Input::setFallbackValue is not implemented on FileInput');
    }

    protected function getDummyValue($raw = true)
    {
        return ['tmp_name' => 'bar'];
    }
}
