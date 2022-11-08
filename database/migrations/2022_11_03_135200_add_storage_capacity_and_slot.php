<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStorageCapacityAndSlot extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->mediumInteger('slot')->default(0)->after('storage_id');
        });

        Schema::table('storages', function (Blueprint $table) {
            $table->mediumInteger('slots')->default(0)->after('full_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn([
                'slot',
            ]);
        });

        Schema::table('storages', function (Blueprint $table) {
            $table->dropColumn([
                'slots',
            ]);
        });
    }
}
