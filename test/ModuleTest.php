<?php
/**
 * @link      https://github.com/zendframework/zend-inputfilter for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://framework.zend.com/license New BSD License
 */

namespace ZendTest\InputFilter;

use Interop\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use Zend\InputFilter\InputFilterAbstractServiceFactory;
use Zend\InputFilter\InputFilterPluginManager;
use Zend\InputFilter\InputFilterPluginManagerFactory;
use Zend\InputFilter\Module;

class ModuleTest extends TestCase
{
    /** @var Module */
    private $module;

    public function setUp()
    {
        $this->module = new Module();
    }

    public function testGetConfigMethodShouldReturnExpectedKeys()
    {
        $config = $this->module->getConfig();

        $this->assertInternalType('array', $config);

        // Service manager
        $this->assertArrayHasKey('service_manager', $config);

        // Input filters
        $this->assertArrayHasKey('input_filters', $config);
    }

    public function testGetConfigMethodShouldReturnExpectedValues()
    {
        $config = $this->module->getConfig();

        // Service manager
        $this->assertSame(
            [
                'aliases'   => [
                    'InputFilterManager' => InputFilterPluginManager::class,
                ],
                'factories' => [
                    InputFilterPluginManager::class => InputFilterPluginManagerFactory::class,
                ],
            ],
            $config['service_manager']
        );

        // Input filters
        $this->assertSame(
            [
                'abstract_factories' => [
                    InputFilterAbstractServiceFactory::class,
                ],
            ],
            $config['input_filters']
        );
    }

    public function testInitMethodShouldRegisterPluginManagerSpecificationWithServiceListener(
    )
    {
        // Service listener
        $serviceListener = $this->prophesize(
            TestAsset\ServiceListenerInterface::class
        );
        $serviceListener->addServiceManager(
            'InputFilterManager',
            'input_filters',
            'Zend\ModuleManager\Feature\InputFilterProviderInterface',
            'getInputFilterConfig'
        )->shouldBeCalled();

        // Container
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('ServiceListener')->willReturn(
            $serviceListener->reveal()
        );

        // Event
        $event = $this->prophesize(TestAsset\ModuleEventInterface::class);
        $event->getParam('ServiceManager')->willReturn($container->reveal());

        // Module manager
        $moduleManager = $this->prophesize(
            TestAsset\ModuleManagerInterface::class
        );
        $moduleManager->getEvent()->willReturn($event->reveal());

        $this->assertNull($this->module->init($moduleManager->reveal()));
    }
}
