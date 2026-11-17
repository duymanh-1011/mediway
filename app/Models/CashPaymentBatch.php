<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashPaymentBatch extends Model
{
    protected $fillable = ['medical_visit_id', 'receipt_code'];

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }
}
