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

$description = [
  'fields' => [
    'email' => [
      'type'     => 'string',
      'required' => true,
      'default'  => null,
      'name'     => 'email',
      'length'   => 255,
    ],
    'firstname' => [
      'type'     => 'string',
      'required' => true,
      'default'  => null,
      'name'     => 'firstname',
      'length'   => 255,
    ],
    'id' => [
      'type'     => 'integer',
      'required' => true,
      'default'  => null,
      'name'     => 'id',
    ],
    'is_admin' => [
      'type'     => 'boolean',
      'required' => false,
      'default'  => null,
      'name'     => 'is_admin',
    ],
    'lastname' => [
      'type'     => 'string',
      'required' => true,
      'default'  => null,
      'name'     => 'lastname',
      'length'   => 255,
    ],
    'last_login_time' => [
      'type'     => 'datetime',
      'required' => false,
      'default'  => null,
      'name'     => 'last_login_time',
    ],
    'office' => [
      'type'     => 'integer',
      'required' => false,
      'default'  => null,
      'name'     => 'office',
    ],
    'othernames' => [
      'type'     => 'string',
      'required' => false,
      'default'  => null,
      'name'     => 'othernames',
      'length'   => 255,
    ],
    'password' => [
      'type'     => 'string',
      'required' => true,
      'default'  => null,
      'name'     => 'password',
      'length'   => 255,
    ],
    'phone' => [
      'type'     => 'string',
      'required' => false,
      'default'  => null,
      'name'     => 'phone',
      'length'   => 64,
    ],
    'role_id' => [
      'type'     => 'integer',
      'required' => true,
      'default'  => null,
      'name'     => 'role_id',
    ],
    'status' => [
      'type'     => 'integer',
      'required' => true,
      'default'  => '2',
      'name'     => 'status',
    ],
    'username' => [
      'type'     => 'string',
      'required' => true,
      'default'  => null,
      'name'     => 'username',
      'length'   => 255,
    ],
  ],
  'primary_key' => [
    0 => 'id',
  ],
  'unique_keys' => [
  ],
  'auto_primary_key' => true,
];
