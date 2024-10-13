<?php

namespace App\Models;

use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Chat extends TelegraphChat
{
    protected $table = 'telegraph_chats';

    public function products():BelongsToMany
    {
        return $this->belongsToMany(Product::class, "product_telegraph_chat", "telegraph_chat_id", "product_id");
    }
    protected $fillable = [
        'chat_id',
        'name',
        "percentage",
        "language"
    ];
    public function getPercentage()
    {
        return $this->percentage;
    }
}
