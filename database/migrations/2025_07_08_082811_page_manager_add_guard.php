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
            $table->string('guard')->nullable()->after('special');
            $table->index(['guard']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', static function (Blueprint $table) {
            $table->dropColumn('guard');
        });
    }
};
