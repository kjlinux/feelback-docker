<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Device extends Model
{
    use HasUuids, SoftDeletes;

    protected $guarded = ['id'];

    public function feedbacks()
    {
        return $this->hasMany(Feedback::class);
    }

    public function statistics()
    {
        return $this->hasMany(Statistic::class);
    }
}
