<?php
/**
 * @link      http://github.com/zendframework/zend-inputfilter for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\InputFilter;

use Zend\InputFilter\ConfigProvider;
use Zend\InputFilter\InputFilterAbstractServiceFactory;
use Zend\InputFilter\InputFilterPluginManager;
use Zend\InputFilter\InputFilterPluginManagerFactory;

class ConfigProviderTest extends \PHPUnit_Framework_TestCase
{
    private $config = [
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

    public function testProvidesExpectedConfiguration()
    {
        $provider = new ConfigProvider();
        $this->assertEquals($this->config, $provider->getDependencyConfig());
        return $provider;
    }

    /**
     * @depends testProvidesExpectedConfiguration
     */
    public function testInvocationProvidesDependencyConfiguration(ConfigProvider $provider)
    {
        $this->assertEquals(['dependencies' => $provider->getDependencyConfig()], $provider());
    }
}
