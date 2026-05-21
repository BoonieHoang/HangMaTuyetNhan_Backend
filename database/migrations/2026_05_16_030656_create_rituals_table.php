<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('rituals', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->text('summary')->nullable();
            $table->text('significance')->nullable();
            $table->text('preparation')->nullable();
            $table->text('prayer_text')->nullable();
            $table->integer('views')->default(0);
            $table->timestamps();
        });


    }
    public function down()
    {
        Schema::dropIfExists('rituals');
    }
};
