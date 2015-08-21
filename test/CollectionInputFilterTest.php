<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\InputFilter;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\InputFilter\BaseInputFilter;
use Zend\InputFilter\CollectionInputFilter;
use Zend\InputFilter\Input;
use Zend\InputFilter\InputFilter;
use Zend\Validator;

/**
 * @covers Zend\InputFilter\CollectionInputFilter
 */
class CollectionInputFilterTest extends TestCase
{
    use InputFilterInterfaceTestTrait;
    use ReplaceableInputInterfaceTestTrait;
    use UnknownInputsCapableInterfaceTestTrait;

    public function getBaseInputFilter()
    {
        $filter = new BaseInputFilter();

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
               ->add($baz, 'baz')
               ->add($this->getChildInputFilter(), 'nest');

        return $filter;
    }

    public function getChildInputFilter()
    {
        $filter = new BaseInputFilter();

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

    public function getValidCollectionData()
    {
        return [
            [
                'foo' => ' bazbat ',
                'bar' => '12345',
                'baz' => '',
                'nest' => [
                    'foo' => ' bazbat ',
                    'bar' => '12345',
                    'baz' => '',
                ],
            ],
            [
                'foo' => ' batbaz ',
                'bar' => '54321',
                'baz' => '',
                'nest' => [
                    'foo' => ' batbaz ',
                    'bar' => '54321',
                    'baz' => '',
                ],
            ]
        ];
    }

    public function testSetInputFilter()
    {
        $inputFilter = $this->createDefaultInputFilter();

        $inputFilter->setInputFilter(new BaseInputFilter());
        $this->assertInstanceOf(BaseInputFilter::class, $inputFilter->getInputFilter());
    }

    public function testGetDefaultInputFilter()
    {
        $inputFilter = $this->createDefaultInputFilter();

        $this->assertInstanceOf(BaseInputFilter::class, $inputFilter->getInputFilter());
    }

    public function testSetCount()
    {
        $inputFilter = $this->createDefaultInputFilter();

        $inputFilter->setCount(5);
        $this->assertEquals(5, $inputFilter->getCount());
    }

    public function testSetCountBelowZero()
    {
        $inputFilter = $this->createDefaultInputFilter();

        $inputFilter->setCount(-1);
        $this->assertEquals(0, $inputFilter->getCount());
    }

    public function testGetCountUsesCountOfCollectionDataWhenNotSet()
    {
        $inputFilter = $this->createDefaultInputFilter();

        $collectionData = [
            ['foo' => 'bar'],
            ['foo' => 'baz']
        ];

        $inputFilter->setData($collectionData);
        $this->assertEquals(2, $inputFilter->getCount());
    }

    public function testGetCountUsesSpecifiedCount()
    {
        $inputFilter = $this->createDefaultInputFilter();

        $collectionData = [
            ['foo' => 'bar'],
            ['foo' => 'baz']
        ];

        $inputFilter->setCount(3);
        $inputFilter->setData($collectionData);
        $this->assertEquals(3, $inputFilter->getCount());
    }

    /**
     * @group 6160
     */
    public function testGetCountReturnsRightCountOnConsecutiveCallsWithDifferentData()
    {
        $inputFilter = $this->createDefaultInputFilter();

        $collectionData1 = [
            ['foo' => 'bar'],
            ['foo' => 'baz']
        ];

        $collectionData2 = [
            ['foo' => 'bar']
        ];

        $inputFilter->setData($collectionData1);
        $this->assertEquals(2, $inputFilter->getCount());
        $inputFilter->setData($collectionData2);
        $this->assertEquals(1, $inputFilter->getCount());
    }

    public function testCanValidateValidData()
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('ext/intl not enabled');
        }

        $inputFilter = $this->createDefaultInputFilter();

