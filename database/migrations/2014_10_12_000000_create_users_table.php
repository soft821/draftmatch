<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Common\Consts\User\UserStatusConsts;
use App\Common\Consts\User\UserRoleConsts;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('username')->unique();
            $table->string('role')->default(UserRoleConsts::$USER);
            $table->double('balance')->default(0);
            $table->string('status')->default(UserStatusConsts::$ACTIVE);
            $table->double('wins')->default(0);
            $table->double('loses')->default(0);
            $table->double('history_count')->default(0);
            $table->double('history_entry')->default(0);
            $table->double('history_winning')->default(0);
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}
