<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Billing clients, their invoices, invoice lines and payments.
     * Money is stored in minor units (pesewas, cents) to avoid rounding errors.
     */
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('company')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->text('address')->nullable();
            $table->string('tax_number', 60)->nullable();
            $table->char('currency', 3)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->string('number', 40)->unique();
            $table->string('subject')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->char('currency', 3);
            $table->bigInteger('subtotal')->default(0);
            $table->bigInteger('discount')->default(0);
            $table->string('tax_label', 40)->nullable();
            $table->decimal('tax_rate', 6, 3)->default(0);
            $table->bigInteger('tax_amount')->default(0);
            $table->bigInteger('total')->default(0);
            $table->bigInteger('amount_paid')->default(0);
            $table->json('bill_to')->nullable();
            $table->text('notes')->nullable();
            $table->text('payment_instructions')->nullable();
            $table->text('internal_notes')->nullable();
            $table->string('token_hash', 64)->unique();
            $table->text('token');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->text('description');
            $table->decimal('quantity', 12, 2);
            $table->bigInteger('unit_price');
            $table->bigInteger('amount');
            $table->timestamps();
        });

        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('amount');
            $table->date('paid_on');
            $table->string('method', 30);
            $table->string('reference', 120)->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $this->grantToAdministrators();
    }

    /**
     * Existing Administrator roles are not re-synced automatically, so give
     * them the new permissions here (matching the default role matrix).
     */
    protected function grantToAdministrators(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['invoices.view', 'invoices.manage'] as $name) {
            Permission::findOrCreate($name, 'web');
        }

        Role::query()->where('name', 'Administrator')->where('guard_name', 'web')->first()
            ?->givePermissionTo(['invoices.view', 'invoices.manage']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('clients');
    }
};
