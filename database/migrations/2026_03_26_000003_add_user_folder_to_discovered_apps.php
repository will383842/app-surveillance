<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discovered_apps', function (Blueprint $table) {
            $table->string('folder')->nullable()->default(null)->after('analyzed_at');
            $table->index('folder');
        });
    }

    public function down(): void
    {
        Schema::table('discovered_apps', function (Blueprint $table) {
            $table->dropColumn('folder');
        });
    }
};
