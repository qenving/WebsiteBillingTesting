<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = ['client_id','product_id','external_id','status','billing_cycle','next_due_date','meta','suspended_at','terminated_at'];
    protected $casts = ['meta'=>'array','next_due_date'=>'date','suspended_at'=>'datetime','terminated_at'=>'datetime'];

    public function client(){ return $this->belongsTo(Client::class); }
    public function product(){ return $this->belongsTo(Product::class); }
}

