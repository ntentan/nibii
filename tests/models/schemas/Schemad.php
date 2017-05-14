<?php

namespace ntentan\nibii\tests\models\schemas;

/**
 * Description of Projects
 *
 * @author ekow
 */
class Schemad extends \ntentan\nibii\RecordWrapper {

    public $manyHaveMany = ['\ntentan\nibii\tests\models\raw\Users'];

}
