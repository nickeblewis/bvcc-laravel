<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('excerpt', 500)->nullable();
            $table->text('body')->nullable();
            $table->string('hero_image')->nullable();
            $table->string('hero_alt')->nullable();
            $table->foreignId('article_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('author_name')->nullable();
            $table->dateTime('published_at')->nullable()->index();
            $table->string('source_url')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('section');
            $table->string('slug');
            $table->string('title');
            $table->string('summary')->nullable();
            $table->text('body')->nullable();
            $table->json('facts')->nullable();
            $table->string('faq_category')->nullable();
            $table->string('hero_image')->nullable();
            $table->string('hero_alt')->nullable();
            $table->string('meta_description')->nullable();
            $table->date('reviewed_on')->nullable();
            $table->boolean('is_published')->default(true);
            $table->boolean('to_confirm')->default(false);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->string('source_url')->nullable();
            $table->timestamps();
            $table->unique(['section', 'slug']);
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category');
            $table->string('file');
            $table->date('document_date')->nullable();
            $table->boolean('is_public')->default(true);
            $table->string('source_url')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('committee_roles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('email')->nullable();
            $table->string('holder_name')->nullable();
            $table->string('description', 500)->nullable();
            $table->boolean('is_listed')->default(true);
            $table->boolean('takes_enquiries')->default(false);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('category');
            $table->string('question');
            $table->text('answer');
            $table->boolean('is_published')->default(true);
            $table->boolean('to_confirm')->default(false);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->foreignId('committee_role_id')->nullable()->constrained()->nullOnDelete();
            $table->text('message');
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('hire_requests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('use_type');
            $table->date('collect_on');
            $table->date('return_on');
            $table->unsignedTinyInteger('participants')->default(1);
            $table->json('items');
            $table->string('craft_type')->nullable();
            $table->text('intended_use')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('new')->index();
            $table->timestamps();
        });

        Schema::create('media_items', function (Blueprint $table) {
            $table->id();
            $table->string('file');
            $table->string('alt')->nullable();
            $table->string('caption')->nullable();
            $table->string('credit')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->boolean('is_imported')->default(false);
            $table->string('source_url')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_path')->unique();
            $table->string('to_path');
            $table->unsignedInteger('hits')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['redirects', 'media_items', 'hire_requests', 'enquiries', 'faqs', 'committee_roles', 'documents', 'pages', 'articles', 'article_categories'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
