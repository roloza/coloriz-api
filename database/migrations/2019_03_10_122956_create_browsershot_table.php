<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBrowsershotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('browsershots', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_image');
            $table->string('url');
            $table->string('type');
            $table->string('device');
            $table->integer('width');
            $table->integer('height');
            $table->boolean('fullpage');
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
        Schema::dropIfExists('browsershot');
    }
}
