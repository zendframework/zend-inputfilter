<?php
/**
 * @link      http://github.com/zendframework/zend-inputfilter for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\InputFilter;

use Interop\Container\ContainerInterface;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\InputFilterPluginManager;
use Zend\InputFilter\InputFilterPluginManagerFactory;
use Zend\InputFilter\InputInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class InputFilterPluginManagerFactoryTest extends TestCase
{
    public function testFactoryReturnsPluginManager()
    {
        $container = $this->prophesize(ContainerInterface::class)->reveal();
        $factory = new InputFilterPluginManagerFactory();

        $filters = $factory($container, InputFilterPluginManagerFactory::class);
        $this->assertInstanceOf(InputFilterPluginManager::class, $filters);

        if (method_exists($filters, 'configure')) {
            // zend-servicemanager v3
            $this->assertAttributeSame($container, 'creationContext', $filters);
        } else {
            // zend-servicemanager v2
            $this->assertSame($container, $filters->getServiceLocator());
        }
    }

    public function pluginProvider()
    {
        return [
            'input' => [InputInterface::class],
            'input-filter' => [InputFilterInterface::class],
        ];
    }

    /**
     * @depends testFactoryReturnsPluginManager
     * @dataProvider pluginProvider
     */
    public function testFactoryConfiguresPluginManagerUnderContainerInterop($pluginType)
    {
        $container = $this->prophesize(ContainerInterface::class)->reveal();
        $plugin = $this->prophesize($pluginType)->reveal();

        $factory = new InputFilterPluginManagerFactory();
        $filters = $factory($container, InputFilterPluginManagerFactory::class, [
            'services' => [
                'test' => $plugin,
            ],
        ]);
        $this->assertSame($plugin, $filters->get('test'));
    }

    /**
     * @depends testFactoryReturnsPluginManager
     * @dataProvider pluginProvider
     */
    public function testFactoryConfiguresPluginManagerUnderServiceManagerV2($pluginType)
    {
        $container = $this->prophesize(ServiceLocatorInterface::class);
        $container->willImplement(ContainerInterface::class);

        $plugin = $this->prophesize($pluginType)->reveal();

        $factory = new InputFilterPluginManagerFactory();
        $factory->setCreationOptions([
            'services' => [
                'test' => $plugin,
            ],
        ]);

        $filters = $factory->createService($container->reveal());
        $this->assertSame($plugin, $filters->get('test'));
    }
}
