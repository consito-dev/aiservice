<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {

    public function up()
    {
        Schema::table('prompts', function (Blueprint $table) {
            $table->text('system_template')->nullable()->after('template');
        });
    }

    public function down()
    {
        Schema::table('prompts', function (Blueprint $table) {
            $table->dropColumn('system_template');
        });
    }

};
