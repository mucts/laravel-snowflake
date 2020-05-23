<p align="center"><img src="https://images.mucts.com/image/mcts.png" width="400"></p>
<p align="center">
    <a href="https://scrutinizer-ci.com/g/mucts/laravel-snowflake"><img src="https://scrutinizer-ci.com/g/mucts/laravel-snowflake/badges/build.png" alt="Build Status"></a>
    <a href="https://scrutinizer-ci.com/g/mucts/laravel-snowflake"><img src="https://scrutinizer-ci.com/g/mucts/laravel-snowflake/badges/code-intelligence.svg" alt="Code Intelligence Status"></a>
    <a href="https://scrutinizer-ci.com/g/mucts/laravel-snowflake"><img src="https://scrutinizer-ci.com/g/mucts/laravel-snowflake/badges/quality-score.png" alt="Scrutinizer Code Quality"></a>
    <a href="https://packagist.org/packages/mucts/laravel-snowflake"><img src="https://poser.pugx.org/mucts/laravel-snowflake/d/total.svg" alt="Total Downloads"></a>
    <a href="https://packagist.org/packages/mucts/laravel-snowflake"><img src="https://poser.pugx.org/mucts/laravel-snowflake/v/stable.svg" alt="Latest Stable Version"></a>
    <a href="https://packagist.org/packages/mucts/laravel-snowflake"><img src="https://poser.pugx.org/mucts/laravel-snowflake/license.svg" alt="License"></a>
</p>

# Laravel Snowflake

> This Laravel package to generate 64 bit identifier like the snowflake within Twitter.

## Installation

### Server Requirements
>you will need to make sure your server meets the following requirements:

- `php ^7.4`
- `JSON PHP Extension`
- `OpenSSL PHP Extension`
- `GMP PHP Extension`
- `BCMath PHP Extension`
- `laravel/framework ^7.0`


### Laravel Installation
```
composer require "mucts/laravel-snowflake"

```

## Usage

### Generate snowflake identifier
```php
$id = Snowflake::next();
```

### Analysis snowflake identifier

```php
$info = Snowflake::info($id);
```

### Usage with Eloquent
Add the `MuCTS\LaravelSnowflake\Models\Traits\Snowflake` trait to your Eloquent model.
This trait make type `snowflake` of primary key. Trait will automatically set $incrementing property to false.

``` php
<?php
namespace App;

use MuCTS\LaravelSnowflake\Models\Traits\Snowflake;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Snowflake, Notifiable;
}
```

Finally, in migrations, set the primary key to `bigInteger`, `unsigned` and `primary`.

``` php
/**
 * Run the migrations.
 *
 * @return void
 */
public function up()
{
    Schema::create('users', function (Blueprint $table) {
        // $table->increments('id');
        $table->bigInteger('id')->unsigned()->primary();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('password');
        $table->rememberToken();
        $table->timestamps();
    });
}
```


## Configuration
If `config/snowflake.php` not exist, run below:
```
php artisan vendor:publish
```
