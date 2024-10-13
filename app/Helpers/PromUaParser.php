<?php

namespace App\Helpers;

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
            print_r($exception->getErrorDetails());
            exit;
        }


        $data = $results->getData();

        return $data['product'];

    }
}
