<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class PromUaParser
{
    private const API_ENDPOINT = "https://prom.ua/graphql";
    public static function getInfoOrNullAboutProductById(int $id):array|null
    {
        try{
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://prom.ua/graphql');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded',
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "\r\n{\"operationName\":\"ChatInShopInShopQuery\",\"variables\":{\"productId\":{$id}},\"query\":\"query ChatInShopInShopQuery(\$productId: Long!) {\\n  product(id: \$productId) {\\n    id\\n    name\\n    company_id\\n    discountedPrice\\n    price\\n    priceCurrencyLocalized\\n    image(width: 80, height: 80)\\n    report_start_chat_url\\n    payPartsButtonText\\n    productTypeKey\\n    __typename\\n  }\\n  context {\\n    context_meta\\n    __typename\\n  }\\n  productAdvert(productId: \$productId) {\\n    commission_type\\n    __typename\\n  }\\n}\"}");
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);

            curl_close($ch);
            $result = json_decode($response, 1);
            return $result['data']['product'];
        }catch (\Throwable $throwable){
            Log::error("MainParser@getInfoOrNullAboutProductById: message:{$throwable->getMessage()}, line:{$throwable->getLine()}, file:{$throwable->getFile()}");
            return null;
        }

    }
}
