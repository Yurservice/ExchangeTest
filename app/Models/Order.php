<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    public function user(): BelongsTo
	{
		return $this->belongsTo(User::class, 'user_id', 'id');
	}

	protected $fillable = [
        'user_id',
		'trade_side',
		'set_currency',
        'set_amount',
		'get_currency',
		'get_amount',
		'order_status'
    ];
}
