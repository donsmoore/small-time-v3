<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClockEvent extends Model
{
    protected $table = 'clockEvent';
    
    public $timestamps = false;
    
    protected $fillable = [
        'userId',
        'inOrOut',
        'eventTime',
    ];
    
    protected $casts = [
        'eventTime' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(ClockUser::class, 'userId');
    }
}
