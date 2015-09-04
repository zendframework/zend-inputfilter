<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\InputFilter;

use ArrayIterator;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use PHPUnit_Framework_TestCase as TestCase;
use stdClass;
use Zend\InputFilter\BaseInputFilter;
use Zend\InputFilter\CollectionInputFilter;
use Zend\InputFilter\Exception\RuntimeException;
use Zend\InputFilter\Input;
use Zend\InputFilter\InputFilter;

/**
 * @covers Zend\InputFilter\CollectionInputFilter
 */
class CollectionInputFilterTest extends TestCase
{
    /**
     * @var CollectionInputFilter
     */
    protected $filter;

    public function setUp()
    {
        $this->filter = new CollectionInputFilter();
    }

    public function testSetDataWithInvalidDataTypeThrowsInvalidArgumentException()
    {
        $inputFilter = $this->filter;

        $this->setExpectedException(
            RuntimeException::class,
            'expects an instance of Zend\InputFilter\BaseInputFilter; received "stdClass"'
        );
        /** @noinspection PhpParamsInspection */
        $inputFilter->setInputFilter(new stdClass());
    }

    /**
     * @dataProvider inputFilterProvider
     */
    public function testSetInputFilter($inputFilter, $expectedType)
    {
        $this->filter->setInputFilter($inputFilter);

        $this->assertInstanceOf($expectedType, $this->filter->getInputFilter(), 'getInputFilter() type not match');
    }

    public function testGetDefaultInputFilter()
    {
        $this->assertInstanceOf(BaseInputFilter::class, $this->filter->getInputFilter());
    }

    /**
     * @dataProvider isRequiredProvider
     */
    public function testSetRequired($value)
    {
        $this->filter->setIsRequired($value);
        $this->assertEquals($value, $this->filter->getIsRequired());
    }

    /**
     * @dataProvider countVsDataProvider
     */
    public function testSetCount($count, $data, $expectedCount)
    {
        if ($count !== null) {
            $this->filter->setCount($count);
        }
        if ($data !== null) {
            $this->filter->setData($data);
        }

        $this->assertEquals($expectedCount, $this->filter->getCount(), 'getCount() value not match');
    }

    /**
     * @group 6160
     */
    public function testGetCountReturnsRightCountOnConsecutiveCallsWithDifferentData()
    {
        $collectionData1 = [
            ['foo' => 'bar'],
            ['foo' => 'baz'],
        ];

        $collectionData2 = [
            ['foo' => 'bar'],
        ];

        $this->filter->setData($collectionData1);
        $this->assertEquals(2, $this->filter->getCount());
        $this->filter->setData($collectionData2);
        $this->assertEquals(1, $this->filter->getCount());
    }

    public function testInvalidCollectionIsNotValid()
    {
        $data = 1;

        $this->filter->setData($data);

        $this->assertFalse($this->filter->isValid());
    }

    /**
     * @dataProvider dataVsValidProvider
     */
    public function testDataVsValid(
        $required,
        $count,
        $data,
        $inputFilter,
        $expectedRaw,
        $expecteValues,
        $expectedValid,
        $expectedMessages
    ) {
        $this->filter->setInputFilter($inputFilter);
        $this->filter->setData($data);
        if ($count !== null) {
            $this->filter->setCount($count);
        }
        $this->filter->setIsRequired($required);

        $this->assertEquals(
            $expectedValid,
            $this->filter->isValid(),
            'isValid() value not match. Detail . ' . json_encode($this->filter->getMessages())
        );
        $this->assertEquals($expectedRaw, $this->filter->getRawValues(), 'getRawValues() value not match');
        $this->assertEquals($expecteValues, $this->filter->getValues(), 'getValues() value not match');
        $this->assertEquals($expectedMessages, $this->filter->getMessages(), 'getMessages() value not match');
    }

