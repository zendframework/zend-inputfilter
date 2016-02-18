<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\InputFilter;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\InputFilter\Exception\RuntimeException;
use Zend\InputFilter\InputFilterPluginManager;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\Test\CommonPluginManagerTrait;

class MigrationTest extends TestCase
{
    use CommonPluginManagerTrait;

    public function testInstanceOfMatches()
    {
        $this->markTestSkipped("InputFilterPluginManager accepts multiple instances");
    }

    protected function getPluginManager()
    {
        return new InputFilterPluginManager(new ServiceManager());
    }

    protected function getV2InvalidPluginException()
    {
        return RuntimeException::class;
    }

    protected function getInstanceOf()
    {
        // InputFilterManager accepts multiple instance types
        return;
    }
}
