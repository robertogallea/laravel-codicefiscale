<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'codicefiscale';

    public function up(): void
    {
        // Nullable: an already-populated installation upgrading the
        // package must not fail this migration on existing rows. It's
        // backfilled the next time codice-fiscale:update-places runs,
        // since the importers upsert every row on every run.
        Schema::table('municipalities', function (Blueprint $table) {
            $table->string('name_normalized')->nullable()->index();
        });

        Schema::table('foreign_countries', function (Blueprint $table) {
            $table->string('name_normalized')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('municipalities', function (Blueprint $table) {
            $table->dropIndex(['name_normalized']);
            $table->dropColumn('name_normalized');
        });

        Schema::table('foreign_countries', function (Blueprint $table) {
            $table->dropIndex(['name_normalized']);
            $table->dropColumn('name_normalized');
        });
    }
};
