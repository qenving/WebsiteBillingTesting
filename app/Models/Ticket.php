<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'reference',
        'subject',
        'status',
        'priority',
        'department',
        'last_reply_at',
        'last_reply_by',
    ];

    protected $casts = [
        'last_reply_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function (Ticket $ticket) {
            if (empty($ticket->reference)) {
                $ticket->reference = static::generateReference();
            }
        });
    }

    public static function generateReference(): string
    {
        return DB::transaction(function () {
            $key = 'ticket:'.date('Ym');
            $row = DB::table('counters')->where('key', $key)->lockForUpdate()->first();
            if (! $row) {
                DB::table('counters')->insert([
                    'key' => $key,
                    'value' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $seq = 1;
            } else {
                $seq = $row->value + 1;
                DB::table('counters')->where('key', $key)->update([
                    'value' => $seq,
                    'updated_at' => now(),
                ]);
            }

            return sprintf('TCK-%s-%04d', date('Ym'), $seq);
        });
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function replies()
    {
        return $this->hasMany(TicketReply::class)->orderBy('created_at');
    }

    public function lastResponder()
    {
        return $this->belongsTo(User::class, 'last_reply_by');
    }

    public function addReply(?User $user, string $message, bool $isInternal = false): TicketReply
    {
        $reply = $this->replies()->create([
            'user_id' => $user?->id,
            'message' => $message,
            'is_internal' => $isInternal,
        ]);

        $this->last_reply_at = now();
        $this->last_reply_by = $user?->id;
        if (! $isInternal) {
            $this->status = $user && $user->is_admin ? 'answered' : 'open';
        }
        $this->save();

        return $reply;
    }

    public function scopeForClient($query, Client $client)
    {
        return $query->where('client_id', $client->id);
    }
}
