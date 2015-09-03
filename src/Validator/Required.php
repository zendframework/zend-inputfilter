<?php

namespace Zend\InputFilter\Validator;

use Zend\InputFilter\Input;
use Zend\Validator\AbstractValidator;

class Required extends AbstractValidator
{
    const INVALID = 'inputInvalid';
    const REQUIRED = 'inputRequired';

    /**
     * @var string[]
     */
    protected $messageTemplates = [
        self::INVALID => 'Invalid type given. Zend\InputFilter\Input is required',
        self::REQUIRED => 'Value is required',
    ];

    /**
     * {@inheritDoc}
     */
    public function isValid($value)
    {
        if (!($value instanceof Input)) {
            $this->error(self::INVALID);
            return false;
        }

        $input = $value;

        if ($input->hasValue()) { // If has value then all is ok
            return true;
        }

        if ($input->isRequired()) { // It's Required and value was not set.
            $this->error(self::REQUIRED);
            return false;
        }

        return true;
    }
}
