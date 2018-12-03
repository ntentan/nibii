<?php

/*
 * The MIT License
 *
 * Copyright 2014-2018 James Ekow Abaka Ainooson
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace ntentan\nibii\factories;

use ntentan\nibii\interfaces\ModelFactoryInterface;
use ntentan\utils\Text;

class DefaultModelFactory implements ModelFactoryInterface
{
    public function createModel($className, $context)
    {
        return new $className();
    }

    public function getClassName($model)
    {
        return $model;
    }

    public function getModelTable($instance)
    {
        $class = new \ReflectionClass($instance);
        $nameParts = explode('\\', $class->getName());

        return Text::deCamelize(end($nameParts));
    }

    public function getJunctionClassName($classA, $classB)
    {
        $classA = $this->getClassFileDetails($classA);
        $classB = $this->getClassFileDetails($classB);
        if ($classA['namespace'] != $classB['namespace']) {
            throw new NibiiException(
            'Cannot automatically join two classes of different '
            .'namespaces. Please provide a model joiner or '
            .'explicitly specify your joint model.'
            );
        }
        $classes = [$classA['class'], $classB['class']];
        sort($classes);

        return "{$classA['namespace']}\\".implode('', $classes);
    }

    private function getClassFileDetails($className)
    {
        $arrayed = explode('\\', $className);
        $class = array_pop($arrayed);
        if ($arrayed[0] == '') {
            array_shift($arrayed);
        }

        return ['class' => $class, 'namespace' => implode('\\', $arrayed)];
    }
}
