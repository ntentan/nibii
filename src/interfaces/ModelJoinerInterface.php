<?php

namespace ntentan\nibii\interfaces;

/**
 * Description of ModelJoinerInterface
 *
 * @author ekow
 */
interface ModelJoinerInterface
{
    public function getJunctionClassName($classA, $classB);
}
