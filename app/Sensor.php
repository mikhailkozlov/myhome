<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Sensor extends Model
{
    /**
     * Energy data
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function energy()
    {
        return $this->hasMany('App\Energy');
    }

    /**
     * Power data
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function power()
    {
        return $this->hasMany('App\Power');
    }
}
