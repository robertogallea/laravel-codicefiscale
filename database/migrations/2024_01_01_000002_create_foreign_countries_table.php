<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'codicefiscale';

    public function up(): void
    {
        Schema::create('foreign_countries', function (Blueprint $table) {
            $table->id();
            $table->string('code', 4)->index();
            $table->string('name');
            $table->string('country_code', 3);
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('foreign_countries');
    }
};
