<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $table = 'item';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'parcel_id',
        'tn_code',
        'tn_position',
        'measure_code',
        'quantity',
        'price',
        'currency',
        'model',
        'imei1',
        'imei2',
        'url'
    ];

    protected $casts = [
        'id' => 'integer',
        'parcel_id' => 'integer',
        'quantity' => 'integer',
        'price' => 'float'
    ];

    public function parcel()
    {
        return $this->belongsTo(Parcel::class, 'parcel_id', 'parcel_id');
    }
}
