<?php

namespace ZendTest\InputFilter\Validator;

use PHPUnit_Framework_MockObject_MockObject as MockObject;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\InputFilter\Input;
use Zend\InputFilter\Validator\Required;

/**
 * @covers Zend\InputFilter\Validator\Required
 */
class RequiredTest extends TestCase
{
    /**
     * @var Required
     */
    protected $validator;

    protected function setUp()
    {
        $this->validator = new Required();
    }

    /**
     * @dataProvider inputProvider
     */
    public function testValid($input, $expectedIsValid, $expectedMessages)
    {
        $this->assertEquals(
            $expectedIsValid,
            $this->validator->isValid($input),
            'isValid() value not match. Detail: ' . json_encode($this->validator->getMessages())
        );

        $this->assertEquals(
            $expectedMessages,
            $this->validator->getMessages(),
            'getMessages() value not match.'
        );
    }

    public function inputProvider()
    {
        $requiredMsg = [
            Required::REQUIRED => 'Value is required',
        ];
        $invalidMsg = [
            Required::INVALID => 'Invalid type given. Zend\InputFilter\Input is required',
        ];

        $required = true;
        $hasValue = true;

        // @codingStandardsIgnoreStart
        return [
            // Description => [$input, isValid, getMessages]
            'Invalid type'                => [new \stdClass()                               , false, $invalidMsg],
            'Required: T. Value: Set'     => [$this->createInputMock($required,   $hasValue), true , []],
            'Required: T. Value: Not set' => [$this->createInputMock($required,  !$hasValue), false, $requiredMsg],
            'Required: F. Value: set'     => [$this->createInputMock(!$required,  $hasValue), true , []],
            'Required: F. Value: Not set' => [$this->createInputMock(!$required, !$hasValue), true , []],
        ];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @param bool $required
     * @param bool $hasValue
     *
     * @return Input|MockObject
     */
    protected function createInputMock($required, $hasValue)
    {
        /** @var Input|MockObject $input */
        $input = $this->getMock(Input::class);

        $input->method('isRequired')
            ->willReturn($required)
        ;

        $input->method('hasValue')
            ->willReturn($hasValue)
        ;

        return $input;
    }
}
