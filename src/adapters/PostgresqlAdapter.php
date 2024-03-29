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

namespace ntentan\nibii\adapters;

use ntentan\nibii\DriverAdapter;

class PostgresqlAdapter extends DriverAdapter
{
    /**
     * Convert from postgresqls native type to a generic type accepted in the
     * atiaa library.
     *
     * @param string $nativeType
     *
     * @throws \Exception
     *
     * @return string
     */
    public function mapDataTypes($nativeType)
    {
        switch ($nativeType) {
            case 'character varying':
                return 'string';
            case 'text':
                return 'text';
            case 'date':
                return 'date';
            case 'integer':
            case 'boolean':
                return $nativeType;
            case 'numeric':
                return 'double';
            case 'timestamp without time zone':
            case 'timestamp with time zone':
                return 'datetime';
            default:
                throw new \Exception("Unknown type {$nativeType}");
        }
    }
}
