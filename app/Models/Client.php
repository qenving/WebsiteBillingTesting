<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id','company','phone','address_line1','address_line2','city','state','country','postal_code','tax_id','credit_balance','currency'
    ];

    public function user(){ return $this->belongsTo(User::class); }
    public function services(){ return $this->hasMany(Service::class); }
    public function invoices(){ return $this->hasMany(Invoice::class); }
}

