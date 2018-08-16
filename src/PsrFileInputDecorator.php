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

/**
 * PsrFileInput is a special Input type for handling uploaded files through  PSR-7 middlware.
 *
 * It differs from Input in a few ways:
 *
 * 1. It expects the raw value to be instance of UploadedFileInterface object type.
 *
 * 2. The validators are run **before** the filters (the opposite behavior of Input).
 *    This is so is_uploaded_file() validation can be run prior to any filters that
 *    may rename/move/modify the file.
 *
 * 3. Instead of adding a NotEmpty validator, it will (by default) automatically add
 *    a Zend\Validator\File\Upload validator.
 */
class PsrFileInputDecorator extends FileInput implements FileInputDecoratorInterface
{

    /** @var FileInput */
    private $subject;

    public function __construct(FileInput $subject)
    {
        $this->subject = $subject;
    }

    /**
     * @return UploadedFileInterface|array
     */
    public function getValue()
    {
        $value = $this->subject->value;

        // Run filters ~after~ validation, so that is_uploaded_file()
        // validation is not affected by filters.
        if (! $this->subject->isValid) {
            return $value;
        }

        if (\is_array($value)) {
            // Multi file input (multiple attribute set)
            $filter = $this->subject->getFilterChain();

            $newValue = [];
            foreach ($value as $fileData) {
                $newValue[] = $filter->filter($fileData);
            }
            $value = $newValue;
        } else {
            // Single file input

            $filter = $this->subject->getFilterChain();
            $value = $filter->filter($value);
        }

        return $value;
    }

    /**
     * Checks if the raw input value is an empty file input eg: no file was uploaded
     *
     * @param UploadedFileInterface|array $rawValue
     * @return bool
     */
    public static function isEmptyFileDecorator($rawValue)
    {
        if (\is_array($rawValue)) {
            return self::isEmptyFileDecorator($rawValue[0]);
        }

        return $rawValue->getError() === UPLOAD_ERR_NO_FILE;

    }

    /**
     * @param  mixed $context Extra "context" to provide the validator
     * @return bool
     */
    public function isValid($context = null)
    {
        $rawValue        = $this->subject->getRawValue();
        $validator       = $this->subject->getValidatorChain();
        $this->injectUploadValidator();

        //$value   = $this->getValue(); // Do not run the filters yet for File uploads (see getValue())

        if (\is_array($rawValue)) {
            // Multi file input (multiple attribute set)
            $this->subject->isValid = true;
            foreach ($rawValue as $value) {
                if (! $validator->isValid($value, $context)) {
                    $this->subject->isValid = false;
                    break; // Do not continue processing files if validation fails
                }
            }
        } else {
            // Single file input
            $this->subject->isValid = $validator->isValid($rawValue, $context);
        }

        return $this->subject->isValid;
    }

    /**
     * @return void
     */
    protected function injectUploadValidator()
    {
        if (! $this->subject->autoPrependUploadValidator) {
            return;
        }
        $chain = $this->subject->getValidatorChain();

        // Check if Upload validator is already first in chain
        $validators = $chain->getValidators();
        if (isset($validators[0]['instance'])) {
            $this->subject->autoPrependUploadValidator = false;
            return;
        }

        $chain->prependByName('fileuploadfile', [], true);
        $this->subject->autoPrependUploadValidator = false;
    }
}
