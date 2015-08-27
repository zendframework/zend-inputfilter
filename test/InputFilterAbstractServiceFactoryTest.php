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
use Zend\Filter\FilterPluginManager;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterPluginManager;
use Zend\ServiceManager\ServiceManager;
use Zend\Validator\ValidatorPluginManager;
use Zend\InputFilter\InputFilterAbstractServiceFactory;

class InputFilterAbstractServiceFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->services = new ServiceManager();
        $this->filters  = new InputFilterPluginManager();
        $this->filters->setServiceLocator($this->services);
        $this->services->setService('InputFilterManager', $this->filters);

        $this->factory = new InputFilterAbstractServiceFactory();
    }

    public function testCannotCreateServiceIfNoConfigServicePresent()
    {
        $this->assertFalse($this->factory->canCreateServiceWithName($this->filters, 'filter', 'filter'));
    }

    public function testCannotCreateServiceIfConfigServiceDoesNotHaveInputFiltersConfiguration()
    {
        $this->services->setService('Config', []);
        $this->assertFalse($this->factory->canCreateServiceWithName($this->filters, 'filter', 'filter'));
    }

    public function testCannotCreateServiceIfConfigInputFiltersDoesNotContainMatchingServiceName()
    {
        $this->services->setService('Config', [
            'input_filter_specs' => [],
        ]);
        $this->assertFalse($this->factory->canCreateServiceWithName($this->filters, 'filter', 'filter'));
    }

    public function testCanCreateServiceIfConfigInputFiltersContainsMatchingServiceName()
    {
        $this->services->setService('Config', [
            'input_filter_specs' => [
                'filter' => [],
            ],
        ]);
        $this->assertTrue($this->factory->canCreateServiceWithName($this->filters, 'filter', 'filter'));
    }

    public function testCreatesInputFilterInstance()
    {
        $this->services->setService('Config', [
            'input_filter_specs' => [
                'filter' => [],
            ],
        ]);
        $filter = $this->factory->createServiceWithName($this->filters, 'filter', 'filter');
        $this->assertInstanceOf('Zend\InputFilter\InputFilterInterface', $filter);
    }

    /**
     * @depends testCreatesInputFilterInstance
     */
    public function testUsesConfiguredValidationAndFilterManagerServicesWhenCreatingInputFilter()
    {
        $filters = new FilterPluginManager();
        $filter  = function ($value) {
        };
        $filters->setService('foo', $filter);

        $validators = new ValidatorPluginManager();
        $validator  = $this->getMock('Zend\Validator\ValidatorInterface');
        $validators->setService('foo', $validator);

        $this->services->setService('FilterManager', $filters);
        $this->services->setService('ValidatorManager', $validators);
        $this->services->setService('Config', [
            'input_filter_specs' => [
                'filter' => [
                    'input' => [
                        'name' => 'input',
                        'required' => true,
                        'filters' => [
                            [ 'name' => 'foo' ],
                        ],
                        'validators' => [
                            [ 'name' => 'foo' ],
                        ],
                    ],
                ],
            ],
        ]);

        $inputFilter = $this->factory->createServiceWithName($this->filters, 'filter', 'filter');
        $this->assertTrue($inputFilter->has('input'));

        $input = $inputFilter->get('input');

        $filterChain = $input->getFilterChain();
        $this->assertSame($filters, $filterChain->getPluginManager());
        $this->assertEquals(1, count($filterChain));
        $this->assertSame($filter, $filterChain->plugin('foo'));
        $this->assertEquals(1, count($filterChain));

        $validatorChain = $input->getvalidatorChain();
        $this->assertSame($validators, $validatorChain->getPluginManager());
        $this->assertEquals(1, count($validatorChain));
        $this->assertSame($validator, $validatorChain->plugin('foo'));
        $this->assertEquals(1, count($validatorChain));
    }

    public function testRetrieveInputFilterFromInputFilterPluginManager()
    {
        $filters = new FilterPluginManager();
        $filter  = function ($value) {
        };
        $filters->setService('foo', $filter);

        $validators = new ValidatorPluginManager();
        $validator  = $this->getMock('Zend\Validator\ValidatorInterface');
        $validators->setService('foo', $validator);

        $this->services->setService('FilterManager', $filters);
        $this->services->setService('ValidatorManager', $validators);
        $this->services->setService('Config', [
            'input_filter_specs' => [
                'foobar' => [
                    'input' => [
                        'name' => 'input',
                        'required' => true,
                        'filters' => [
                            [ 'name' => 'foo' ],
                        ],
                        'validators' => [
                            [ 'name' => 'foo' ],
                        ],
                    ],
                ],
            ],
        ]);
        $this->services->get('InputFilterManager')->addAbstractFactory('Zend\InputFilter\InputFilterAbstractServiceFactory');

        $inputFilter = $this->services->get('InputFilterManager')->get('foobar');
        $this->assertInstanceOf('Zend\InputFilter\InputFilterInterface', $inputFilter);
    }


    /**
     * @depends testCreatesInputFilterInstance
     */
    public function testInjectsInputFilterManagerFromServiceManager()
    {
        $this->services->setService('Config', array(
            'input_filter_specs' => array(
                'filter' => array(),
            ),
        ));
        $this->filters->addAbstractFactory('ZendTest\InputFilter\TestAsset\FooAbstractFactory');

        /**
         * @type InputFilter $filter
         */
        $filter = $this->factory->createServiceWithName($this->filters, 'filter', 'filter');

        $inputFilterManager = $filter->getFactory()->getInputFilterManager();

        $this->assertInstanceOf('Zend\InputFilter\InputFilterPluginManager', $inputFilterManager);

        $this->assertInstanceOf('ZendTest\InputFilter\TestAsset\Foo', $inputFilterManager->get('foo'));
    }

}
