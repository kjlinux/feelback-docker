<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Statistic extends Model
{
    use HasUuids, SoftDeletes;

    protected $guarded = ['id'];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
