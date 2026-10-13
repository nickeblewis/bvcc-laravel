<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venues', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('summary')->nullable();
            $table->string('address')->nullable();
            $table->string('town')->nullable();
            $table->string('postcode')->nullable();
            $table->decimal('latitude', 9, 6)->nullable();
            $table->decimal('longitude', 9, 6)->nullable();
            $table->text('description')->nullable();
            $table->text('access_notes')->nullable();
            $table->string('hero_image')->nullable();
            $table->string('hero_alt')->nullable();
            $table->boolean('is_listed')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('disciplines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('summary')->nullable();
            $table->text('body')->nullable();
            $table->json('facts')->nullable();
            $table->string('hero_image')->nullable();
            $table->string('hero_alt')->nullable();
            $table->string('video_url')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('meta_description')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('event_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('colour')->default('teal');
            $table->string('description')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('summary')->nullable();
            $table->text('description')->nullable();
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at')->nullable();
            $table->boolean('all_day')->default(false);
            $table->foreignId('venue_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('event_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('discipline_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('leader_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->dateTime('booking_closes_at')->nullable();
            $table->unsignedTinyInteger('grade')->nullable();
            $table->string('suitability')->nullable();
            $table->string('fee_type');
            $table->json('custom_prices')->nullable();
            $table->string('extra_costs')->nullable();
            $table->string('hero_image')->nullable();
            $table->string('hero_alt')->nullable();
            $table->string('status')->index();
            $table->uuid('series_id')->nullable()->index();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->string('contact_name');
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('status')->index();
            $table->text('notes')->nullable();
            $table->timestamp('last_chased_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('booking_attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('fee_band');
            $table->unsignedInteger('price_pence')->default(0);
            $table->string('waived_reason')->nullable();
            $table->timestamp('attended_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->morphs('payable');
            // Negative amounts are refunds.
            $table->integer('amount_pence');
            $table->string('currency', 3)->default('GBP');
            $table->string('method');
            $table->date('received_on')->index();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('booking_attendees');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('events');
        Schema::dropIfExists('event_categories');
        Schema::dropIfExists('disciplines');
        Schema::dropIfExists('venues');
    }
};
