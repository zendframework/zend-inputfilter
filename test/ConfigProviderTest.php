<?php
/**
 * @link      http://github.com/zendframework/zend-inputfilter for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\InputFilter;

use PHPUnit\Framework\TestCase;
use Zend\InputFilter\ConfigProvider;
use Zend\InputFilter\InputFilterAbstractServiceFactory;
use Zend\InputFilter\InputFilterPluginManager;
use Zend\InputFilter\InputFilterPluginManagerFactory;

final class ConfigProviderTest extends TestCase
{
    public function testProvidesExpectedConfiguration()
    {
        $provider = new ConfigProvider();

        $expected = [
            'abstract_factories' => [
                InputFilterAbstractServiceFactory::class,
            ],
            'aliases' => [
                'InputFilterManager' => InputFilterPluginManager::class,
            ],
            'factories' => [
                InputFilterPluginManager::class => InputFilterPluginManagerFactory::class,
            ],
        ];

        $this->assertEquals($expected, $provider->getDependencyConfig());
    }

    public function testInvocationProvidesDependencyConfiguration()
    {
        $provider = new ConfigProvider();

        $expected = [
            'dependencies' => $provider->getDependencyConfig(),
        ];
        $this->assertEquals($expected, $provider());
    }
}
