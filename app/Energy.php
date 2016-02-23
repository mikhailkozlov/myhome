<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Energy extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'energy';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['sensor_id', 'node', 'instance', 'value'];

}
