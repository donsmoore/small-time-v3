<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClockUser extends Model
{
    protected $table = 'clockUser';
    
    public $timestamps = false;
    
    protected $fillable = [
        'name',
        'userCode',
        'groupId',
    ];
}
