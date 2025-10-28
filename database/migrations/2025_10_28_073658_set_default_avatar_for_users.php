<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Устанавливаем дефолтное значение для существующих записей
        DB::table('users')->whereNull('avatar')->update(['avatar' => '/avatars/default.png']);
        
        // Меняем структуру колонки чтобы добавить DEFAULT
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->default('/avatars/default.png')->change();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->default(null)->change();
        });
    }
};