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
use Zend\Filter;
use Zend\InputFilter\CollectionInputFilter;
use Zend\InputFilter\Factory;
use Zend\InputFilter\Input;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\InputFilterPluginManager;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\InputFilter\InputInterface;
use Zend\InputFilter\InputProviderInterface;
use Zend\ServiceManager;
use Zend\Validator;

/**
 * @covers Zend\InputFilter\Factory
 */
class FactoryTest extends TestCase
{
    public function testFactoryComposesFilterChainByDefault()
    {
        $factory = new Factory();
        $this->assertInstanceOf(Filter\FilterChain::class, $factory->getDefaultFilterChain());
    }

    public function testFactoryComposesValidatorChainByDefault()
    {
        $factory = new Factory();
        $this->assertInstanceOf(Validator\ValidatorChain::class, $factory->getDefaultValidatorChain());
    }

    public function testFactoryAllowsInjectingFilterChain()
    {
        $factory     = new Factory();
        $filterChain = new Filter\FilterChain();
        $factory->setDefaultFilterChain($filterChain);
        $this->assertSame($filterChain, $factory->getDefaultFilterChain());
    }

    public function testFactoryAllowsInjectingValidatorChain()
    {
        $factory        = new Factory();
        $validatorChain = new Validator\ValidatorChain();
        $factory->setDefaultValidatorChain($validatorChain);
        $this->assertSame($validatorChain, $factory->getDefaultValidatorChain());
    }

    public function testFactoryUsesComposedFilterChainWhenCreatingNewInputObjects()
    {
        $factory       = new Factory();
        $filterChain   = new Filter\FilterChain();
        $pluginManager = new Filter\FilterPluginManager();
        $filterChain->setPluginManager($pluginManager);
        $factory->setDefaultFilterChain($filterChain);
        $input = $factory->createInput([
            'name' => 'foo',
        ]);
        $this->assertInstanceOf(InputInterface::class, $input);
        $inputFilterChain = $input->getFilterChain();
        $this->assertNotSame($filterChain, $inputFilterChain);
        $this->assertSame($pluginManager, $inputFilterChain->getPluginManager());
    }

    public function testFactoryUsesComposedValidatorChainWhenCreatingNewInputObjects()
    {
        $factory          = new Factory();
        $validatorChain   = new Validator\ValidatorChain();
        $validatorPlugins = new Validator\ValidatorPluginManager();
        $validatorChain->setPluginManager($validatorPlugins);
        $factory->setDefaultValidatorChain($validatorChain);
        $input = $factory->createInput([
            'name' => 'foo',
        ]);
        $this->assertInstanceOf(InputInterface::class, $input);
        $inputValidatorChain = $input->getValidatorChain();
        $this->assertNotSame($validatorChain, $inputValidatorChain);
        $this->assertSame($validatorPlugins, $inputValidatorChain->getPluginManager());
    }

    public function testFactoryInjectsComposedFilterAndValidatorChainsIntoInputObjectsWhenCreatingNewInputFilterObjects()
    {
        $factory          = new Factory();
        $filterPlugins    = new Filter\FilterPluginManager();
        $validatorPlugins = new Validator\ValidatorPluginManager();
        $filterChain      = new Filter\FilterChain();
        $validatorChain   = new Validator\ValidatorChain();
        $filterChain->setPluginManager($filterPlugins);
        $validatorChain->setPluginManager($validatorPlugins);
        $factory->setDefaultFilterChain($filterChain);
        $factory->setDefaultValidatorChain($validatorChain);

        $inputFilter = $factory->createInputFilter([
            'foo' => [
                'name' => 'foo',
            ],
        ]);
        $this->assertInstanceOf(InputFilterInterface::class, $inputFilter);
        $this->assertEquals(1, count($inputFilter));
        $input = $inputFilter->get('foo');
        $this->assertInstanceOf(InputInterface::class, $input);
        $inputFilterChain    = $input->getFilterChain();
        $inputValidatorChain = $input->getValidatorChain();
        $this->assertSame($filterPlugins, $inputFilterChain->getPluginManager());
        $this->assertSame($validatorPlugins, $inputValidatorChain->getPluginManager());
    }

