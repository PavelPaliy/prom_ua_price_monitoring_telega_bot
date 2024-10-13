<?php

namespace App\Models;

use App\Casts\Json;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        "promua_id", "name", "price","discounted_price", "currency", "link", "history", "image"
    ];
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'history' => 'array',
        ];
    }
    public function chats(): BelongsToMany
    {
        return $this->belongsToMany(Chat::class, "product_telegraph_chat", "product_id", "telegraph_chat_id");
    }
}
