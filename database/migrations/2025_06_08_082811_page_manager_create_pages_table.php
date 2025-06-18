<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Novius\LaravelPublishable\Enums\PublicationStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pages', static function (Blueprint $table) {
            $table->id();

            $table->string('title', 191);
            $table->string('slug', 191);
            $table->string('locale', 15);
            $table->string('template');

            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('locale_parent_id')->nullable();

            $table->enum('publication_status', array_column(PublicationStatus::cases(), 'value'))
                ->default(PublicationStatus::draft->value)
                ->after('locale_parent_id');
            $table->timestamp('published_first_at')
                ->nullable()
                ->index()
                ->after('publication_status');
            $table->timestamp('published_at')
                ->nullable()
                ->after('published_first_at');
            $table->timestamp('expired_at')
                ->nullable()
                ->after('published_first_at');

            $table->string('preview_token');

            $table->addMeta();

            $table->longText('extras')->nullable();

            $table->timestamps();

            $table->unique(['slug', 'locale']);
            $table->index(['publication_status', 'published_at', 'expired_at'], 'page_manager_pages_publishable');

            $table->foreign('parent_id')
                ->references('id')
                ->on('pages')
                ->onDelete('restrict')
                ->onUpdate('restrict');

            $table->foreign('locale_parent_id')
                ->references('id')
                ->on('pages')
                ->onDelete('restrict')
                ->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
