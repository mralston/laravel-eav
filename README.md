# Laravel EAV

## Introduction

Entity Attribute Value package for Laravel.

EAV Definition From [Wikipedia](https://en.wikipedia.org/wiki/Entity%E2%80%93attribute%E2%80%93value_model):

Entity–attribute–value model (EAV) is a data model to encode, in a space-efficient manner, entities where the number of attributes (properties, parameters) that can be used to describe them is potentially vast, but the number that will actually apply to a given entity is relatively modest.

**TL;DR** this package allows you to work with attributes on your models which aren't defined in the underlying database schema. 

## But why though?

> Your scientists were so preoccupied with whether or not they could that they didn't stop to think if they should.
> 
> -- Dr. Ian Malcolm (Jeff Goldblum), Jurassic Park

- Yes, there are another packages which already provide similar functionality.
- Yes, EAV is sometimes considered an anti-pattern due to performance issues.
- Yes, this might be considered reinventing the NoSQL wheel.
- Yes, I did it anyway. 🤷🏻‍♂️

Truth be told, this started as a proof-of-concept based on an idea I had, aimed at improving a messy pre-existing implementation in several projects which I am involved in maintaining.

The EAV implementations which I've seen thus far have a separate database record for each EAV attribute. I believe that the bulk of the performance problems comes from the large amounts of additional database traffic as a result.

My concept is to place all of the EAV data into a JSON field in a single database record, which can be fetched just once per model instance.

Inspiration for using a [JSON field](https://www.youtube.com/watch?v=QZBxgX2OWbI) came from [Aaron Francis](https://aaronfrancis.com/) @ PlanetScale, so kudos to him. Also thanks to Aaron for the get-out-of-jail-free card when it comes to the 'you should just use a NoSQL database' argument.

## Installation

Install the package using Composer:

```bash
composer require mralston/laravel-eav
```

## Config

This package has no config, it doesn't need it. Yay!

## Migrations

After installing, you need to migrate the package's single database table.

```bash
php artisan migrate
```

## Usage

This package is designed to be completely transparent. You can read and write to model attributes just as you normally would.

Any model which you would like to use EAV will need to use the `HasEntityAttributeValues` trait:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mralston\Eav\Traits\HasEntityAttributeValues;

class MyModel extends Model
{
    use HasEntityAttributeValues;

    ...
}
```

Then all you need to do is set attributes on your model as you normally would:

```php
$myModel->myField = 'test';
dump($myModel->myField); // test
```

If the attribute you set is defined as a field on the underlying table, it will be stored there as normal. Otherwise, it will be stored as an EAV attribute.

Other methods are also available which allow access to EAV data.

The `eav` method operates as a combined accessor and mutator, depending on whether it is passed the optional `$value` argument.

```php
$myModel->eav('myField', 'test');
dump($myModel->eav('myField')); // test
```

The underlying EAV store is assessable using the `entityAttributeStore` relationship. It provides `get`, `set` and `unset` methods:

```php
$myModel->entityAttributeStore->set('myField', 'test');
$myModel->entityAttributeStore->get('myField'); // test

$myModel->entityAttributeStore->unset('myField');
$myModel->entityAttributeStore->get('myField'); // null
```

## A note on Persistence

EAV data is persisted to the database when you save the associated model. Until that time, any changes you make exist only on the model in memory - just like standard Eloquent attributes.

```php
// Load model from database and set an EAV attribute
$myModel = MyModel::find(1);
$myModel->myField = 'test';
dump($myModel->myField); // test

// Load a second copy of the model and attempt to
// retrieve the same EAV attribute.
// It fails because it hasn't been saved yet
$myModel2 = MyModel::find(1);
dump($myModel2->myField); // null

// Save the first model and its EAV attributes
$myModel->save(); 

// Load another copy of the model from the database.
// This one will have the EAV data
$myModel3 = MyModel::find(1);
dump($myModel3->myField); // test
```

Likewise, if you instantiate several individual models of the same database record, each model instance will have its own distinct copy of the EAV attributes. You can modify an EAV attribute on one model instance and the others will remain unaffected - just like standard Eloquent attributes.

```php
$myModel = MyModel::find(1);
$myModel->myField = 'foo';

$myModel2 = MyModel::find(1);
$myModel->myField = 'bar';

dump($myModel->myField); // foo
dump($myModel2->myField); // bar
```

## Security Vulnerabilities

Please [e-mail security vulnerabilities directly to me](mailto:matt@mralston.co.uk).

## Licence

Laravel EAV is open-sourced software licenced under the [MIT license](LICENSE.md).
