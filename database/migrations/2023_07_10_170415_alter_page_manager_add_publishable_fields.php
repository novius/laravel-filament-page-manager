<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Novius\LaravelPublishable\Enums\PublicationStatus;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('page_manager_pages', function (Blueprint $table) {
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

            $table->index(['publication_status', 'published_at', 'expired_at'], 'page_manager_pages_publishable');
        });

        DB::statement('UPDATE page_manager_pages SET publication_status = ?, published_first_at = publication_date
                          WHERE publication_date IS NOT NULL AND end_publication_date IS NULL', [PublicationStatus::published->value]);

        DB::statement('UPDATE page_manager_pages SET publication_status = ?, published_first_at = publication_date, published_at = publication_date, expired_at = end_publication_date
                          WHERE publication_date IS NOT NULL AND end_publication_date IS NOT NULL', [PublicationStatus::scheduled->value]);

        DB::statement('UPDATE page_manager_pages SET publication_status = ?, published_first_at = created_at, expired_at = end_publication_date
                          WHERE publication_date IS NULL AND end_publication_date IS NOT NULL', [PublicationStatus::unpublished->value]);

        Schema::table('page_manager_pages', function (Blueprint $table) {
            $table->dropColumn(['publication_date', 'end_publication_date']);
        });
    }

    public function down(): void
    {
        Schema::table('page_manager_pages', function (Blueprint $table) {
            $table->dateTime('publication_date')->nullable()->after('locale_parent_id');
            $table->dateTime('end_publication_date')->nullable()->after('publication_date');
        });

        DB::statement('UPDATE page_manager_pages SET publication_date = published_at
                          WHERE publication_status = ?', [PublicationStatus::published->value]);

        DB::statement('UPDATE page_manager_pages SET publication_date = published_at, end_publication_date = expired_at
                          WHERE publication_status = ?', [PublicationStatus::scheduled->value]);

        DB::statement('UPDATE page_manager_pages SET publication_date = null, end_publication_date = expired_at
                          WHERE publication_status = ?', [PublicationStatus::unpublished->value]);

        Schema::table('page_manager_pages', function (Blueprint $table) {
            $table->dropPublishable();
        });
    }
};
