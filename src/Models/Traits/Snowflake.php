<?php


namespace MuCTS\LaravelSnowflake\Models\Traits;


use MuCTS\LaravelSnowflake\Facades\Snowflake as SnowflakeFacade;

trait Snowflake
{
    protected static function boolSnowflake()
    {
        static::saving(function ($model) {
            if (is_null($model->getKey())) {
                $model->setIncrementing(false);
                $keyName = $model->getKeyName();
                $id = SnowflakeFacade::next();
                $model->setAttribute($keyName, $id);
            }
        });
    }
}