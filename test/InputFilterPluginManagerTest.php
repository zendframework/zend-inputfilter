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
use Zend\InputFilter\Exception\RuntimeException;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\InputFilterPluginManager;
use Zend\InputFilter\InputInterface;
use Zend\ServiceManager\AbstractPluginManager;

/**
 * @covers Zend\InputFilter\InputFilterPluginManager
 */
class InputFilterPluginManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var InputFilterPluginManager
     */
    protected $manager;

    public function setUp()
    {
        $this->manager = new InputFilterPluginManager();
    }

    public function testIsASubclassOfAbstractPluginManager()
    {
        $this->assertInstanceOf(AbstractPluginManager::class, $this->manager);
    }

    public function testRegisteringInvalidElementRaisesException()
    {
        $this->setExpectedException(RuntimeException::class);
        $this->manager->setService('test', $this);
    }

    public function testLoadingInvalidElementRaisesException()
    {
        $this->manager->setInvokableClass('test', get_class($this));
        $this->setExpectedException(
            RuntimeException::class,
            'must implement Zend\InputFilter\InputFilterInterface or Zend\InputFilter\InputInterface'
        );
        $this->manager->get('test');
    }

    /**
     * @covers Zend\InputFilter\InputFilterPluginManager::validatePlugin
     */
    public function testAllowLoadingInstancesOfInputFilterInterface()
    {
        /** @var InputFilterInterface|MockObject $inputFilter */
        $inputFilter = $this->getMock(InputFilterInterface::class);

        $this->assertNull($this->manager->validatePlugin($inputFilter));
    }

    /**
     * @covers Zend\InputFilter\InputFilterPluginManager::validatePlugin
     */
    public function testAllowLoadingInstancesOfInputInterface()
    {
        /** @var InputInterface|MockObject $input */
        $input = $this->getMock(InputInterface::class);

        $this->assertNull($this->manager->validatePlugin($input));
    }
}
