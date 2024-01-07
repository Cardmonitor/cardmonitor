<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddsSyncActionToArticlesExternalIdsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('articles_external_ids', function (Blueprint $table) {
            $table->string('sync_action')->nullable()->after('sync_message');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('articles_external_ids', function (Blueprint $table) {
            $table->dropColumn('sync_action');
        });
    }
}
