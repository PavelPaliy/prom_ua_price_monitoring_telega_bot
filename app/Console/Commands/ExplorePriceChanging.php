<?php

namespace App\Console\Commands;

use App\Helpers\MailingHelper;
use App\Helpers\PromUaParser;
use App\Models\Product;
use Illuminate\Console\Command;

class ExplorePriceChanging extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:explore-price-changing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Explore price changing of existing products';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = strtotime("now");
        foreach (Product::all() as $product){

            $jsonFromPromUa = PromUaParser::getInfoOrNullAboutProductById($product->promua_id);
            if(isset($jsonFromPromUa['discountedPrice']) &&
                (float)$jsonFromPromUa['discountedPrice'] !== $product->discounted_price){


                $newPrice = (float)$jsonFromPromUa['discountedPrice'];
                $oldPrice = $product->discounted_price;
                $history = $product->history;
                if(!$history) $history = [];

                $history[$date] = ["new_price"=>$newPrice, "old_price"=>$oldPrice];

                $product->price = (float)$jsonFromPromUa['price'];
                $product->discounted_price = (float)$jsonFromPromUa['discountedPrice'];
                $product->history = $history;
                $product->save();
            }
        }

        MailingHelper::sendNotificationsAboutDiscount(Product::where("history->$date", "!=", null)->get(), $date);


        return Command::SUCCESS;
    }
}
