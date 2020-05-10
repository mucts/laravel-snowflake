# Laravel Snowflake

> This Laravel package to generate 64 bit identifier like the snowflake within Twitter.

# Laravel Installation
```
composer require "mucts/laravel-snowflake"

```

Generate snowflake identifier
```
$id = Snowflake::next();
```
# Usage with Eloquent
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


# Configuration
If `config/snowflake.php` not exist, run below:
```
php artisan vendor:publish
```
