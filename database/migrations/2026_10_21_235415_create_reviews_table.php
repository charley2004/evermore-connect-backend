<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->string('site'); // EVERMORE | optinex | incossify | glamour | ...
            $table->string('name', 80);
            $table->string('location', 100)->default('');
            $table->unsignedTinyInteger('rating'); // 1-5
            $table->string('body', 1000);
            $table->boolean('approved')->default(false);
            $table->timestamps();

            $table->index(['site', 'approved']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
