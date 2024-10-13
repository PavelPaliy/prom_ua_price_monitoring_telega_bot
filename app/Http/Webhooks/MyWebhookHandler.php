<?php

namespace App\Http\Webhooks;

use App\Helpers\PromUaParser;
use App\Models\Product;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Stringable;

class MyWebhookHandler extends WebhookHandler
{
    protected function setupChat(): void
    {
        parent::setupChat();
        app()->setLocale($this->chat->language);
    }

    public function menu()
    {
        $this->chat->markdown(__("Бот следит за снижением цен на товары с prom.ua, указанные пользователем."))->keyboard(Keyboard::make()->buttons([
            Button::make("➕ ".__("Добавить товар"))->action('addProduct'),
            Button::make('📋 '.__("Список добавленных товаров"))->action('productList'),
            Button::make('🏷️ '.__("Минимальный процент скидки"))->action('textMinPercentage'),
        ]))->send();
    }

    public function start()
    {
        $this->change_language();
    }
    public function textMinPercentage()
    {
        $this->chat->markdown(__("Введите минимальный процент скидки; сейчас он составляет")." {$this->chat->percentage}%")->send();
    }
    public function change_language()
    {
        $this->chat->markdown(__("Выберите язык"))->keyboard(Keyboard::make()->buttons([
            Button::make(__('Украинский'))->action('set_language')->param("language","uk"),
            Button::make(__("Русский"))->action('set_language')->param("language","ru"),
            Button::make(__('Английский'))->action('set_language')->param("language","en"),
            ]))->send();
    }
    public function set_language($language="uk")
    {
        app()->setLocale($language);
        $this->chat->language = $language;
        $this->chat->save();

        $this->menu();
    }
    public function productList($page = "1")
    {
        $page = (int)$page;
        $productsOnPage = 5;
        $totalProducts = $this->chat->products()->count();
        $totalPages = ceil($totalProducts/$productsOnPage);
        $arrWithPaginationButtons = [];
        if($page > 1){
            $arrWithPaginationButtons[] = Button::make("⬅️")->action('productList')->param("page",$page-1);
        }
        if($page < $totalPages){
            $arrWithPaginationButtons[] = Button::make("➡️")->action('productList')->param("page",$page+1);
        }

        $offset = ($page-1) * $productsOnPage;;
        $products = $this->chat->products()->limit($productsOnPage)->offset($offset)->get();
        if(count($products)===0){
            $this->chat->markdown(__("Нет товаров"))->send();
            return;
        }
        $resultString = "";
        $productButtonsArr = [];
        foreach ($products as $index => $product){
            $number = $index + 1;
            if($number > 1) $resultString .= PHP_EOL;
            $resultString .="{$number}. [\"{$product->name}\"]({$product->link}) {$product->discounted_price} {$product->currency}";
            $productButtonsArr[] = Button::make($number)->action('productInfo')->param("product_id", $product->id);
        }
        $keyBoard = Keyboard::make();
        if(count($productButtonsArr)>0) $keyBoard = $keyBoard->row($productButtonsArr);
        if(count($arrWithPaginationButtons) > 0) $keyBoard = $keyBoard->row($arrWithPaginationButtons);
        $this->chat->markdown($resultString)->keyboard(
            $keyBoard
        )->send();
    }



    public function productInfo($product_id)
    {
        $productObj = Product::where("id", $product_id)->firstOrFail();
        $resultString = "[\"{$productObj->name}\"]({$productObj->link}) {$productObj->discounted_price} {$productObj->currency}";
        $this->chat->markdown($resultString)->keyboard(Keyboard::make()->buttons([
            Button::make(__("Удалить товар"))->action('deleteProduct')->param("product_id", $productObj->id),
        ]))->send();
    }
    public function deleteProduct($product_id)
    {
        $productObj = Product::where("id", $product_id)->firstOrFail();

        $productObj->chats()->detach($this->chat->id);
        $chatProductIds = array_column($this->chat->products()->get()->toArray(), "id");
        if(!in_array($product_id, $chatProductIds)){
            $this->chat->markdown(__("Товар успешно удален!"))->send();
        }
    }

    public function addProduct()
    {
        $this->chat->markdown(__("Отправьте одну или несколько ссылок, разделенных пробелами, на товар(ы) с сайта prom.ua."))->send();
    }

    protected function handleChatMessage(Stringable $text): void
    {
        $text = $text->__toString();
        if((int)$text>0){
            $this->chat->percentage = (int)$text;
            if($this->chat->save()){
                $this->chat->markdown(__('Скидка успешно изменена на')." {$this->chat->percentage}%")->send();
            }
        }else{

            $arr = explode(" ", $text);
            $addedProductsNumber = 0;

            foreach ($arr as $url){
                try{
                    if(filter_var($url, FILTER_VALIDATE_URL) && isset(parse_url($url)['host']) && parse_url($url)['host'] === "prom.ua" && isset(parse_url($url)['path'])){
                        $path = parse_url($url)['path'];
                        if(preg_match("/\/p(\d+)/", $path, $matches) && isset($matches[1])){
                            $infoArray = PromUaParser::getInfoOrNullAboutProductById($matches[1]);

                            $product = Product::firstOrCreate([
                                "promua_id"=>$infoArray['id']
                            ], [
                                "name" => $infoArray['name'],
                                "price"=>$infoArray['price'],
                                "discounted_price"=>$infoArray['discountedPrice'],
                                "currency"=>$infoArray['priceCurrencyLocalized'],
                                "link"=>"https://prom.ua{$path}",
                                "image"=>str_replace("_w80_h80", "",$infoArray["image"]),
                                "history"=>null
                            ]);

                            $product = Product::where("promua_id", $infoArray['id'])->firstOrFail();

                            if(!$product->chats()->where("telegraph_chat_id", $this->chat->id)->first())
                                $product->chats()->attach($this->chat->id);
                            if($product){
                                $addedProductsNumber++;
                            }
                        }
                    }
                }catch (\Throwable $throwable){
                    Log::error("MyWebhookHandler@handleChatMessage: message:{$throwable->getMessage()}, line:{$throwable->getLine()}, file:{$throwable->getFile()}");

                }

            }

            if($addedProductsNumber === 1 && isset($product->image)){
                $this->chat->photo($product->image)->markdown("✅ ".__('Товар')." [\"{$product->name}\"]({$product->link}) ".__('успешно добавлен').".".PHP_EOL."💰 ".__('Цена со скидкой').": {$product->discounted_price} {$product->currency}")->send();
            }else{
                $this->chat->markdown(__("Товары добавлено!"))->send();
            }
        }


    }
}
