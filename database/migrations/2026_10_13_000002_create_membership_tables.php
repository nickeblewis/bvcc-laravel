<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('town')->nullable();
            $table->string('postcode')->nullable();
            $table->string('paddle_uk_number')->nullable();
            $table->date('paddle_uk_expires_on')->nullable();
            $table->text('experience')->nullable();
            // Encrypted at rest; see App\Models\Member casts.
            $table->text('emergency_contacts')->nullable();
            $table->text('medical_notes')->nullable();
            $table->boolean('photo_consent')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['last_name', 'first_name']);
        });

        Schema::create('membership_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('fee_band');
            $table->unsignedInteger('initial_pence')->default(0);
            $table->unsignedInteger('monthly_pence')->default(0);
            $table->string('eligibility')->nullable();
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('max_adults')->nullable();
            $table->boolean('covers_household')->default(false);
            $table->boolean('on_sale')->default(true);
            $table->boolean('to_confirm')->default(false);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membership_tier_id')->constrained()->restrictOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->date('starts_on');
            $table->date('expires_on')->index();
            $table->string('status')->index();
            $table->unsignedInteger('price_pence')->default(0);
            $table->unsignedInteger('monthly_pence')->default(0);
            $table->timestamp('last_chased_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('member_membership', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membership_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->unique(['membership_id', 'member_id']);
        });

        Schema::create('fee_rates', function (Blueprint $table) {
            $table->id();
            $table->string('fee_band');
            $table->string('fee_type');
            // Null means "not applicable" for this band.
            $table->unsignedInteger('amount_pence')->nullable();
            $table->timestamps();
            $table->unique(['fee_band', 'fee_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_rates');
        Schema::dropIfExists('member_membership');
        Schema::dropIfExists('memberships');
        Schema::dropIfExists('membership_tiers');
        Schema::dropIfExists('members');
    }
};
