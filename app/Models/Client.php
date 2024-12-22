<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Client extends Model
{
    use HasFactory;
    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'client';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'client_id';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'integer';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'client_id',
        'name',
        'contact',
        'country_code',
        'address',
        'url',
        'token',
        'webhook'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'client_id' => 'integer',
    ];
}