        $inputFilter->setInputFilter($this->getBaseInputFilter());
        $inputFilter->setData($this->getValidCollectionData());
        $this->assertTrue($inputFilter->isValid());
    }

    public function testCanValidateValidDataWithNonConsecutiveKeys()
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('ext/intl not enabled');
        }

        $inputFilter = $this->createDefaultInputFilter();

        $collectionData = $this->getValidCollectionData();
        $collectionData[2] = $collectionData[0];
        unset($collectionData[0]);
        $inputFilter->setInputFilter($this->getBaseInputFilter());
        $inputFilter->setData($collectionData);
        $this->assertTrue($inputFilter->isValid());
    }

    public function testInvalidDataReturnsFalse()
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('ext/intl not enabled');
        }

        $inputFilter = $this->createDefaultInputFilter();

        $invalidCollectionData = [
            [
                'foo' => ' bazbatlong ',
                'bar' => '12345',
                'baz' => '',
            ],
            [
                'foo' => ' bazbat ',
                'bar' => '12345',
                'baz' => '',
            ]
        ];

        $inputFilter->setInputFilter($this->getBaseInputFilter());
        $inputFilter->setData($invalidCollectionData);
        $this->assertFalse($inputFilter->isValid());
    }

    public function testDataLessThanCountIsInvalid()
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('ext/intl not enabled');
        }

        $inputFilter = $this->createDefaultInputFilter();

        $invalidCollectionData = [
            [
                'foo' => ' bazbat ',
                'bar' => '12345',
                'baz' => '',
                'nest' => [
                    'foo' => ' bazbat ',
                    'bar' => '12345',
                    'baz' => '',
                ],
            ],
        ];

        $inputFilter->setCount(2);
        $inputFilter->setInputFilter($this->getBaseInputFilter());
        $inputFilter->setData($invalidCollectionData);
        $this->assertFalse($inputFilter->isValid());
    }

    public function testGetValues()
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('ext/intl not enabled');
        }

        $inputFilter = $this->createDefaultInputFilter();

        $expectedData = [
            [
                'foo' => 'bazbat',
                'bar' => '12345',
                'baz' => '',
                'nest' => [
                    'foo' => 'bazbat',
                    'bar' => '12345',
                    'baz' => '',
                ],
            ],
            [
                'foo' => 'batbaz',
                'bar' => '54321',
                'baz' => '',
                'nest' => [
                    'foo' => 'batbaz',
                    'bar' => '54321',
                    'baz' => '',
                ],
            ]
        ];

        $inputFilter->setInputFilter($this->getBaseInputFilter());
        $inputFilter->setData($this->getValidCollectionData());

        $this->assertTrue($inputFilter->isValid());
        $this->assertEquals($expectedData, $inputFilter->getValues());

        $this->assertCount(2, $inputFilter->getValidInput());
        foreach ($inputFilter->getValidInput() as $validInputs) {
            $this->assertCount(4, $validInputs);
        }
    }

    public function testGetRawValues()
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('ext/intl not enabled');
        }

        $inputFilter = $this->createDefaultInputFilter();

        $expectedData = [
            [
                'foo' => ' bazbat ',
                'bar' => '12345',
                'baz' => '',
                'nest' => [
                    'foo' => ' bazbat ',
                    'bar' => '12345',
                    'baz' => '',
                ],
            ],
            [
                'foo' => ' batbaz ',
                'bar' => '54321',
                'baz' => '',
                'nest' => [
                    'foo' => ' batbaz ',
                    'bar' => '54321',
                    'baz' => '',
                ],
            ]
        ];

        $inputFilter->setInputFilter($this->getBaseInputFilter());
        $inputFilter->setData($this->getValidCollectionData());

        $this->assertTrue($inputFilter->isValid());
        $this->assertEquals($expectedData, $inputFilter->getRawValues());
    }

    public function testGetMessagesForInvalidInputs()
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('ext/intl not enabled');
        }

        $inputFilter = $this->createDefaultInputFilter();

        $invalidCollectionData = [
            [
                'foo' => ' bazbattoolong ',
                'bar' => '12345',
                'baz' => '',
                'nest' => [
                    'foo' => ' bazbat ',
                    'bar' => '12345',
                    'baz' => '',
                ],
            ],
            [
                'foo' => ' bazbat ',
                'bar' => 'notstring',
                'baz' => '',
                'nest' => [
                    'foo' => ' bazbat ',
                    'bar' => '12345',
                    'baz' => '',
                ],
            ],
            [
                'foo' => ' bazbat ',
                'bar' => '12345',
                'baz' => '',
                'nest' => [
                    // missing 'foo' here
                    'bar' => '12345',
                    'baz' => '',
                ],
            ],
        ];

        $inputFilter->setInputFilter($this->getBaseInputFilter());
        $inputFilter->setData($invalidCollectionData);

        $this->assertFalse($inputFilter->isValid());

        $this->assertCount(3, $inputFilter->getInvalidInput());
        foreach ($inputFilter->getInvalidInput() as $invalidInputs) {
            $this->assertCount(1, $invalidInputs);
        }

        $messages = $inputFilter->getMessages();

        $this->assertCount(3, $messages);
        $this->assertArrayHasKey('foo', $messages[0]);
        $this->assertArrayHasKey('bar', $messages[1]);
        $this->assertArrayHasKey('nest', $messages[2]);

        $this->assertCount(1, $messages[0]['foo']);
        $this->assertCount(1, $messages[1]['bar']);
        $this->assertCount(1, $messages[2]['nest']);
    }

    public function testSetValidationGroupUsingFormStyle()
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('ext/intl not enabled');
        }

        $inputFilter = $this->createDefaultInputFilter();

        // forms set an array of identical validation groups for each set of data
        $formValidationGroup = [
            [
                'foo',
                'bar',
            ],
            [
                'foo',
                'bar',
            ],
            [
                'foo',
                'bar',
            ]
        ];

        $data = [
            [
                'foo' => ' bazbat ',
                'bar' => '12345'
            ],
            [
                'foo' => ' batbaz ',
                'bar' => '54321'
            ],
            [
                'foo' => ' batbaz ',
                'bar' => '54321'
            ]
        ];

        $inputFilter->setInputFilter($this->getBaseInputFilter());
        $inputFilter->setData($data);
        $inputFilter->setValidationGroup($formValidationGroup);

        $this->assertTrue($inputFilter->isValid());
    }

    public function testEmptyCollectionIsValidByDefault()
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('ext/intl not enabled');
        }

        $inputFilter = $this->createDefaultInputFilter();

        $data = [];

        $inputFilter->setInputFilter($this->getBaseInputFilter());
        $inputFilter->setData($data);

        $this->assertTrue($inputFilter->isValid());
    }

    public function testEmptyCollectionIsNotValidIfRequired()
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('ext/intl not enabled');
        }

        $inputFilter = $this->createDefaultInputFilter();

        $data = [];

        $inputFilter->setInputFilter($this->getBaseInputFilter());
        $inputFilter->setData($data);
        $inputFilter->setIsRequired(true);

        $this->assertFalse($inputFilter->isValid());
    }

    public function testSetRequired()
    {
        $inputFilter = $this->createDefaultInputFilter();

        $inputFilter->setIsRequired(true);
        $this->assertEquals(true, $inputFilter->getIsRequired());
    }

    public function testNonRequiredFieldsAreValidated()
    {
        $inputFilter = $this->createDefaultInputFilter();

        $invalidCollectionData = [
            [
                'foo' => ' bazbattoolong ',
                'bar' => '12345',
                'baz' => 'baztoolong',
                'nest' => [
                    'foo' => ' bazbat ',
                    'bar' => '12345',
                    'baz' => '',
                ],
            ]
        ];

        $inputFilter->setInputFilter($this->getBaseInputFilter());
        $inputFilter->setData($invalidCollectionData);

        $this->assertFalse($inputFilter->isValid());
        $this->assertCount(2, current($inputFilter->getInvalidInput()));
        $this->assertArrayHasKey('baz', current($inputFilter->getMessages()));
    }

    public function testNestedCollectionWithEmptyChild()
    {
        $items_inputfilter = new BaseInputFilter();
        $items_inputfilter->add(new Input(), 'id')
                          ->add(new Input(), 'type');
        $items = $this->createDefaultInputFilter();
        $items->setInputFilter($items_inputfilter);

        $groups_inputfilter = new BaseInputFilter();
        $groups_inputfilter->add(new Input(), 'group_class')
                           ->add($items, 'items');
        $groups = $this->createDefaultInputFilter();
        $groups->setInputFilter($groups_inputfilter);

        $inputFilter = new BaseInputFilter();
        $inputFilter->add($groups, 'groups');

        $preFilterdata = [
            'groups' => [
                [
                    'group_class' => 'bar',
                    'items' => [
                        [
                            'id' => 100,
                            'type' => 'item-1',
                        ],
                    ],
                ],
                [
                    'group_class' => 'bar',
                    'items' => [
                        [
                            'id' => 200,
                            'type' => 'item-2',
                        ],
                        [
                            'id' => 300,
                            'type' => 'item-3',
                        ],
                        [
                            'id' => 400,
                            'type' => 'item-4',
                        ],
                    ],
                ],
                [
                    'group_class' => 'biz',
                ],
            ],
        ];

        $postFilterdata = [
            'groups' => [
                [
                    'group_class' => 'bar',
                    'items' => [
                        [
                            'id' => 100,
                            'type' => 'item-1',
                        ],
                    ],
                ],
                [
                    'group_class' => 'bar',
                    'items' => [
                        [
                            'id' => 200,
                            'type' => 'item-2',
                        ],
                        [
                            'id' => 300,
                            'type' => 'item-3',
                        ],
                        [
                            'id' => 400,
                            'type' => 'item-4',
                        ],
                    ],
                ],
                [
                    'group_class' => 'biz',
                    'items' => [],
                ],
            ],
        ];

        $inputFilter->setData($preFilterdata);
        $inputFilter->isValid();
        $values = $inputFilter->getValues();
        $this->assertEquals($postFilterdata, $values);
    }

    public function testNestedCollectionWithEmptyData()
    {
        $items_inputfilter = new BaseInputFilter();
        $items_inputfilter->add(new Input(), 'id')
                          ->add(new Input(), 'type');
        $items = $this->createDefaultInputFilter();
        $items->setInputFilter($items_inputfilter);

        $groups_inputfilter = new BaseInputFilter();
        $groups_inputfilter->add(new Input(), 'group_class')
                           ->add($items, 'items');
        $groups = $this->createDefaultInputFilter();
        $groups->setInputFilter($groups_inputfilter);

        $inputFilter = new BaseInputFilter();
        $inputFilter->add($groups, 'groups');

        $data = [
            'groups' => [
                [
                    'group_class' => 'bar',
                    'items' => [
                        [
                            'id' => 100,
                            'type' => 'item-1',
                        ],
                    ],
                ],
                [
                    'group_class' => 'biz',
                    'items' => [],
                ],
                [
                    'group_class' => 'bar',
                    'items' => [
                        [
                            'id' => 200,
                            'type' => 'item-2',
                        ],
                        [
                            'id' => 300,
                            'type' => 'item-3',
                        ],
                        [
                            'id' => 400,
                            'type' => 'item-4',
                        ],
                    ],
                ],
            ],
        ];

        $inputFilter->setData($data);
        $inputFilter->isValid();
        $values = $inputFilter->getValues();
        $this->assertEquals($data, $values);
    }

    /**
     * @group 6472
     */
    public function testNestedCollectionWhereChildDataIsNotOverwritten()
    {
        $items_inputfilter = new BaseInputFilter();
        $items_inputfilter->add(new Input(), 'id')
                          ->add(new Input(), 'type');
        $items = $this->createDefaultInputFilter();
        $items->setInputFilter($items_inputfilter);

        $groups_inputfilter = new BaseInputFilter();
        $groups_inputfilter->add(new Input(), 'group_class')
                           ->add($items, 'items');
        $groups = $this->createDefaultInputFilter();
        $groups->setInputFilter($groups_inputfilter);

        $inputFilter = new BaseInputFilter();
        $inputFilter->add($groups, 'groups');

        $data = [
            'groups' => [
                [
                    'group_class' => 'bar',
                    'items' => [
                        [
                            'id' => 100,
                            'type' => 'item-100',
                        ],
                        [
                            'id' => 101,
                            'type' => 'item-101',
                        ],
                        [
                            'id' => 102,
                            'type' => 'item-102',
                        ],
                        [
                            'id' => 103,
                            'type' => 'item-103',
                        ],
                    ],
                ],
                [
                    'group_class' => 'foo',
                    'items' => [
                        [
                            'id' => 200,
                            'type' => 'item-200',
                        ],
                        [
                            'id' => 201,
                            'type' => 'item-201',
                        ],
                    ],
                ],
            ],
        ];

        $inputFilter->setData($data);
        $inputFilter->isValid();
        $values = $inputFilter->getValues();
        $this->assertEquals($data, $values);
    }

    public function dataNestingCollection()
    {
        return [
            'count not specified' => [
                'count' => null,
                'isValid' => true
            ],
            'count = 1' =>  [
                'count' => 1,
                'isValid' => true
            ],
            'count = 2' => [
                'count' => 2,
                'isValid' => false
            ],
            'count = 3' => [
                'count' => 3,
                'isValid' => false
            ]
        ];
    }

    /**
     * @dataProvider dataNestingCollection
     */
    public function testNestingCollectionCountCached($count, $expectedIsValid)
    {
        $firstInputFilter = new InputFilter();

        $firstCollection = $this->createDefaultInputFilter();
        $firstCollection->setInputFilter($firstInputFilter);

        $someInput = new Input('input');
        $secondInputFilter = new InputFilter();
        $secondInputFilter->add($someInput, 'input');

        $secondCollection = $this->createDefaultInputFilter();
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
                            'input' => 'some value'
                        ],
                        [
                            'input' => 'some value'
                        ]
                    ]
                ],
                [
                    'second_collection' => [
                        [
                            'input' => 'some value'
                        ],
                    ]
                ]
            ]
        ];

        $mainInputFilter->setData($data);
        $this->assertSame($expectedIsValid, $mainInputFilter->isValid());
    }

    public function testInvalidCollectionIsNotValid()
    {
        $inputFilter = $this->createDefaultInputFilter();

        $data = 1;

        $inputFilter->setInputFilter($this->getBaseInputFilter());
        $inputFilter->setData($data);

        $this->assertFalse($inputFilter->isValid());
    }

    protected function createDefaultInputFilter()
    {
        $inputFilter = new CollectionInputFilter();

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
