<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientCreditTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'type',
        'amount',
        'balance_after',
        'description',
        'meta',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'float',
        'balance_after' => 'float',
        'meta' => 'array',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
