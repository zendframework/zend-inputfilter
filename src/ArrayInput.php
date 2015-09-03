<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\InputFilter;

class ArrayInput extends Input
{
    /**
     * @var array
     */
    protected $value = [];

    /**
     * @param  array $value
     * @throws Exception\InvalidArgumentException
     * @return Input
     */
    public function setValue($value)
    {
        if (!is_array($value)) {
            throw new Exception\InvalidArgumentException(
                sprintf('Value must be an array, %s given.', gettype($value))
            );
        }
        return parent::setValue($value);
    }

    /**
     * {@inheritdoc}
     */
    public function resetValue()
    {
        $this->value = [];
        $this->hasValue = false;
        return $this;
    }

    /**
     * @return array
     */
    public function getValue()
    {
        $filter = $this->getFilterChain();
        $result = [];
        foreach ($this->value as $key => $value) {
            $result[$key] = $filter->filter($value);
        }
        return $result;
    }

    /**
     * @param  mixed $context Extra "context" to provide the validator
     * @return bool
     */
    public function isValid($context = null)
    {
        $hasValue = $this->hasValue();
        $required = $this->isRequired();
        $hasFallback = $this->hasFallback();

        if (! $hasValue && $hasFallback) {
            $this->setValue($this->getFallbackValue());
            return true;
        }

        if (! $hasValue && $required) {
            $this->setErrorMessage('Value is required');
            return false;
        }

        $allowEmpty = $this->allowEmpty();
        $continueIfEmpty = $this->continueIfEmpty();

        $validator = $this->getValidatorChain();
        $values    = $this->getValue();
        $result    = true;
        foreach ($values as $value) {
            $empty = ($value === null || $value === '' || $value === []);
            if ($empty && $allowEmpty && !$continueIfEmpty) {
                $result = true;
                continue;
            }

            // At this point, we need to run validators.
            // If we do not allow empty and the "continue if empty" flag are
            // BOTH false, we inject the "not empty" validator into the chain,
            // which adds that logic into the validation routine.
            if ($empty && !$allowEmpty) {
                $this->injectNotEmptyValidator();
            }

            $result = $validator->isValid($value, $context);
            if (!$result) {
                if ($hasFallback) {
                    $this->setValue($this->getFallbackValue());
                    return true;
                }
                break;
            }
        }

        return $result;
    }
}
