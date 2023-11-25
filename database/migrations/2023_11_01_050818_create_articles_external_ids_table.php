<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArticlesExternalIdsTable extends Migration
{
    public function up()
    {
        Schema::create('articles_external_ids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id');
            $table->foreignId('user_id');
            $table->string('external_type');
            $table->string('external_id')->nullable();

            $table->dateTime('external_updated_at')->nullable();

            $table->unsignedSmallInteger('sync_status')->default(0);
            $table->string('sync_message')->nullable();

            $table->dateTime('imported_at')->nullable();
            $table->dateTime('exported_at')->nullable();

            $table->timestamps();

            $table->foreign('article_id')->references('id')->on('articles');
            $table->foreign('user_id')->references('id')->on('users');

            $table->index(['external_type', 'external_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('articles_external_ids');
    }
}
