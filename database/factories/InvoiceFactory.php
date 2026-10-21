<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Creates an invoice with one line. Totals are set directly, so use
 * App\Services\Invoicing when a test needs them recalculated.
 *
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'number' => 'INV-TEST-'.fake()->unique()->numerify('#####'),
            'issue_date' => today(),
            'due_date' => today()->addDays(14),
            'currency' => 'GHS',
            'subtotal' => 100000,
            'total' => 100000,
            'status' => InvoiceStatus::Draft,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Invoice $invoice): void {
            $invoice->items()->create(['position' => 0, 'description' => 'Website design', 'quantity' => 1, 'unit_price' => $invoice->subtotal, 'amount' => $invoice->subtotal]);
        });
    }

    public function sent(): static
    {
        return $this->state(fn () => ['status' => InvoiceStatus::Sent, 'sent_at' => now()]);
    }

    public function overdue(): static
    {
        return $this->state(fn () => ['status' => InvoiceStatus::Sent, 'sent_at' => now()->subMonth(), 'issue_date' => today()->subMonth(), 'due_date' => today()->subDays(3)]);
    }
}
