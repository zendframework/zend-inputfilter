<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\InputFilter;

/**
 * FileInput is a special Input type for handling uploaded files.
 *
 * It differs from Input in a few ways:
 *
 * 1. It expects the raw value to be in the $_FILES array format.
 *
 * 2. The validators are run **before** the filters (the opposite behavior of Input).
 *    This is so is_uploaded_file() validation can be run prior to any filters that
 *    may rename/move/modify the file.
 */
class FileInput extends Input
{
    /**
     * @var bool
     */
    protected $isValid = false;

    /**
     * @return mixed
     */
    public function getValue()
    {
        $value = $this->value;
        if ($this->isValid && is_array($value)) {
            // Run filters ~after~ validation, so that is_uploaded_file()
            // validation is not affected by filters.
            $filter = $this->getFilterChain();
            if (isset($value['tmp_name'])) {
                // Single file input
                $value = $filter->filter($value);
            } else {
                // Multi file input (multiple attribute set)
                $newValue = [];
                foreach ($value as $fileData) {
                    if (is_array($fileData) && isset($fileData['tmp_name'])) {
                        $newValue[] = $filter->filter($fileData);
                    }
                }
                $value = $newValue;
            }
        }

        return $value;
    }

    /**
     * @param  mixed $context Extra "context" to provide the validator
     * @return bool
     */
    public function isValid($context = null)
    {
        $rawValue        = $this->getRawValue();
        $hasValue        = $this->hasValue();
        $required        = $this->isRequired();

        if (! $hasValue && ! $required) {
            return true;
        }

        if (! $hasValue && $required && ! $this->hasFallback()) {
            if ($this->errorMessage === null) {
                $this->errorMessage = $this->prepareRequiredValidationFailureMessage();
            }
            return false;
        }

        $validator = $this->getValidatorChain();
        //$value   = $this->getValue(); // Do not run the filters yet for File uploads (see getValue())

        if (!is_array($rawValue)) {
            // This can happen in an AJAX POST, where the input comes across as a string
            $rawValue = [
                'tmp_name' => $rawValue,
                'name'     => $rawValue,
                'size'     => 0,
                'type'     => '',
                'error'    => UPLOAD_ERR_NO_FILE,
            ];
        }
        if (is_array($rawValue) && isset($rawValue['tmp_name'])) {
            // Single file input
            $this->isValid = $validator->isValid($rawValue, $context);
        } elseif (is_array($rawValue) && !empty($rawValue) && isset($rawValue[0]['tmp_name'])) {
            // Multi file input (multiple attribute set)
            $this->isValid = true;
            foreach ($rawValue as $value) {
                if (!$validator->isValid($value, $context)) {
                    $this->isValid = false;
                    break; // Do not continue processing files if validation fails
                }
            }
        }

        return $this->isValid;
    }
}
