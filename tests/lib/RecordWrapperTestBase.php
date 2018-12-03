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

namespace ntentan\nibii\tests\lib;

use ntentan\nibii\ORMContext;
use PHPUnit\DbUnit\TestCase;

class RecordWrapperTestBase extends TestCase
{
    protected $context;

    public function setUp() : void
    {
        parent::setUp();

        $config = [
            'driver'   => getenv('NIBII_DATASTORE'),
            'host'     => getenv('NIBII_HOST'),
            'user'     => getenv('NIBII_USER'),
            'password' => getenv('NIBII_PASSWORD'),
            'file'     => getenv('NIBII_FILE'),
            'dbname'   => getenv('NIBII_DBNAME'),
        ];

        $modelFactory = new \ntentan\nibii\factories\DefaultModelFactory();
        $driverAdapterFactory = new \ntentan\nibii\factories\DriverAdapterFactory($config['driver']);
        $modelValidatorFactory = new \ntentan\nibii\factories\DefaultValidatorFactory();
        $cache = new \ntentan\kaikai\Cache(new \ntentan\kaikai\backends\VolatileCache());
        \ntentan\atiaa\DbContext::initialize(new \ntentan\atiaa\DriverFactory($config));
        ORMContext::initialize($modelFactory, $driverAdapterFactory, $modelValidatorFactory, $cache);
    }

    protected function getConnection()
    {
        $pdo = new \PDO(getenv('NIBII_PDO_DSN'), getenv('NIBII_USER'), getenv('NIBII_PASSWORD'));

        return $this->createDefaultDBConnection($pdo, getenv('NIBII_DBNAME'));
    }

    protected function getDataSet()
    {
        return $this->createArrayDataSet(array_merge($this->getExtraArrayData(), [
            'roles' => [
                ['id' => 10, 'name' => 'Some test user'],
                ['id' => 11, 'name' => 'Matches'],
                ['id' => 12, 'name' => 'Rematch'],
                ['id' => 13, 'name' => 'Test role'],
                ['id' => 14, 'name' => 'Another test role'],
                ['id' => 15, 'name' => 'More test roles'],
            ],
            'users' => [
                ['id' => 1, 'username' => 'james', 'role_id' => 10, 'firstname' => 'James', 'lastname' => 'Ainooson', 'status' => 1, 'password' => 'somehashedstring', 'email' => 'james@nibii.test'],
                ['id' => 2, 'username' => 'fiifi', 'role_id' => 11, 'firstname' => 'Fiifi', 'lastname' => 'Antobra', 'status' => 2, 'password' => md5('password'), 'email' => 'fiifi@nibii.test'],
                ['id' => 3, 'username' => 'kwame', 'role_id' => 12, 'firstname' => 'Kwame', 'lastname' => 'Nyarko', 'status' => 2, 'password' => 'coolthings', 'email' => 'knyarko@nibii.test'],
                ['id' => 4, 'username' => 'adjoa', 'role_id' => 12, 'firstname' => 'Adjoa', 'lastname' => 'Boateng', 'status' => 2, 'password' => 'hahaha', 'email' => 'aboateng@nibii.test'],
            ],
        ]));
    }

    protected function getSetUpOperation()
    {
        return $this->getOperations()->CLEAN_INSERT(true);
    }

    protected function getExtraArrayData()
    {
        return [];
    }
}
