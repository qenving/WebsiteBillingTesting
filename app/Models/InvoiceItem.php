<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasFactory;
    protected $fillable = ['invoice_id','product_id','service_id','description','quantity','unit_price','discount','total','meta'];
    protected $casts = ['meta'=>'array'];
    public function invoice(){ return $this->belongsTo(Invoice::class); }
    public function product(){ return $this->belongsTo(Product::class); }
    public function service(){ return $this->belongsTo(Service::class); }
}

