<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddsSourceToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('source_slug')->after('user_id');
            $table->unsignedBigInteger('source_id')->after('source_slug');

            $table->index(['source_slug', 'source_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['source_slug', 'source_id']);

            $table->dropColumn('source_slug');
            $table->dropColumn('source_id');
        });
    }
}
