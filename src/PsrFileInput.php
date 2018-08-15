<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\InputFilter;

use Psr\Http\Message\UploadedFileInterface;
use Zend\Diactoros\UploadedFile;
use Zend\Validator\File\UploadFile as UploadValidator;

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
 *
 * 3. Instead of adding a NotEmpty validator, it will (by default) automatically add
 *    a Zend\Validator\File\Upload validator.
 */
class PsrFileInput extends Input
{
    /**
     * @var bool
     */
    protected $isValid = false;

    /**
     * @var bool
     */
    protected $autoPrependUploadValidator = true;

    /**
     * @param  bool $value Enable/Disable automatically prepending an Upload validator
     * @return PsrFileInput
     */
    public function setAutoPrependUploadValidator($value)
    {
        $this->autoPrependUploadValidator = $value;
        return $this;
    }

    /**
     * @return bool
     */
    public function getAutoPrependUploadValidator()
    {
        return $this->autoPrependUploadValidator;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        $value = $this->value;
        if ($this->isValid && $value instanceof UploadedFileInterface) {
            // Single file input

            // Run filters ~after~ validation, so that is_uploaded_file()
            // validation is not affected by filters.
            $filter = $this->getFilterChain();

            $value = $filter->filter($value);
        } elseif ($this->isValid && \is_array($value)) {
            // Multi file input (multiple attribute set)
            $filter = $this->getFilterChain();

            $newValue = [];
            foreach ($value as $fileData) {
                $newValue[] = $filter->filter($fileData);
            }
            $value = $newValue;
        }

        return $value;
    }

    /**
     * Checks if the raw input value is an empty file input eg: no file was uploaded
     *
     * @param UploadedFile|array $rawValue
     * @return bool
     */
    public function isEmptyFile($rawValue)
    {
        if (\is_array($rawValue) && $rawValue[0] instanceof UploadedFile) {
            return $this->isEmptyFile($rawValue[0]);
        }

        if ($rawValue instanceof UploadedFile && $rawValue->getError() === UPLOAD_ERR_NO_FILE) {
            return true;
        }

        return false;
    }

    /**
     * @param  mixed $context Extra "context" to provide the validator
     * @return bool
     */
    public function isValid($context = null)
    {
        $rawValue        = $this->getRawValue();
        $hasValue        = $this->hasValue();
        $empty           = $this->isEmptyFile($rawValue);
        $required        = $this->isRequired();
        $allowEmpty      = $this->allowEmpty();
        $continueIfEmpty = $this->continueIfEmpty();

        if (! $hasValue && ! $required) {
            return true;
        }

        if (! $hasValue && $required && ! $this->hasFallback()) {
            if ($this->errorMessage === null) {
                $this->errorMessage = $this->prepareRequiredValidationFailureMessage();
            }
            return false;
        }

        if ($empty && ! $required && ! $continueIfEmpty) {
            return true;
        }

        if ($empty && $allowEmpty && ! $continueIfEmpty) {
            return true;
        }

        $this->injectUploadValidator();
        $validator = $this->getValidatorChain();
        //$value   = $this->getValue(); // Do not run the filters yet for File uploads (see getValue())

        if ($rawValue instanceof UploadedFileInterface) {
            // Single file input
            $this->isValid = $validator->isValid($rawValue, $context);
        } elseif (\is_array($rawValue) && $rawValue[0] instanceof UploadedFileInterface) {
            // Multi file input (multiple attribute set)
            $this->isValid = true;
            foreach ($rawValue as $value) {
                if (! $validator->isValid($value, $context)) {
                    $this->isValid = false;
                    break; // Do not continue processing files if validation fails
                }
            }
        }

        return $this->isValid;
    }

    /**
     * @return void
     */
    protected function injectUploadValidator()
    {
        if (! $this->autoPrependUploadValidator) {
            return;
        }
        $chain = $this->getValidatorChain();

        // Check if Upload validator is already first in chain
        $validators = $chain->getValidators();
        if (isset($validators[0]['instance'])
            && $validators[0]['instance'] instanceof UploadValidator
        ) {
            $this->autoPrependUploadValidator = false;
            return;
        }

        $chain->prependByName('fileuploadfile', [], true);
        $this->autoPrependUploadValidator = false;
    }

    /**
     * @deprecated 2.4.8 See note on parent class. Removal does not affect this class.
     *
     * No-op, NotEmpty validator does not apply for PsrFileInputs.
     * See also: BaseInputFilter::isValid()
     *
     * @return void
     */
    protected function injectNotEmptyValidator()
    {
        $this->notEmptyValidator = true;
    }

    /**
     * @param  InputInterface $input
     * @return PsrFileInput
     */
    public function merge(InputInterface $input)
    {
        parent::merge($input);
        if ($input instanceof PsrFileInput) {
            $this->setAutoPrependUploadValidator($input->getAutoPrependUploadValidator());
        }
        return $this;
    }
}
