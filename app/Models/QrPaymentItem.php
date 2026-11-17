<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrPaymentItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['payable_id', 'description', 'amount'];

    protected $casts = ['amount' => 'integer'];

    public function payable(): BelongsTo
    {
        return $this->belongsTo(Payable::class);
    }
}
