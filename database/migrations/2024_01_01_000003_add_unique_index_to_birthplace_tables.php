<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'codicefiscale';

    public function up(): void
    {
        // A (code, valid_from) pair uniquely identifies one era-record,
        // even though a code has many rows across its history. Required
        // for upsert() to target the right row instead of duplicating.
        Schema::table('municipalities', function (Blueprint $table) {
            $table->unique(['code', 'valid_from']);
        });

        Schema::table('foreign_countries', function (Blueprint $table) {
            $table->unique(['code', 'valid_from']);
        });
    }

    public function down(): void
    {
        Schema::table('municipalities', function (Blueprint $table) {
            $table->dropUnique(['code', 'valid_from']);
        });

        Schema::table('foreign_countries', function (Blueprint $table) {
            $table->dropUnique(['code', 'valid_from']);
        });
    }
};
