<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
    protected $fillable = ['invoice_id','gateway','amount','currency','transaction_id','status','paid_at','meta'];
    protected $casts = ['paid_at'=>'datetime','meta'=>'array'];

    protected static function booted()
    {
        $syncInvoice = function (Payment $payment) {
            if ($payment->relationLoaded('invoice')) {
                $payment->invoice->refresh();
            }
            optional($payment->invoice)->refreshPaymentStatus();
        };

        static::saved($syncInvoice);
        static::deleted($syncInvoice);
    }

    public function invoice(){ return $this->belongsTo(Invoice::class); }
    public function logs(){ return $this->hasMany(PaymentLog::class); }
}

