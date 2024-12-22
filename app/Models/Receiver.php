<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receiver extends Model
{
    protected $table = 'receiver';
    protected $primaryKey = 'receiver_id';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'phone_nums',
        'email',
        'passport_id',
        'inn',
        'birth_date'
    ];

    protected $casts = [
        'receiver_id' => 'integer',
        'inn' => 'integer',
        'birth_date' => 'date'
    ];

    public function parcels()
    {
        return $this->hasMany(Parcel::class, 'receiver_id', 'receiver_id');
    }
}
