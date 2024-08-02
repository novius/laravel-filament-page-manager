<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Novius\LaravelMeta\Enums\IndexFollow;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_manager_pages', static function (Blueprint $table) {
            $table->addMeta();
        });

        $pages = DB::table('page_manager_pages')->get();
        foreach ($pages as $page) {
            $meta = [
                'seo_robots' => match ($page->seo_robots) {
                    1 => IndexFollow::index_follow->value,
                    2 => IndexFollow::index_nofollow->value,
                    3 => IndexFollow::noindex_nofollow->value,
                    4 => IndexFollow::noindex_follow->value,
                },
                'seo_title' => $page->seo_title,
                'seo_description' => $page->seo_description,
                'seo_canonical_url' => $page->seo_canonical_url,
                'og_title' => $page->og_title,
                'og_description' => $page->og_description,
                'og_image' => $page->og_image,
            ];
            DB::table('page_manager_pages')->where('id', $page->id)->update([
                'meta' => json_encode($meta, JSON_THROW_ON_ERROR),
            ]);
        }

        Schema::table('page_manager_pages', static function (Blueprint $table) {
            $table->dropColumn([
                'seo_robots',
                'seo_title',
                'seo_description',
                'seo_canonical_url',
                'og_title',
                'og_description',
                'og_image',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('page_manager_pages', static function (Blueprint $table) {
            $table->string('seo_title');
            $table->string('seo_description');
            $table->unsignedTinyInteger('seo_robots')->default(1);
            $table->string('seo_canonical_url')->nullable();

            $table->string('og_title')->nullable();
            $table->string('og_description')->nullable();
            $table->string('og_image')->nullable();
        });

        $pages = DB::table('page_manager_pages')->get();
        foreach ($pages as $page) {
            $meta = json_decode($page->meta, false, 512, JSON_THROW_ON_ERROR);
            DB::table('page_manager_pages')->where('id', $page->id)->update([
                'seo_robots' => $meta->seo_robots ?? 1,
                'seo_title' => $meta->seo_title ?? null,
                'seo_description' => $meta->seo_description ?? null,
                'seo_canonical_url' => $page->seo_canonical_url ?? null,
                'og_title' => $meta->og_title ?? null,
                'og_description' => $meta->og_description ?? null,
                'og_image' => $meta->og_image ?? null,
            ]);
        }

        Schema::table('page_manager_pages', static function (Blueprint $table) {
            $table->dropColumn('meta');
        });
    }
};
