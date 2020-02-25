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

namespace ntentan\nibii\tests\cases;

use ntentan\nibii\tests\lib\RecordWrapperTestBase;
use ntentan\nibii\tests\models\raw\Roles;
use ntentan\nibii\tests\models\raw\Users;

class LazyLoadingTest extends RecordWrapperTestBase
{
    public function testBelongsTo()
    {
        $expected = [
            ['id' => 10, 'name' => 'Some test user'],
            ['id' => 11, 'name' => 'Matches'],
            ['id' => 12, 'name' => 'Rematch'],
            ['id' => 12, 'name' => 'Rematch'],
        ];
        $users = (new Users())->fetch();
        $i = 0;
        foreach ($users as $user) {
            $this->assertEquals(
                $expected[$i++], $user->{'\ntentan\nibii\tests\models\raw\Roles'}->toArray()
            );
        }
    }

    public function testHasMany()
    {
        $role = (new Roles())->fetch(12);
        $results = [
            0 => [
                'id'              => 3,
                'username'        => 'kwame',
                'password'        => 'coolthings',
                'role_id'         => 12,
                'firstname'       => 'Kwame',
                'lastname'        => 'Nyarko',
                'othernames'      => null,
                'status'          => 2,
                'email'           => 'knyarko@nibii.test',
                'phone'           => null,
                'office'          => null,
                'last_login_time' => null,
                'is_admin'        => null,
            ],
            1 => [
                'id'              => 4,
                'username'        => 'adjoa',
                'password'        => 'hahaha',
                'role_id'         => 12,
                'firstname'       => 'Adjoa',
                'lastname'        => 'Boateng',
                'othernames'      => null,
                'status'          => 2,
                'email'           => 'aboateng@nibii.test',
                'phone'           => null,
                'office'          => null,
                'last_login_time' => null,
                'is_admin'        => null,
            ],
        ];
        $this->assertEquals($results, $role->{'\ntentan\nibii\tests\models\raw\Users'}->toArray());
    }

    public function testAliasedBelongsTo()
    {
        $expected = [
            ['id' => 10, 'name' => 'Some test user'],
            ['id' => 11, 'name' => 'Matches'],
            ['id' => 12, 'name' => 'Rematch'],
            ['id' => 12, 'name' => 'Rematch'],
        ];
        $users = (new \ntentan\nibii\tests\models\aliased\Users())->fetch();
        $i = 0;
        foreach ($users as $user) {
            $this->assertEquals(
                    $expected[$i++], $user->role->toArray()
            );
        }
    }

    public function testAliasedHasMany()
    {
        $role = (new \ntentan\nibii\tests\models\aliased\Roles())->fetch(12);
        $results = [
            0 => [
                'id'              => 3,
                'username'        => 'kwame',
                'password'        => 'coolthings',
                'role_id'         => 12,
                'firstname'       => 'Kwame',
                'lastname'        => 'Nyarko',
                'othernames'      => null,
                'status'          => 2,
                'email'           => 'knyarko@nibii.test',
                'phone'           => null,
                'office'          => null,
                'last_login_time' => null,
                'is_admin'        => null,
            ],
            1 => [
                'id'              => 4,
                'username'        => 'adjoa',
                'password'        => 'hahaha',
                'role_id'         => 12,
                'firstname'       => 'Adjoa',
                'lastname'        => 'Boateng',
                'othernames'      => null,
                'status'          => 2,
                'email'           => 'aboateng@nibii.test',
                'phone'           => null,
                'office'          => null,
                'last_login_time' => null,
                'is_admin'        => null,
            ],
        ];
        $this->assertEquals($results, $role->users->toArray());
    }
}
