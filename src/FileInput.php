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
class FileInput extends Input
{
    /**
     * @var bool
     */
    protected $isValid = false;

    /**
     * @var bool
     */
    protected $autoPrependUploadValidator = true;

    /** @var FileInputDecoratorInterface */
    private $filterImpl;

    /**
     * @param array|UploadedFile $value
     *
     * @return Input
     */
    public function setValue($value)
    {
        if(\is_array($value)) {
            if (isset($value[0]) && $value[0] instanceof UploadedFileInterface) {
                $this->filterImpl = new PsrFileInputDecorator($this);
            } else {
                $this->filterImpl = new HttpServerFileInputDecorator($this);
            }
        } elseif ($value instanceof UploadedFileInterface) {
            $this->filterImpl = new PsrFileInputDecorator($this);
        } else {
            // ajax case
            $this->filterImpl = new HttpServerFileInputDecorator($this);
        }


        parent::setValue($value);

        return $this;
    }

    public function resetValue()
    {
        $this->filterImpl = null;
        return parent::resetValue();
    }


    /**
     * @param  bool $value Enable/Disable automatically prepending an Upload validator
     *
     * @return FileInput
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
        if ($this->filterImpl === null) {
            return $this->value;
        }
        return $this->filterImpl->getValue();
    }

    /**
     * Checks if the raw input value is an empty file input eg: no file was uploaded
     *
     * @param $rawValue
     * @return bool
     */
    public function isEmptyFile($rawValue)
    {
        if (\is_array($rawValue)) {
            if (isset($rawValue[0]) && $rawValue[0] instanceof UploadedFileInterface) {
                return PsrFileInputDecorator::isEmptyFileDecorator($rawValue);
            }

            return HttpServerFileInputDecorator::isEmptyFileDecorator($rawValue);
        }

        if($rawValue instanceof UploadedFileInterface) {
            return PsrFileInputDecorator::isEmptyFileDecorator($rawValue);
        }

        return true;
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

        return $this->filterImpl->isValid($context);
    }

    /**
     * @param  InputInterface $input
     *
     * @return FileInput
     */
    public function merge(InputInterface $input)
    {
        parent::merge($input);
        if ($input instanceof FileInput) {
            $this->setAutoPrependUploadValidator($input->getAutoPrependUploadValidator());
        }
        return $this;
    }

    /**
     * @deprecated 2.4.8 See note on parent class. Removal does not affect this class.
     *
     * No-op, NotEmpty validator does not apply for FileInputs.
     * See also: BaseInputFilter::isValid()
     *
     * @return void
     */
    protected function injectNotEmptyValidator()
    {
        $this->notEmptyValidator = true;
    }
}
