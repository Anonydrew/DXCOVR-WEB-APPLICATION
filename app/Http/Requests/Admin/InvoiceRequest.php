<?php

namespace App\Http\Requests\Admin;

use App\Models\Invoice;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvoiceRequest extends FormRequest
{
    public const MAX_ITEMS = 100;

    public function authorize(): bool
    {
        return $this->user()->can('invoices.manage');
    }

    /**
     * Drops empty lines and normalises money fields before validation.
     */
    protected function prepareForValidation(): void
    {
        $items = collect((array) $this->input('items', []))
            ->filter(fn ($item) => is_array($item) && (filled($item['description'] ?? null) || filled($item['unit_price'] ?? null)))
            ->map(fn (array $item) => [
                'description' => is_string($item['description'] ?? null) ? trim($item['description']) : null,
                'quantity' => $this->number($item['quantity'] ?? null) ?? 1,
                'unit_price' => $this->number($item['unit_price'] ?? null),
            ])
            ->values()
            ->all();

        $this->merge([
            'items' => $items,
            'currency' => strtoupper(trim((string) $this->input('currency'))),
            'number' => trim((string) $this->input('number')),
            'discount' => $this->number($this->input('discount')) ?? 0,
            'tax_rate' => $this->number($this->input('tax_rate')) ?? 0,
        ]);
    }

    protected function number(mixed $value): ?string
    {
        $clean = str_replace([',', ' '], '', trim((string) $value));

        return $clean === '' ? null : $clean;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $invoice = $this->route('invoice');

        return [
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'number' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9\/._-]+$/', Rule::unique('invoices', 'number')->ignore($invoice instanceof Invoice ? $invoice->id : null)],
            'subject' => ['nullable', 'string', 'max:200'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'currency' => ['required', 'string', 'regex:/^[A-Z]{3}$/'],
            'items' => ['required', 'array', 'min:1', 'max:'.self::MAX_ITEMS],
            'items.*.description' => ['required', 'string', 'max:2000'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:10000000000'],
            'discount' => ['numeric', 'min:0', 'max:10000000000'],
            'tax_label' => ['nullable', 'string', 'max:40'],
            'tax_rate' => ['numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'payment_instructions' => ['nullable', 'string', 'max:2000'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'client_id' => 'client',
            'number' => 'invoice number',
            'items' => 'line items',
            'items.*.description' => 'description',
            'items.*.quantity' => 'quantity',
            'items.*.unit_price' => 'price',
            'tax_rate' => 'tax rate',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Add at least one line item.',
            'items.min' => 'Add at least one line item.',
            'number.regex' => 'Use letters, numbers, dashes, dots or slashes only.',
            'currency.regex' => 'Use a 3-letter currency code, such as GHS or USD.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function invoiceData(): array
    {
        $data = collect($this->validated())->except('items')->all();
        $data['discount'] = Money::toMinor($data['discount'] ?? 0) ?? 0;
        $data['tax_rate'] = round((float) ($data['tax_rate'] ?? 0), 3);
        $data['tax_label'] = $data['tax_rate'] > 0 ? ($data['tax_label'] ?: 'Tax') : ($data['tax_label'] ?? null);

        return $data;
    }

    /**
     * @return list<array{description: string, quantity: string, unit_price: int}>
     */
    public function lineItems(): array
    {
        return array_map(fn (array $item) => [
            'description' => $item['description'],
            'quantity' => (string) $item['quantity'],
            'unit_price' => (int) Money::toMinor($item['unit_price']),
        ], $this->validated()['items']);
    }
}