    public function dataVsValidProvider()
    {
        $dataRaw = [
            'fooInput' => 'fooRaw',
        ];
        $dataFiltered = [
            'fooInput' => 'fooFiltered',
        ];
        $colRaw = [$dataRaw];
        $colFiltered = [$dataFiltered];
        $errorMessage = [
            'fooInput' => 'fooError',
        ];
        $colMessages = [$errorMessage];

        $invalidIF = function () use ($dataRaw, $dataFiltered, $errorMessage) {
            return $this->createBaseInputFilterMock(false, $dataRaw, $dataFiltered, $errorMessage);
        };
        $validIF = function () use ($dataRaw, $dataFiltered) {
            return $this->createBaseInputFilterMock(true, $dataRaw, $dataFiltered);
        };
        $isRequired = true;

        // @codingStandardsIgnoreStart
        $dataSets = [
            // Description => [$required, $count, $data, $inputFilter, $expectedRaw, $expecteValues, $expectedValid, $expectedMessages]
            'Required: T, Count: N, Valid: T'  => [ $isRequired, null, $colRaw, $validIF  , $colRaw, $colFiltered, true , []],
            'Required: T, Count: N, Valid: F'  => [ $isRequired, null, $colRaw, $invalidIF, $colRaw, $colFiltered, false, $colMessages],
            'Required: T, Count: +1, Valid: F' => [ $isRequired,    2, $colRaw, $invalidIF, $colRaw, $colFiltered, false, $colMessages],
            'Required: F, Count: N, Valid: T'  => [!$isRequired, null, $colRaw, $validIF  , $colRaw, $colFiltered, true , []],
            'Required: F, Count: N, Valid: F'  => [!$isRequired, null, $colRaw, $invalidIF, $colRaw, $colFiltered, false, $colMessages],
            'Required: F, Count: +1, Valid: F' => [!$isRequired,    2, $colRaw, $invalidIF, $colRaw, $colFiltered, false, $colMessages],
            'Required: T, Data: [], Valid: X'  => [ $isRequired, null, []     , $invalidIF, []     , []          , false, []],
            'Required: F, Data: [], Valid: X'  => [!$isRequired, null, []     , $invalidIF, []     , []          , true , []],
        ];
        // @codingStandardsIgnoreEnd

        array_walk(
            $dataSets,
            function (&$set) {
                // Create unique mock input instances for each set
                $inputFilter = $set[3]();

                $set[3] = $inputFilter;
            }
        );

        return $dataSets;
    }

    public function testSetValidationGroupUsingFormStyle()
    {
        $validationGroup = [
            'fooGroup',
        ];
        $colValidationGroup = [$validationGroup];

        $dataRaw = [
            'fooInput' => 'fooRaw',
        ];
        $dataFiltered = [
            'fooInput' => 'fooFiltered',
        ];
        $colRaw = [$dataRaw];
        $colFiltered = [$dataFiltered];
        $baseInputFilter = $this->createBaseInputFilterMock(true, $dataRaw, $dataFiltered);
        $baseInputFilter->expects($this->once())
            ->method('setValidationGroup')
            ->with($validationGroup)
        ;

        $this->filter->setInputFilter($baseInputFilter);
        $this->filter->setData($colRaw);
        $this->filter->setValidationGroup($colValidationGroup);

        $this->assertTrue(
            $this->filter->isValid(),
            'isValid() value not match. Detail . ' . json_encode($this->filter->getMessages())
        );
        $this->assertEquals($colRaw, $this->filter->getRawValues(), 'getRawValues() value not match');
        $this->assertEquals($colFiltered, $this->filter->getValues(), 'getValues() value not match');
        $this->assertEquals([], $this->filter->getMessages(), 'getMessages() value not match');
    }

    public function dataNestingCollection()
    {
        return [
            'count not specified' => [
                'count' => null,
                'isValid' => true,
            ],
            'count=0' => [
                'count' => 0,
                'isValid' => true,
            ],
            'count = 1' =>  [
                'count' => 1,
                'isValid' => true,
            ],
            'count = 2' => [
                'count' => 2,
                'isValid' => false,
            ],
            'count = 3' => [
                'count' => 3,
                'isValid' => false,
            ],
        ];
    }

