<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use Tests\TestCase;
use App\Models\Quote;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class QuoteGetTotalPriceTest extends TestCase
{
    /**
     * 
     * @test
     */
    public function basicTestGetTotalPrice()
    {
        // Crea products
        $product1 = Product::factory()->create([
            'price' => 10,
        ]);
        $product2 = Product::factory()->create([
            'price' => 10,
        ]);
        $product3 = Product::factory()->create([
            'price' => 10,
        ]);

        //Crea Customer per associare id alla quote
        Customer::factory()->create();

        // Crea una quote ed associa i prodotti ad essa
        $quote = Quote::factory()->create();
        $quote->products()->attach([
            $product1->id => ['quantity' => 1],
            $product2->id => ['quantity' => 1],
            $product3->id => ['quantity' => 1],
        ]);

        // Verifica che il prezzo totale dei prodotti sia calcolato correttamente
        $this->assertEquals(30, $quote->getTotalPrice());
    }

    /**
     * 
     * @test
     */
    public function advancedTestGetTotalPrice()
    {
        $product1 = Product::factory()->create([
            'price' => 0.5,
        ]);
        $product2 = Product::factory()->create([
            'price' => 10.00
        ]);
        $product3 = Product::factory()->create([
            'price' => 0,
        ]);
        $product4 = Product::factory()->create([
            'price' => 999.99
        ]);
        $product5 = Product::factory()->create([
            'price' => 1234.56
        ]);
        Customer::factory()->create();


        $quote = Quote::factory()->create();
        $quote->products()->attach([
            $product1->id => ['quantity' => 100],
            $product2->id => ['quantity' => 25],
            $product3->id => ['quantity' => 9999],
            $product4->id => ['quantity' => 10],
            $product5->id => ['quantity' => 20],
        ]);

        $this->assertEquals(34991.1, $quote->getTotalPrice());
    }
}
