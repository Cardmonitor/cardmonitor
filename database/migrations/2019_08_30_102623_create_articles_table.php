<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateArticlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('card_id')->nullable();
            $table->unsignedTinyInteger('language_id');

            $table->unsignedBigInteger('storage_id')->nullable();
            $table->unsignedBigInteger('rule_id')->nullable();

            $table->unsignedBigInteger('cardmarket_article_id')->nullable()->index();
            $table->unsignedSmallInteger('index')->default(0);
            $table->dateTime('cardmarket_last_edited')->nullable();
            $table->unsignedTinyInteger('state')->nullable();
            $table->string('state_comments')->nullable();
            $table->string('condition');
            $table->unsignedTinyInteger('condition_sort');
            $table->string('cardmarket_comments')->nullable();
            $table->boolean('is_in_shoppingcard')->default(false);
            $table->boolean('is_foil')->default(false);
            $table->boolean('is_signed')->default(false);
            $table->boolean('is_altered')->default(false);
            $table->boolean('is_playset')->default(false);
            $table->decimal('unit_cost', 15, 6)->default(0);
            $table->decimal('unit_price', 15, 6)->default(0);
            $table->decimal('provision', 15, 6)->default(0);
            $table->dateTime('bought_at')->nullable();
            $table->dateTime('exported_at')->nullable();
            $table->dateTime('sold_at')->nullable();
            $table->text('hash')->nullable();
            $table->boolean('should_sync')->default(false);
            $table->boolean('has_sync_error')->default(false);
            $table->string('sync_error')->nullable();
            $table->dateTime('synced_at')->nullable();

            $table->decimal('price_rule', 15, 6)->nullable();
            $table->decimal('rule_difference', 15, 6)->nullable();
            $table->decimal('rule_difference_percent', 5, 2)->nullable();
            $table->dateTime('rule_applied_at')->nullable();


            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('card_id')->references('id')->on('cards')->onDelete('cascade');
            $table->foreign('language_id')->references('id')->on('languages');
            $table->foreign('storage_id')->references('id')->on('storages');
            $table->foreign('rule_id')->references('id')->on('rules');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('articles');
    }
}
