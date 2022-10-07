<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSkryfallDataToCards extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->mediumInteger('cmc')->nullable();
            $table->string('type_line')->nullable();
            $table->string('skryfall_image_small')->nullable();
            $table->string('skryfall_image_normal')->nullable();
            $table->string('skryfall_image_large')->nullable();
            $table->string('skryfall_image_png')->nullable();
            $table->text('color_identity')->nullable();
            $table->text('colors')->nullable();
            $table->string('color_order_by')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->dropColumn([
                'cmc',
                'type_line',
                'skryfall_image_name_small',
                'skryfall_image_name_normal',
                'skryfall_image_name_large',
                'skryfall_image_name_png',
                'color_identity',
                'colors',
                'color_order_by',
            ]);
        });
    }
}
