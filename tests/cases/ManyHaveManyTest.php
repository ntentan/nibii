<?php

/*
 * The MIT License
 *
 * Copyright 2015 ekow.
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

/**
 * Description of ManyHaveManyTest
 *
 * @author ekow
 */

namespace ntentan\nibii\tests\cases;

class ManyHaveManyTest extends \ntentan\nibii\tests\lib\RecordWrapperTestBase
{
    public function testManyHaveMany()
    {
        $projects = \ntentan\nibii\tests\models\raw\Projects::fetchFirstWithId(1);
        $this->assertEquals(
            array (
              'id' => 1,
              'name' => 'Kakalika',
              'description' => 'Lorem ipsum dolor',
              '\\ntentan\\nibii\\tests\\models\\raw\\Users' => 
              array (
                0 => 
                array (
                  'id' => 1,
                  'username' => 'james',
                  'password' => 'somehashedstring',
                  'role_id' => 10,
                  'firstname' => 'James',
                  'lastname' => 'Ainooson',
                  'othernames' => NULL,
                  'status' => 1,
                  'email' => 'james@nibii.test',
                  'phone' => NULL,
                  'office' => NULL,
                  'last_login_time' => NULL,
                  'is_admin' => NULL,
                ),
                1 => 
                array (
                  'id' => 2,
                  'username' => 'fiifi',
                  'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
                  'role_id' => 11,
                  'firstname' => 'Fiifi',
                  'lastname' => 'Antobra',
                  'othernames' => NULL,
                  'status' => 2,
                  'email' => 'fiifi@nibii.test',
                  'phone' => NULL,
                  'office' => NULL,
                  'last_login_time' => NULL,
                  'is_admin' => NULL,
                ),
                2 => 
                array (
                  'id' => 3,
                  'username' => 'kwame',
                  'password' => 'coolthings',
                  'role_id' => 12,
                  'firstname' => 'Kwame',
                  'lastname' => 'Nyarko',
                  'othernames' => NULL,
                  'status' => 2,
                  'email' => 'knyarko@nibii.test',
                  'phone' => NULL,
                  'office' => NULL,
                  'last_login_time' => NULL,
                  'is_admin' => NULL,
                ),
              ),
            ),                
            $projects->toArray(1)
        );
    }
    
    public function testAliasedManyHaveMany()
    {
        $projects = \ntentan\nibii\tests\models\aliased\Projects::fetchFirstWithId(1);
        $this->assertEquals(
            array (
              'id' => 1,
              'name' => 'Kakalika',
              'description' => 'Lorem ipsum dolor',
              'users' => 
              array (
                0 => 
                array (
                  'id' => 1,
                  'username' => 'james',
                  'password' => 'somehashedstring',
                  'role_id' => 10,
                  'firstname' => 'James',
                  'lastname' => 'Ainooson',
                  'othernames' => NULL,
                  'status' => 1,
                  'email' => 'james@nibii.test',
                  'phone' => NULL,
                  'office' => NULL,
                  'last_login_time' => NULL,
                  'is_admin' => NULL,
                ),
                1 => 
                array (
                  'id' => 2,
                  'username' => 'fiifi',
                  'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
                  'role_id' => 11,
                  'firstname' => 'Fiifi',
                  'lastname' => 'Antobra',
                  'othernames' => NULL,
                  'status' => 2,
                  'email' => 'fiifi@nibii.test',
                  'phone' => NULL,
                  'office' => NULL,
                  'last_login_time' => NULL,
                  'is_admin' => NULL,
                ),
                2 => 
                array (
                  'id' => 3,
                  'username' => 'kwame',
                  'password' => 'coolthings',
                  'role_id' => 12,
                  'firstname' => 'Kwame',
                  'lastname' => 'Nyarko',
                  'othernames' => NULL,
                  'status' => 2,
                  'email' => 'knyarko@nibii.test',
                  'phone' => NULL,
                  'office' => NULL,
                  'last_login_time' => NULL,
                  'is_admin' => NULL,
                ),
              ),
            ),                
            $projects->toArray(1)
        );
    }    
    
    protected function getExtraArrayData()
    {
        return [
            'projects' => [
                ['id' => '1', 'name' => 'Kakalika', 'description' => 'Lorem ipsum dolor'],
                ['id' => '2', 'name' => 'WYF', 'description' => 'Lorem ipsum dolor'],
                ['id' => '3', 'name' => 'Ntentan', 'description' => 'Lorem ipsum dolor'],
                ['id' => '4', 'name' => 'Nyansapow', 'description' => 'Lorem ipsum dolor'],
            ],
            
            'projects_users' => [
                ['id' => 1, 'user_id' => 1, 'project_id' => 1],
                ['id' => 2, 'user_id' => 1, 'project_id' => 2],
                ['id' => 3, 'user_id' => 1, 'project_id' => 3],
                ['id' => 4, 'user_id' => 1, 'project_id' => 4],
                ['id' => 5, 'user_id' => 2, 'project_id' => 1],
                ['id' => 6, 'user_id' => 3, 'project_id' => 2],
                ['id' => 7, 'user_id' => 3, 'project_id' => 3],
                ['id' => 8, 'user_id' => 3, 'project_id' => 1],
                ['id' => 9, 'user_id' => 4, 'project_id' => 3],
                ['id' => 10, 'user_id' => 4, 'project_id' => 4]
            ]
        ];
    }
}