    public function testFactoryWillCreateInputWithSuggestedFilters()
    {
        $factory      = new Factory();
        $htmlEntities = new Filter\HtmlEntities();
        $input = $factory->createInput([
            'name'    => 'foo',
            'filters' => [
                [
                    'name' => 'string_trim',
                ],
                $htmlEntities,
                [
                    'name' => 'string_to_lower',
                    'options' => [
                        'encoding' => 'ISO-8859-1',
                    ],
                ],
            ],
        ]);
        $this->assertInstanceOf(InputInterface::class, $input);
        $this->assertEquals('foo', $input->getName());
        $chain = $input->getFilterChain();
        $index = 0;
        foreach ($chain as $filter) {
            switch ($index) {
                case 0:
                    $this->assertInstanceOf(Filter\StringTrim::class, $filter);
                    break;
                case 1:
                    $this->assertSame($htmlEntities, $filter);
                    break;
                case 2:
                    $this->assertInstanceOf(Filter\StringToLower::class, $filter);
                    $this->assertEquals('ISO-8859-1', $filter->getEncoding());
                    break;
                default:
                    $this->fail('Found more filters than expected');
            }
            $index++;
        }
    }

    public function testFactoryWillCreateInputWithSuggestedValidators()
    {
        $factory = new Factory();
        $digits  = new Validator\Digits();
        $input = $factory->createInput([
            'name'       => 'foo',
            'validators' => [
                [
                    'name' => 'not_empty',
                ],
                $digits,
                [
                    'name' => 'string_length',
                    'options' => [
                        'min' => 3,
                        'max' => 5,
                    ],
                ],
            ],
        ]);
        $this->assertInstanceOf(InputInterface::class, $input);
        $this->assertEquals('foo', $input->getName());
        $chain = $input->getValidatorChain();
        $index = 0;
        foreach ($chain as $validator) {
            switch ($index) {
                case 0:
                    $this->assertInstanceOf(Validator\NotEmpty::class, $validator);
                    break;
                case 1:
                    $this->assertSame($digits, $validator);
                    break;
                case 2:
                    $this->assertInstanceOf(Validator\StringLength::class, $validator);
                    $this->assertEquals(3, $validator->getMin());
                    $this->assertEquals(5, $validator->getMax());
                    break;
                default:
                    $this->fail('Found more validators than expected');
            }
            $index++;
        }
    }

    public function testFactoryWillCreateInputWithSuggestedRequiredFlagAndAlternativeAllowEmptyFlag()
    {
        $factory = new Factory();
        $input   = $factory->createInput([
            'name'     => 'foo',
            'required' => false,
            'allow_empty' => false,
        ]);
        $this->assertInstanceOf(InputInterface::class, $input);
        $this->assertFalse($input->isRequired());
        $this->assertFalse($input->allowEmpty());
    }

    public function testFactoryWillCreateInputWithSuggestedAllowEmptyFlagAndImpliesRequiredFlag()
    {
        $factory = new Factory();
        $input   = $factory->createInput([
            'name'        => 'foo',
            'allow_empty' => true,
        ]);
        $this->assertInstanceOf(InputInterface::class, $input);
        $this->assertTrue($input->allowEmpty());
        $this->assertFalse($input->isRequired());
    }

    public function testFactoryWillCreateInputWithSuggestedName()
    {
        $factory = new Factory();
        $input   = $factory->createInput([
            'name'        => 'foo',
        ]);
        $this->assertInstanceOf(InputInterface::class, $input);
        $this->assertEquals('foo', $input->getName());
    }

    public function testFactoryWillCreateInputWithContinueIfEmptyFlag()
    {
        $factory = new Factory();
        $input = $factory->createInput([
            'name'              => 'foo',
            'continue_if_empty' => true,
        ]);
        $this->assertInstanceOf(InputInterface::class, $input);
        $this->assertTrue($input->continueIfEmpty());
    }

    public function testFactoryAcceptsInputInterface()
    {
        $factory = new Factory();
        $input = new Input();

        $inputFilter = $factory->createInputFilter([
            'foo' => $input
        ]);

        $this->assertInstanceOf(InputFilterInterface::class, $inputFilter);
        $this->assertTrue($inputFilter->has('foo'));
        $this->assertEquals($input, $inputFilter->get('foo'));
    }

