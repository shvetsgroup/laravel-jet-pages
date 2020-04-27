<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePagesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('pages')) {
            Schema::drop('pages');
        }
        Schema::create('pages', function (Blueprint $table) {
            $table->increments('id');
            $table->string('type')->nullable();
            $table->string('locale', 5);
            $table->string('slug')->index();
            $table->string('localeSlug')->index();
            $table->string('uri')->nullable();
            $table->string('url')->nullable();
            $table->string('href')->nullable();
            $table->string('title', 500)->nullable()->index();
            $table->longText('content')->nullable();
            $table->boolean('private')->nullable();
            $table->boolean('cache')->nullable();
            $table->string('scanner')->nullable();
            $table->string('path')->nullable();
            $table->string('hash')->nullable();
            $table->longText('data')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['locale', 'slug'], 'locale-slug');
            $table->index(['locale', 'title'], 'locale-title');
            $table->index(['locale', 'cache'], 'locale-cache');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('pages');
    }

}
