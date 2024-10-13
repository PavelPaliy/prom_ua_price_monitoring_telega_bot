<?php

namespace App\Data;


use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Data;

class ProductData extends Data
{
    #[Min(1)]
    public int $id;
    #[Max(255)]
    public string $name;
    #[Min(1)]
    public int $company_id;
    #[Min(0)]
    public float $discountedPrice;
    #[Min(0)]
    public float $price;
    #[Max(10)]
    public string $priceCurrencyLocalized;
    #[Url]
    public string $image;

}
