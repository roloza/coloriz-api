<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Compress extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('compress', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_image')->unique();
            $table->integer('id_image_compress')->unique();
            $table->string('original_name');
            $table->integer('gain');
            $table->integer('gain_pct');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
