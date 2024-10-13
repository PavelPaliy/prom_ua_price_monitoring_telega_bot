<?php

namespace App\Helpers;

use App\Data\ProductData;
use GraphQL\Client;
use GraphQL\Exception\QueryError;
use GraphQL\Query;
use GraphQL\Variable;
use Illuminate\Support\Facades\Log;

class PromUaParser
{
    private const API_ENDPOINT = "https://prom.ua/graphql";
    public static function getInfoOrNullAboutProductById(int $id):array|null
    {
        try {
            $client = new Client(
                self::API_ENDPOINT
            );
            $gql = (new Query('product'))
                ->setVariables([new Variable('productId', 'Long', true)])
                ->setArguments(['id' => '$productId'])
                ->setSelectionSet(
                    [
                        'id',
                        'name',
                        'company_id',
                        'discountedPrice',
                        'price',
                        'priceCurrencyLocalized',
                        'image(width: 80, height: 80)'
                    ]
                );
            $results = $client->runQuery($gql, true, ['productId' => $id]);
        }
        catch (QueryError $exception) {
            Log::error("PromUaParser@getInfoOrNullAboutProductById ".var_export($exception->getErrorDetails(), 1));
            return null;
        }

        try {
            $data = $results->getData();

            return ProductData::validate($data['product']);
        }catch (\Throwable $throwable){
            Log::error("PromUaParser@getInfoOrNullAboutProductById Message:{$throwable->getMessage()}, file:{$throwable->getFile()}, line: {$throwable->getLine()}");
            return null;
        }

    }
}
