<?php

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
        Schema::table('pages', static function (Blueprint $table) {
            $table->dropForeign(['locale_parent_id']);

            $table->foreign('locale_parent_id')
                ->references('id')
                ->on('pages')
                ->nullOnDelete()
                ->nullOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', static function (Blueprint $table) {
            $table->dropForeign(['locale_parent_id']);

            $table->foreign('locale_parent_id')
                ->references('id')
                ->on('pages')
                ->onDelete('restrict')
                ->onUpdate('restrict');
        });
    }
};
