<?php

use App\Enums\DomainStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->foreignId('domain_category_id')->nullable()
                ->constrained('domain_categories')->nullOnDelete();
            $table->string('registrar')->nullable();
            $table->enum('status', array_column(DomainStatus::cases(), 'value'))
                ->default(DomainStatus::Watching->value);
            $table->date('purchase_date');
            $table->date('renewal_date')->nullable();
            $table->date('expiration_date');
            $table->decimal('purchase_cost', 10, 2);
            $table->decimal('renewal_cost', 10, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->text('notes')->nullable();
            $table->foreignId('project_id')->nullable()
                ->constrained('projects')->nullOnDelete();
            $table->boolean('is_for_sale')->default(false);
            $table->boolean('auto_renew')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
