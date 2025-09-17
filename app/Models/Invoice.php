<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = ['client_id','number','status','subtotal','tax_total','discount_total','tax_rate','total','currency','due_date','paid_at','notes'];
    protected $casts = ['due_date'=>'date','paid_at'=>'datetime'];

    protected static function booted()
    {
        static::creating(function (Invoice $invoice) {
            if (empty($invoice->number)) {
                $invoice->number = static::generateNumber();
            }
        });
    }

    public static function generateNumber(): string
    {
        $prefix = (string) \App\Support\Settings::get('invoice.number_prefix', 'INV');
        $dateFormat = (string) \App\Support\Settings::get('invoice.number_date_format', 'Ymd');
        $scope = (string) \App\Support\Settings::get('invoice.sequence_scope', 'daily'); // daily|monthly|yearly|global
        $date = date($dateFormat);
        // Build counter key based on scope
        $scopeKey = date('Ymd');
        if ($scope === 'monthly') $scopeKey = date('Ym');
        elseif ($scope === 'yearly') $scopeKey = date('Y');
        elseif ($scope === 'global') $scopeKey = 'all';
        return \DB::transaction(function () use ($prefix, $date, $scopeKey) {
            $key = 'invoice:'.$scopeKey;
            $row = \DB::table('counters')->where('key',$key)->lockForUpdate()->first();
            if (! $row) { \DB::table('counters')->insert(['key'=>$key,'value'=>1,'created_at'=>now(),'updated_at'=>now()]); $seq=1; }
            else { $seq = $row->value+1; \DB::table('counters')->where('key',$key)->update(['value'=>$seq,'updated_at'=>now()]); }
            return $prefix.'-'.$date.'-'.str_pad((string)$seq,4,'0',STR_PAD_LEFT);
        });
    }

    public function client(){ return $this->belongsTo(Client::class); }
    public function items(){ return $this->hasMany(InvoiceItem::class); }
    public function payments(){ return $this->hasMany(Payment::class); }

    public function completedPaymentsSum(): float
    {
        if ($this->relationLoaded('payments')) {
            return (float) $this->payments->where('status', 'completed')->sum('amount');
        }
        return (float) $this->payments()->where('status', 'completed')->sum('amount');
    }

    public function balanceDue(): float
    {
        $due = round($this->total - $this->completedPaymentsSum(), 2);
        return $due > 0 ? $due : 0.0;
    }

    public function refreshPaymentStatus(): void
    {
        $balance = $this->balanceDue();
        if ($balance <= 0.0) {
            if ($this->status !== 'paid') {
                $this->status = 'paid';
                $this->paid_at = now();
                $this->saveQuietly();
                event(new \App\Events\InvoicePaid($this->fresh()));
            }
            return;
        }

        if ($this->status === 'paid') {
            $this->status = ($this->due_date && $this->due_date->isPast()) ? 'overdue' : 'unpaid';
            $this->paid_at = null;
            $this->saveQuietly();
        }
    }
}
