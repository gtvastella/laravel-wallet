<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'amount',
        'sender_id',
        'recipient_id',
        'status',
        'reference_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function originalTransaction()
    {
        return $this->belongsTo(Transaction::class, 'reference_id');
    }

    public function reversalTransaction()
    {
        return $this->hasOne(Transaction::class, 'reference_id');
    }

    public function isReversal()
    {
        return $this->type === 'reversal';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isReversed()
    {
        return $this->status === 'reversed';
    }
}
