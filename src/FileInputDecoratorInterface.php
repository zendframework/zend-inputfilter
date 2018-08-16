<?php
/**
 * Created by PhpStorm.
 * User: sasha
 * Date: 16.08.2018
 * Time: 10:15
 */

namespace Zend\InputFilter;


use Zend\Diactoros\UploadedFile;

/**
 * FileInputInterface defines expected methods and return signature for PSR-7 and
 * Legacy HTTP Server uploaded file filtering. Used as a bridge to keep BC for
 * tightly coupled repositories with hard-coded strings for filter names.
 *
 */
interface FileInputDecoratorInterface
{

    /**
     * @return mixed
     */
    public function getValue();

    /**
     * Checks if the raw input value is an empty file input eg: no file was uploaded
     *
     * @param $rawValue
     *
     * @return bool
     */
    public static function isEmptyFileDecorator($rawValue);

    /**
     * @param  mixed $context Extra "context" to provide the validator
     *
     * @return bool
     */
    public function isValid($context = null);

}