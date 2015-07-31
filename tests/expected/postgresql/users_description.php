<?php
$description = array (
  'fields' => 
  array (
    'email' => 
    array (
      'type' => 'string',
      'required' => true,
      'default' => NULL,
      'name' => 'email',
      'length' => 255,
    ),
    'firstname' => 
    array (
      'type' => 'string',
      'required' => true,
      'default' => NULL,
      'name' => 'firstname',
      'length' => 255,
    ),
    'id' => 
    array (
      'type' => 'integer',
      'required' => true,
      'default' => NULL,
      'name' => 'id',
    ),
    'is_admin' => 
    array (
      'type' => 'boolean',
      'required' => false,
      'default' => NULL,
      'name' => 'is_admin',
    ),
    'last_login_time' => 
    array (
      'type' => 'datetime',
      'required' => false,
      'default' => NULL,
      'name' => 'last_login_time',
    ),
    'lastname' => 
    array (
      'type' => 'string',
      'required' => true,
      'default' => NULL,
      'name' => 'lastname',
      'length' => 255,
    ),
    'office' => 
    array (
      'type' => 'integer',
      'required' => false,
      'default' => NULL,
      'name' => 'office',
    ),
    'othernames' => 
    array (
      'type' => 'string',
      'required' => false,
      'default' => NULL,
      'name' => 'othernames',
      'length' => 255,
    ),
    'password' => 
    array (
      'type' => 'string',
      'required' => true,
      'default' => NULL,
      'name' => 'password',
      'length' => 255,
    ),
    'phone' => 
    array (
      'type' => 'string',
      'required' => false,
      'default' => NULL,
      'name' => 'phone',
      'length' => 64,
    ),
    'role_id' => 
    array (
      'type' => 'integer',
      'required' => true,
      'default' => NULL,
      'name' => 'role_id',
    ),
    'status' => 
    array (
      'type' => 'integer',
      'required' => true,
      'default' => '2',
      'name' => 'status',
    ),
    'username' => 
    array (
      'type' => 'string',
      'required' => true,
      'default' => NULL,
      'name' => 'username',
      'length' => 255,
    ),
  ),
  'primary_key' => 
  array (
    0 => 'id',
  ),
  'unique_keys' => 
  array (
  ),
  'auto_primary_key' => true,
);
