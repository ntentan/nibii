<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ntentan\nibii\interfaces;

use ntentan\nibii\RecordWrapper;

/**
 * Description of ModelValidatorFactoryInterface
 *
 * @author ekow
 */
interface ValidatorFactoryInterface
{
    public function createModelValidator(RecordWrapper $model, $mode);
}
