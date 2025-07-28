<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Feedback extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'feedbacks';

    protected $guarded = ['id'];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
