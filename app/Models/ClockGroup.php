<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClockGroup extends Model
{
    protected $table = 'clockGroup';
    
    public $timestamps = false;
    
    protected $fillable = [
        'groupName',
        'weekStartDOW',
        'weekStartTime',
    ];
}