    /**
     * @dataProvider dataNestingCollection
     */
    public function testNestingCollectionCountCached($count, $expectedIsValid)
    {
        $firstInputFilter = new InputFilter();

        $firstCollection = new CollectionInputFilter();
        $firstCollection->setInputFilter($firstInputFilter);

        $someInput = new Input('input');
        $secondInputFilter = new InputFilter();
        $secondInputFilter->add($someInput, 'input');

        $secondCollection = new CollectionInputFilter();
        $secondCollection->setInputFilter($secondInputFilter);
        if (!is_null($count)) {
            $secondCollection->setCount($count);
        }

        $firstInputFilter->add($secondCollection, 'second_collection');

        $mainInputFilter = new InputFilter();
        $mainInputFilter->add($firstCollection, 'first_collection');

        $data = [
            'first_collection' => [
                [
                    'second_collection' => [
                        [
                            'input' => 'some value',
                        ],
                        [
                            'input' => 'some value',
                        ],
                    ],
                ],
                [
                    'second_collection' => [
                        [
                            'input' => 'some value',
                        ],
                    ],
                ],
            ],
        ];

        $mainInputFilter->setData($data);
        $this->assertSame($expectedIsValid, $mainInputFilter->isValid());
    }

    public function inputFilterProvider()
    {
        $baseInputFilter = new BaseInputFilter();

        $inputFilterSpecificationAsArray = [];
        $inputSpecificationAsTraversable = new ArrayIterator($inputFilterSpecificationAsArray);

        $inputFilterSpecificationResult = new InputFilter();
        $inputFilterSpecificationResult->getFactory()->getInputFilterManager();

        $dataSets = [
            // Description => [inputFilter, $expectedType]
            'BaseInputFilter' => [$baseInputFilter, BaseInputFilter::class],
            'array' => [$inputFilterSpecificationAsArray, InputFilter::class],
            'Traversable' => [$inputSpecificationAsTraversable, InputFilter::class],
        ];

        return $dataSets;
    }

    public function countVsDataProvider()
    {
        $data0 = [];
        $data1 = ['A' => 'a'];
        $data2 = ['A' => 'a', 'B' => 'b'];

        // @codingStandardsIgnoreStart
        return [
            // Description => [$count, $data, $expectedCount]
            'C:   -1, D: null' => [  -1, null  ,  0],
            'C:    0, D: null' => [   0, null  ,  0],
            'C:    1, D: null' => [   1, null  ,  1],
            'C: null, D:    0' => [null, $data0,  0],
            'C: null, D:    1' => [null, $data1,  1],
            'C: null, D:    2' => [null, $data2,  2],
            'C:   -1, D:    0' => [  -1, $data0,  0],
            'C:    0, D:    0' => [   0, $data0,  0],
            'C:    1, D:    0' => [   1, $data0,  1],
            'C:   -1, D:    1' => [  -1, $data1,  0],
            'C:    0, D:    1' => [   0, $data1,  0],
            'C:    1, D:    1' => [   1, $data1,  1],
        ];
        // @codingStandardsIgnoreEnd
    }

    public function isRequiredProvider()
    {
        return [
            'enabled' => [true],
            'disabled' => [false],
        ];
    }

    /**
     * @param null|bool $isValid
     * @param mixed[] $getRawValues
     * @param mixed[] $getValues
     * @param string[] $getMessages
     *
     * @return MockObject|BaseInputFilter
     */
    protected function createBaseInputFilterMock(
        $isValid = null,
        $getRawValues = [],
        $getValues = [],
        $getMessages = []
    ) {
        /** @var BaseInputFilter|MockObject $inputFilter */
        $inputFilter = $this->getMock(BaseInputFilter::class);
        $inputFilter->method('getRawValues')
            ->willReturn($getRawValues)
        ;
        $inputFilter->method('getValues')
            ->willReturn($getValues)
        ;
        if (($isValid === false) || ($isValid === true)) {
            $inputFilter->expects($this->once())
                ->method('isValid')
                ->willReturn($isValid)
            ;
        } else {
            $inputFilter->expects($this->never())
                ->method('isValid')
            ;
        }
        $inputFilter->method('getMessages')
            ->willReturn($getMessages)
        ;

        return $inputFilter;
    }
}
