<?php

use App\Enums\OfferStatus;
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
        Schema::create('domain_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('domains')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('email');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->text('message')->nullable();
            $table->enum('status', array_column(OfferStatus::cases(), 'value'))
                ->default(OfferStatus::Pending->value);
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_offers');
    }
};
