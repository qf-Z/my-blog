<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tag extends Model
{
    use SoftDeletes;
    protected $table = 'tags';

    protected $guarded = [];
    protected $dates = [
        'created_at',
        'updated_at',
    ];
}
