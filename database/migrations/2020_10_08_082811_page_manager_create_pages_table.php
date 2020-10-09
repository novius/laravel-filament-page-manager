<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PageManagerCreatePagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('page_manager_pages', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->string('slug');
            $table->string('locale');
            $table->string('template');

            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('locale_parent_id')->nullable();

            $table->dateTime('publication_date')->nullable();
            $table->dateTime('end_publication_date')->nullable();
            $table->string('preview_token');

            $table->string('seo_title');
            $table->string('seo_description');
            $table->unsignedTinyInteger('seo_robots')
                ->default(\Novius\LaravelNovaPageManager\Models\Page::ROBOTS_INDEX_FOLLOW);
            $table->string('seo_canonical_url')->nullable();

            $table->string('og_title')->nullable();
            $table->string('og_description')->nullable();
            $table->string('og_image')->nullable();

            $table->longText('extras')->nullable();

            $table->timestamps();

            $table->unique(['slug', 'locale']);

            $table->foreign('parent_id')
                ->references('id')
                ->on('page_manager_pages')
                ->onDelete('restrict')
                ->onUpdate('restrict');

            $table->foreign('locale_parent_id')
                ->references('id')
                ->on('page_manager_pages')
                ->onDelete('restrict')
                ->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('page_manager_pages');
    }
}
