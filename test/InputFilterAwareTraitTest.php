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
use Zend\InputFilter\InputFilterAwareTrait;

/**
 * @requires PHP 5.4
 * @covers Zend\InputFilter\InputFilterAwareTrait
 */
class InputFilterAwareTraitTest extends TestCase
{
    use InputFilterAwareInterfaceTestTrait;

    public function testImplementsInputFilterAwareInterface()
    {
        $this->markTestSkipped("Traits don't implement interfaces");
    }

    protected function createDefaultInputFilterAware()
    {
        $trait = $this->getObjectForTrait(InputFilterAwareTrait::class);

        return $trait;
    }
}
