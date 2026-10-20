<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Product;
use Database\Factories\Concerns\PublicationStates;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    use PublicationStates;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->words(2, true)),
            'tagline' => fake()->sentence(5),
            'short_description' => fake()->sentence(18),
            'product_status' => ProductStatus::InDevelopment,
            'demo_requests_enabled' => true,
            'enquiries_enabled' => true,
            'pricing_visible' => false,
            'is_featured' => false,
            'sort_order' => 0,
            'status' => 'draft',
        ];
    }
}
