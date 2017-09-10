<?php

namespace ntentan\nibii\factories;

use ntentan\nibii\DefaultValidator;
use ntentan\nibii\interfaces\ValidatorFactoryInterface;
use ntentan\nibii\RecordWrapper;

/**
 * Description of DefaultModelValidatorFactory
 *
 * @author ekow
 */
class DefaultValidatorFactory implements ValidatorFactoryInterface
{
    public function createModelValidator(RecordWrapper $model, $mode)
    {
        $validator = new DefaultValidator($model, $mode);
        $validator->extractRules();
        return $validator;
    }
}
