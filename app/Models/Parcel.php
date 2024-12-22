<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parcel extends Model
{
    protected $table = 'parcels';
    protected $primaryKey = 'parcel_id';
    public $timestamps = false;

    protected $fillable = [
        'order_no',
        'client_id',
        'receiver_id',
        'items_id',
        'current_status'
    ];

    protected $casts = [
        'parcel_id' => 'integer',
        'client_id' => 'integer',
        'receiver_id' => 'integer',
        'items_id' => 'string',
        'current_status' => 'string'
    ];

    public function items()
    {
        return $this->hasMany(Item::class, 'parcel_id', 'parcel_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }

    public function receiver()
    {
        return $this->belongsTo(Receiver::class, 'receiver_id', 'receiver_id');
    }
}
