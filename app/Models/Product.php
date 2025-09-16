<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['name','slug','description','base_price','currency','options','is_active'];
    protected $casts = ['options'=>'array','is_active'=>'boolean'];

    public function services(){ return $this->hasMany(Service::class); }
}

