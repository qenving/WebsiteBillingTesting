<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentLog extends Model
{
    use HasFactory;
    protected $fillable = ['payment_id','gateway','event','payload','signature','ip_address'];
    protected $casts = ['payload'=>'array'];
    public function payment(){ return $this->belongsTo(Payment::class); }
}

