<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'codicefiscale';

    public function up(): void
    {
        Schema::create('municipalities', function (Blueprint $table) {
            $table->id();
            $table->string('code', 4)->index();
            $table->string('name');
            $table->string('province', 2);
            $table->string('istat_code', 6);
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('municipalities');
    }
};
