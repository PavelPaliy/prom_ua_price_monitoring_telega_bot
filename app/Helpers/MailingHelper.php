<?php

namespace App\Helpers;

use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use Illuminate\Database\Eloquent\Collection;

class MailingHelper
{
    public static function sendNotificationsAboutDiscount(Collection $products, int $dateInUnixTimestamp):void
    {

        foreach ($products as $product){
            if(isset($product->history[$dateInUnixTimestamp]["new_price"]) && isset($product->history[$dateInUnixTimestamp]["old_price"])){

                if($product->history[$dateInUnixTimestamp]["new_price"] < $product->history[$dateInUnixTimestamp]["old_price"]){
                   foreach ($product->chats()->get() as $chat){
                       app()->setLocale($chat->language);
                       $diff = $product->history[$dateInUnixTimestamp]["old_price"] - $product->history[$dateInUnixTimestamp]["new_price"];
                       $percentage = round(($diff * 100)/$product->history[$dateInUnixTimestamp]["old_price"]);
                       $currency = $product->currency;
                       $minPercentage = $chat->percentage;

                       if($minPercentage<=$percentage)
                            $chat->markdownV2(__("Снижение цены товара ")."[\"{$product->name}\"]({$product->link}) ".__("на")." $diff $currency \($percentage %\)\n\n" .__("Было")." ~{$product->history[$dateInUnixTimestamp]["old_price"]} {$product->currency}~ \| ".__("Стало")." {$product->history[$dateInUnixTimestamp]["new_price"]} {$product->currency}")
                                ->keyboard(Keyboard::make()->buttons([
                                    Button::make(__("Удалить товар"))->action('deleteProduct')->param("product_id", $product->id),
                                ]))
                                ->send();
                   }
                }
            }
        }
    }
}