    public function testFactoryAcceptsInputFilterInterface()
    {
        $factory = new Factory();
        $input = new InputFilter();

        $inputFilter = $factory->createInputFilter([
            'foo' => $input
        ]);

        $this->assertInstanceOf(InputFilterInterface::class, $inputFilter);
        $this->assertTrue($inputFilter->has('foo'));
        $this->assertEquals($input, $inputFilter->get('foo'));
    }

    public function testFactoryWillCreateInputFilterAndAllInputObjectsFromGivenConfiguration()
    {
        $factory     = new Factory();
        $inputFilter = $factory->createInputFilter([
            'foo' => [
                'name'       => 'foo',
                'required'   => false,
                'validators' => [
                    [
                        'name' => 'not_empty',
                    ],
                    [
                        'name' => 'string_length',
                        'options' => [
                            'min' => 3,
                            'max' => 5,
                        ],
                    ],
                ],
            ],
            'bar' => [
                'allow_empty' => true,
                'filters'     => [
                    [
                        'name' => 'string_trim',
                    ],
                    [
                        'name' => 'string_to_lower',
                        'options' => [
                            'encoding' => 'ISO-8859-1',
                        ],
                    ],
                ],
            ],
            'baz' => [
                'type'   => InputFilter::class,
                'foo' => [
                    'name'       => 'foo',
                    'required'   => false,
                    'validators' => [
                        [
                            'name' => 'not_empty',
                        ],
                        [
                            'name' => 'string_length',
                            'options' => [
                                'min' => 3,
                                'max' => 5,
                            ],
                        ],
                    ],
                ],
                'bar' => [
                    'allow_empty' => true,
                    'filters'     => [
                        [
                            'name' => 'string_trim',
                        ],
                        [
                            'name' => 'string_to_lower',
                            'options' => [
                                'encoding' => 'ISO-8859-1',
                            ],
                        ],
                    ],
                ],
            ],
            'bat' => [
                'type' => 'ZendTest\InputFilter\TestAsset\CustomInput',
                'name' => 'bat',
            ],
            'zomg' => [
                'name' => 'zomg',
                'continue_if_empty' => true,
            ],
        ]);
        $this->assertInstanceOf(InputFilter::class, $inputFilter);
        $this->assertEquals(5, count($inputFilter));

        foreach (['foo', 'bar', 'baz', 'bat', 'zomg'] as $name) {
            $input = $inputFilter->get($name);

            switch ($name) {
                case 'foo':
                    $this->assertInstanceOf(Input::class, $input);
                    $this->assertFalse($input->isRequired());
                    $this->assertEquals(2, count($input->getValidatorChain()));
                    break;
                case 'bar':
                    $this->assertInstanceOf(Input::class, $input);
                    $this->assertTrue($input->allowEmpty());
                    $this->assertEquals(2, count($input->getFilterChain()));
                    break;
                case 'baz':
                    $this->assertInstanceOf(InputFilter::class, $input);
                    $this->assertEquals(2, count($input));
                    $foo = $input->get('foo');
                    $this->assertInstanceOf(Input::class, $foo);
                    $this->assertFalse($foo->isRequired());
                    $this->assertEquals(2, count($foo->getValidatorChain()));
                    $bar = $input->get('bar');
                    $this->assertInstanceOf(Input::class, $bar);
                    $this->assertTrue($bar->allowEmpty());
                    $this->assertEquals(2, count($bar->getFilterChain()));
                    break;
                case 'bat':
                    $this->assertInstanceOf('ZendTest\InputFilter\TestAsset\CustomInput', $input);
                    $this->assertEquals('bat', $input->getName());
                    break;
                case 'zomg':
                    $this->assertInstanceOf(Input::class, $input);
                    $this->assertTrue($input->continueIfEmpty());
            }
        }
    }

    public function testFactoryWillCreateInputFilterMatchingInputNameWhenNotSpecified()
    {
        $factory     = new Factory();
        $inputFilter = $factory->createInputFilter([
            ['name' => 'foo']
        ]);

        $this->assertTrue($inputFilter->has('foo'));
        $this->assertInstanceOf(Input::class, $inputFilter->get('foo'));
    }

    public function testFactoryAllowsPassingValidatorChainsInInputSpec()
    {
        $factory = new Factory();
        $chain   = new Validator\ValidatorChain();
        $input   = $factory->createInput([
            'name'       => 'foo',
            'validators' => $chain,
        ]);
        $this->assertInstanceOf(InputInterface::class, $input);
        $test = $input->getValidatorChain();
        $this->assertSame($chain, $test);
    }

