<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DualPricingLocale extends Model
{
     use HasFactory;

    protected $fillable = ['user_id', 'locale', 'installed'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
