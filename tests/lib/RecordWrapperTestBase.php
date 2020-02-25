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

use ntentan\atiaa\DbContext;
use ntentan\atiaa\DriverFactory;
use ntentan\kaikai\backends\VolatileCache;
use ntentan\kaikai\Cache;
use ntentan\nibii\factories\DefaultModelFactory;
use ntentan\nibii\factories\DefaultValidatorFactory;
use ntentan\nibii\factories\DriverAdapterFactory;
use ntentan\nibii\ORMContext;
use PDO;
use PHPUnit\Framework\TestCase;

class RecordWrapperTestBase extends TestCase
{
    protected $context;
    private $pdo;

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

        $modelFactory = new DefaultModelFactory();
        $driverAdapterFactory = new DriverAdapterFactory($config['driver']);
        $modelValidatorFactory = new DefaultValidatorFactory();
        $cache = new Cache(new VolatileCache());
        DbContext::initialize(new DriverFactory($config));
        ORMContext::initialize($modelFactory, $driverAdapterFactory, $modelValidatorFactory, $cache);
        $this->setupDatabase();
    }

    private function setupDatabase()
    {
        $pdo = new PDO(getenv('NIBII_PDO_DSN'), getenv('NIBII_USER'), getenv('NIBII_PASSWORD'));
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $data = $this->getDataSet();
        foreach(array_reverse(array_keys($data)) as $table) {
            $success = $pdo->query("DELETE FROM $table");
        }
        foreach($data as $table => $rows) {
            foreach($rows as $row) {
                $rowData = implode("','", $row);
                $rowFields = implode(", ", array_keys($row));
                $success = $pdo->query("INSERT INTO $table($rowFields) VALUES ('$rowData')");
            }
        }
        $pdo = null;
    }

    protected function countTableRows($table)
    {
        $pdo = new PDO(getenv('NIBII_PDO_DSN'), getenv('NIBII_USER'), getenv('NIBII_PASSWORD'));
        $results = $pdo->query("SELECT COUNT(*) FROM $table")->fetchAll()[0][0];
        $pdo = null;
        return $results;
    }

    protected function getQueryAsArray($query)
    {
        $pdo = new PDO(getenv('NIBII_PDO_DSN'), getenv('NIBII_USER'), getenv('NIBII_PASSWORD'));
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $results = $pdo->query($query, PDO::FETCH_ASSOC)->fetchAll();
        $pdo = null;
        return $results;
    }

    protected function getDataSet()
    {
        return array_merge($this->getExtraArrayData(), [
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
        ]);
    }

    protected function getExtraArrayData()
    {
        return [];
    }
}