    public function testFactoryAllowsPassingFilterChainsInInputSpec()
    {
        $factory = new Factory();
        $chain   = new Filter\FilterChain();
        $input   = $factory->createInput([
            'name'    => 'foo',
            'filters' => $chain,
        ]);
        $this->assertInstanceOf(InputInterface::class, $input);
        $test = $input->getFilterChain();
        $this->assertSame($chain, $test);
    }

    public function testFactoryAcceptsCollectionInputFilter()
    {
        $factory = new Factory();

        $inputFilter = $factory->createInputFilter([
            'type'        => CollectionInputFilter::class,
            'required'    => true,
            'inputfilter' => new InputFilter(),
            'count'       => 3,
        ]);

        $this->assertInstanceOf(CollectionInputFilter::class, $inputFilter);
        $this->assertInstanceOf(InputFilter::class, $inputFilter->getInputFilter());
        $this->assertTrue($inputFilter->getIsRequired());
        $this->assertEquals(3, $inputFilter->getCount());
    }

    public function testFactoryWillCreateInputWithErrorMessage()
    {
        $factory = new Factory();
        $input   = $factory->createInput([
            'name'          => 'foo',
            'error_message' => 'My custom error message',
        ]);
        $this->assertEquals('My custom error message', $input->getErrorMessage());
    }

    public function testFactoryWillNotGetPrioritySetting()
    {
        //Reminder: Priority at which to enqueue filter; defaults to 1000 (higher executes earlier)
        $factory = new Factory();
        $input   = $factory->createInput([
            'name'    => 'foo',
            'filters' => [
                [
                    'name'      => 'string_trim',
                    'priority'  => \Zend\Filter\FilterChain::DEFAULT_PRIORITY - 1 // 999
                ],
                [
                    'name'      => 'string_to_upper',
                    'priority'  => \Zend\Filter\FilterChain::DEFAULT_PRIORITY + 1 //1001
                ],
                [
                    'name'      => 'string_to_lower', // default priority 1000
                ]
            ]
        ]);

        // We should have 3 filters
        $this->assertEquals(3, $input->getFilterChain()->count());

        // Filters should pop in the following order:
        // string_to_upper (1001), string_to_lower (1000), string_trim (999)
        $index = 0;
        foreach ($input->getFilterChain()->getFilters() as $filter) {
            switch ($index) {
                case 0:
                    $this->assertInstanceOf(Filter\StringToUpper::class, $filter);
                    break;
                case 1:
                    $this->assertInstanceOf(Filter\StringToLower::class, $filter);
                    break;
                case 2:
                    $this->assertInstanceOf(Filter\StringTrim::class, $filter);
                    break;
            }
            $index++;
        }
    }

    public function testConflictNameWithInputFilterType()
    {
        $factory = new Factory();

        $inputFilter = $factory->createInputFilter(
            [
                'type' => [
                    'required' => true
                ]
            ]
        );

        $this->assertInstanceOf(InputFilter::class, $inputFilter);
        $this->assertTrue($inputFilter->has('type'));
    }

    public function testCustomFactoryInCollection()
    {
        $factory = new TestAsset\CustomFactory();
        $inputFilter = $factory->createInputFilter([
            'type'        => 'collection',
            'input_filter' => new InputFilter(),
        ]);
        $this->assertInstanceOf(TestAsset\CustomFactory::class, $inputFilter->getFactory());
    }

    /**
     * @group 4838
     */
    public function testCanSetInputErrorMessage()
    {
        $factory = new Factory();
        $input   = $factory->createInput([
            'name'          => 'test',
            'type'          => Input::class,
            'error_message' => 'Custom error message',
        ]);
        $this->assertEquals('Custom error message', $input->getErrorMessage());
    }

