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
use Zend\InputFilter\BaseInputFilter;
use Zend\InputFilter\CollectionInputFilter;
use Zend\InputFilter\Factory;
use Zend\InputFilter\Input;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputInterface;

/**
 * @covers Zend\InputFilter\InputFilter
 */
class InputFilterTest extends BaseInputFilterTest
{
    public function testIsASubclassOfBaseInputFilter()
    {
        $this->assertInstanceOf(BaseInputFilter::class, $this->createDefaultInputFilter());
    }

    public function testLazilyComposesAFactoryByDefault()
    {
        $inputFilter = $this->createDefaultInputFilter();

        $factory = $inputFilter->getFactory();
        $this->assertInstanceOf(Factory::class, $factory);
    }

    public function testCanComposeAFactory()
    {
        $inputFilter = $this->createDefaultInputFilter();

        $factory = new Factory();
        $inputFilter->setFactory($factory);
        $this->assertSame($factory, $inputFilter->getFactory());
    }

    public function testCanAddUsingSpecification()
    {
        $inputFilter = $this->createDefaultInputFilter();

        $inputFilter->add([
            'name' => 'foo',
        ]);
        $this->assertTrue($inputFilter->has('foo'));
        $foo = $inputFilter->get('foo');
        $this->assertInstanceOf(InputInterface::class, $foo);
    }

    /**
     * @covers \Zend\InputFilter\BaseInputFilter::getValue
     *
     * @group 6028
     */
    public function testGetValueReturnsArrayIfNestedInputFilters()
    {
        $inputFilter = $this->createDefaultInputFilter();

        $nestedInputFilter = $this->createDefaultInputFilter();
        $nestedInputFilter->add(new Input(), 'name');

        $inputFilter->add($nestedInputFilter, 'people');

        $data = [
            'people' => [
                 'name' => 'Wanderson'
            ]
        ];

        $inputFilter->setData($data);
        $this->assertTrue($inputFilter->isValid());

        $this->assertInternalType('array', $inputFilter->getValue('people'));
    }

    /**
     * @group ZF2-5648
     */
    public function testCountZeroValidateInternalInputWithCollectionInputFilter()
    {
        $inputFilter = $this->createDefaultInputFilter();

        $nestedInputFilter = $this->createDefaultInputFilter();
        $nestedInputFilter->add(new Input(), 'name');

        $collection = new CollectionInputFilter();
        $collection->setInputFilter($nestedInputFilter);
        $collection->setCount(0);

        $inputFilter->add($collection, 'people');

        $data = [
            'people' => [
                [
                    'name' => 'Wanderson',
                ],
            ],
        ];
        $inputFilter->setData($data);

        $this->assertTrue($inputFilter->isvalid());
        $this->assertSame($data, $inputFilter->getValues());
    }

    public function testCanUseContextPassedToInputFilter()
    {
        $inputFilter = $this->createDefaultInputFilter();

        $context = new \stdClass();

        /** @var InputInterface|MockObject $input */
        $input = $this->getMock(InputInterface::class);
        $input->expects($this->once())->method('isValid')->with($context)->will($this->returnValue(true));
        $input->expects($this->any())->method('getRawValue')->will($this->returnValue('Mwop'));

        $inputFilter->add($input, 'username');
        $inputFilter->setData(['username' => 'Mwop']);

        $inputFilter->isValid($context);
    }

    protected function createDefaultInputFilter()
    {
        $inputFilter = new InputFilter();

        return $inputFilter;
    }
}
