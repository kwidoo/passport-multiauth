<?php

namespace Kwidoo\MultiAuth\Models;

use Illuminate\Database\Eloquent\Model;

class OTP extends Model
{
    /**
     * @var string
     */
    protected $table = 'otps';

    /**
     * @var string[]
     */
    protected $fillable = ['username', 'method', 'code', 'expires_at', 'verified_at'];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];
}