    public function testSetInputFilterManagerWithServiceManager()
    {
        $inputFilterManager = new InputFilterPluginManager;
        $serviceManager = new ServiceManager\ServiceManager;
        $serviceManager->setService('ValidatorManager', new Validator\ValidatorPluginManager);
        $serviceManager->setService('FilterManager', new Filter\FilterPluginManager);
        $inputFilterManager->setServiceLocator($serviceManager);
        $factory = new Factory();
        $factory->setInputFilterManager($inputFilterManager);
        $this->assertInstanceOf(
            Validator\ValidatorPluginManager::class,
            $factory->getDefaultValidatorChain()->getPluginManager()
        );
        $this->assertInstanceOf(
            Filter\FilterPluginManager::class,
            $factory->getDefaultFilterChain()->getPluginManager()
        );
    }

    public function testSetInputFilterManagerWithoutServiceManager()
    {
        $inputFilterManager = new InputFilterPluginManager();
        $factory = new Factory();
        $factory->setInputFilterManager($inputFilterManager);
        $this->assertSame($inputFilterManager, $factory->getInputFilterManager());
    }

    public function testSetInputFilterManagerOnConstruct()
    {
        $inputFilterManager = new InputFilterPluginManager();
        $factory = new Factory($inputFilterManager);
        $this->assertSame($inputFilterManager, $factory->getInputFilterManager());
    }

    /**
     * @group 5691
     *
     * @covers \Zend\InputFilter\Factory::createInput
     */
    public function testSetsBreakChainOnFailure()
    {
        $factory = new Factory();

        $this->assertTrue($factory->createInput(['break_on_failure' => true])->breakOnFailure());

        $this->assertFalse($factory->createInput(['break_on_failure' => false])->breakOnFailure());
    }

    public function testCanCreateInputFilterWithNullInputs()
    {
        $factory = new Factory();

        $inputFilter = $factory->createInputFilter([
            'foo' => [
                'name' => 'foo',
            ],
            'bar' => null,
            'baz' => [
                'name' => 'baz',
            ],
        ]);

        $this->assertInstanceOf(InputFilter::class, $inputFilter);
        $this->assertEquals(2, count($inputFilter));
        $this->assertTrue($inputFilter->has('foo'));
        $this->assertFalse($inputFilter->has('bar'));
        $this->assertTrue($inputFilter->has('baz'));
    }

    /**
     * @group 7010
     */
    public function testCanCreateInputFromProvider()
    {
        /** @var InputProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->getMock(InputProviderInterface::class, ['getInputSpecification']);

        $provider
            ->expects($this->any())
            ->method('getInputSpecification')
            ->will($this->returnValue(['name' => 'foo']));

        $factory = new Factory();
        $input   = $factory->createInput($provider);

        $this->assertInstanceOf(InputInterface::class, $input);
    }

    /**
     * @group 7010
     */
    public function testCanCreateInputFilterFromProvider()
    {
        /** @var InputFilterProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->getMock(
            InputFilterProviderInterface::class,
            ['getInputFilterSpecification']
        );
        $provider
            ->expects($this->any())
            ->method('getInputFilterSpecification')
            ->will($this->returnValue([
                'foo' => [
                    'name'       => 'foo',
                    'required'   => false,
                ],
                'baz' => [
                    'name'       => 'baz',
                    'required'   => true,
                ],
            ]));

        $factory     = new Factory();
        $inputFilter = $factory->createInputFilter($provider);

        $this->assertInstanceOf(InputFilterInterface::class, $inputFilter);
    }

    public function testSuggestedTypeMayBePluginNameInInputFilterPluginManager()
    {
        $factory = new Factory();
        $pluginManager = new InputFilterPluginManager();
        $pluginManager->setService('bar', new Input('bar'));
        $factory->setInputFilterManager($pluginManager);

        $input = $factory->createInput([
            'type' => 'bar'
        ]);
        $this->assertSame('bar', $input->getName());

        $this->setExpectedException(Filter\Exception\RuntimeException::class);
        $factory->createInput([
            'type' => 'foo'
        ]);
    }

    public function testInputFromPluginManagerMayBeFurtherConfiguredWithSpec()
    {
        $factory = new Factory();
        $pluginManager = new InputFilterPluginManager();
        $pluginManager->setService('bar', $barInput = new Input('bar'));
        $this->assertTrue($barInput->isRequired());
        $factory->setInputFilterManager($pluginManager);

        $input = $factory->createInput([
            'type' => 'bar',
            'required' => false
        ]);

        $this->assertFalse($input->isRequired());
        $this->assertSame('bar', $input->getName());
    }
}
