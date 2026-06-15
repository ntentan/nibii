# Nibii ORM

`ntentan/nibii` is a lightweight, intuitive, and powerful Active Record-based Object-Relational Mapper (ORM) for PHP. Built as the default database interface for the `ntentan` framework, it can also be configured and utilized as a standalone ORM library in any PHP project.

---

## Features

- **Active Record Pattern**: Represent database tables as PHP classes extending `RecordWrapper` for seamless interaction.
- **Dynamic Finders and Filters**: Query the database using expressive dynamic method calls like `filterByFieldName()` and `fetchFirstByFieldName()`.
- **Relationship Mapping**: Easy-to-use relationship configurations:
  - `BelongsTo`
  - `HasMany`
  - `ManyHaveMany` (with automatic pivot table handling)
- **Built-in Validation**: Easily validate model fields before saving, with support for field presence, types, and unique constraints.
- **Bulk Operations**: Perform batch inserts, updates, and deletes cleanly.

---

## Installation

Install `nibii` via [Composer](https://getcomposer.org/):

```bash
composer require ntentan/nibii
```

---

## Initialization & Configuration

Before querying your models, initialize `DbContext` and `ORMContext`:

```php
use ntentan\atiaa\DbContext;
use ntentan\atiaa\DriverFactory;
use ntentan\kaikai\Cache;
use ntentan\kaikai\backends\VolatileCache; // or any PSR-16 cache implementation
use ntentan\nibii\factories\DefaultModelFactory;
use ntentan\nibii\factories\DriverAdapterFactory;
use ntentan\nibii\factories\DefaultValidatorFactory;
use ntentan\nibii\ORMContext;

// Database connection details
$config = [
    'driver'   => 'postgresql', // e.g., 'postgresql', 'mysql', 'sqlite'
    'host'     => 'localhost',
    'user'     => 'db_user',
    'password' => 'db_password',
    'dbname'   => 'my_database',
];

// Initialize database contexts
DbContext::initialize(new DriverFactory($config));

ORMContext::initialize(
    new DefaultModelFactory(),
    new DriverAdapterFactory($config['driver']),
    new DefaultValidatorFactory(),
    new Cache(new VolatileCache())
);
```

---

## Defining Models

Models in `nibii` extend `ntentan\nibii\RecordWrapper`. By default, Nibii maps the model class name to a database table (e.g., a `Users` model maps to the `users` table).

```php
namespace App\Models;

use ntentan\nibii\RecordWrapper;

class Users extends RecordWrapper
{
    // Define a belongsTo relationship to the Roles model
    protected $belongsTo = ['\App\Models\Roles'];
}
```

---

## Usage Guide

### Fetching Records

`nibii` offers several convenient ways to retrieve data.

#### Fetching All Records
```php
$users = (new Users())->fetch();
foreach ($users as $user) {
    echo $user->username;
}
```

#### Filtering Records
You can write custom filters using standard placeholders (`?` or named parameters):

```php
// Using question mark placeholders
$user = (new Users())->filter('username = ?', 'james')->fetchFirst();

// Using named parameters
$user = (new Users())->filter('username = :username', [':username' => 'james'])->fetchFirst();
```

#### Dynamic Finders
Retrieve records using dynamic finder helper methods automatically generated from your column names:

```php
// Find all users with role_id = 11 or 12
$users = (new Users())->filterByRoleId(11, 12)->fetch();

// Fetch first user matching a username
$user = (new Users())->fetchFirstWithUsername('james');
```

---

### Saving and Updating Records

#### Creating New Records
Instantiate your model class, assign values as properties or array keys, and call `save()`:

```php
$role = new Roles();
$role->name = 'Administrator';
// Or: $role['name'] = 'Administrator';

if ($role->save()) {
    echo "Saved successfully! ID: " . $role->id;
} else {
    // Validation failed
    print_r($role->getInvalidFields());
}
```

#### Updating Existing Records
```php
$user = (new Users())->fetchFirstWithId(1);
if ($user) {
    $user->username = 'new_username';
    $user->save();
}
```

#### Bulk Updates
You can update multiple records that match specific conditions in a single query:

```php
// Update role_id to 15 for users matching role_id 11 or 12
(new Users())->filterByRoleId(11, 12)->update(['role_id' => 15]);
```

---

### Deleting Records

You can delete a set of records directly matching a query:

```php
// Delete all users with ID less than 3
(new Users())->filter('id < ?', 3)->delete();
```

---

### Working with Relationships

Configure relationships directly in your class definition properties: `$belongsTo`, `$hasMany`, and `$manyHaveMany`.

#### BelongsTo Relationship
```php
class Users extends RecordWrapper
{
    protected $belongsTo = ['\App\Models\Roles'];
}
```

#### HasMany Relationship
```php
class Roles extends RecordWrapper
{
    protected $hasMany = ['\App\Models\Users'];
}
```

#### ManyHaveMany Relationship
```php
class Projects extends RecordWrapper
{
    public $manyHaveMany = ['\App\Models\Users'];
}
```

---

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file (if present) or `composer.json` for details.
