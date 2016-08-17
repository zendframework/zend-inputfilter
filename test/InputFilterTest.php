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
use Zend\InputFilter\Exception\RuntimeException;
use Zend\InputFilter\Factory;
use Zend\InputFilter\Input;
use Zend\InputFilter\InputFilter;
use Zend\Validator\StringLength;

/**
 * @covers Zend\InputFilter\InputFilter
 */
class InputFilterTest extends BaseInputFilterTest
{
    /**
     * @var InputFilter
     */
    protected $inputFilter;

    public function setUp()
    {
        $this->inputFilter = new InputFilter();
    }

    public function testLazilyComposesAFactoryByDefault()
    {
        $factory = $this->inputFilter->getFactory();
        $this->assertInstanceOf(Factory::class, $factory);
    }

    public function testCanComposeAFactory()
    {
        $factory = $this->createFactoryMock();
        $this->inputFilter->setFactory($factory);
        $this->assertSame($factory, $this->inputFilter->getFactory());
    }

    public function inputProvider()
    {
        $dataSets = parent::inputProvider();

        $inputSpecificationAsArray = [
            'name' => 'inputFoo',
        ];
        $inputSpecificationAsTraversable = new ArrayIterator($inputSpecificationAsArray);

        $inputSpecificationResult = new Input('inputFoo');
        $inputSpecificationResult->getFilterChain(); // Fill input with a default chain just for make the test pass
        $inputSpecificationResult->getValidatorChain(); // Fill input with a default chain just for make the test pass

        // @codingStandardsIgnoreStart
        $inputFilterDataSets = [
            // Description => [input, expected name, $expectedReturnInput]
            'array' =>       [$inputSpecificationAsArray      , 'inputFoo', $inputSpecificationResult],
            'Traversable' => [$inputSpecificationAsTraversable, 'inputFoo', $inputSpecificationResult],
        ];
        // @codingStandardsIgnoreEnd
        $dataSets = array_merge($dataSets, $inputFilterDataSets);

        return $dataSets;
    }

    /**
     * @return Factory|MockObject
     */
    protected function createFactoryMock()
    {
        /** @var Factory|MockObject $factory */
        $factory = $this->getMock(Factory::class);

        return $factory;
    }

    public function testGetUnknownWhenDataAreNotProvidedThrowsRuntimeException()
    {
        $this->setExpectedException(RuntimeException::class);

        $this->inputFilter->getUnknown();
    }

    public function testGetUnknownWhenAllFieldsAreKnownReturnsAnEmptyArray()
    {
        $this->inputFilter->add([
            'name' => 'foo',
        ]);

        $this->inputFilter->setData(['foo' => 'bar']);

        $unknown = $this->inputFilter->getUnknown();

        $this->assertCount(0, $unknown);
    }

    public function testGetUnknownFieldIsUnknown()
    {
        $this->inputFilter->add([
            'name' => 'foo',
        ]);

        $this->inputFilter->setData(['foo' => 'bar', 'baz' => 'hey']);

        $unknown = $this->inputFilter->getUnknown();

        $this->assertCount(1, $unknown);
        $this->assertEquals(['baz' => 'hey'], $unknown);
    }

    public function testGetUnknownWhenDataAreNotValid()
    {
        $this->inputFilter->add([
            'name' => 'foo',
            'validators' => [
                [
                    'name' => StringLength::class,
                    'options' => [
                        'min' => 3,
                    ],
                ],
            ],
        ]);

        $this->inputFilter->setData(['foo' => 'a', 'bar' => 'baz']);

        $isValid = $this->inputFilter->isValid();
        $unknown = $this->inputFilter->getUnknown();

        $this->assertFalse($isValid);
        $this->assertCount(1, $unknown);
        $this->assertEquals(['bar' => 'baz'], $unknown);
    }
}
