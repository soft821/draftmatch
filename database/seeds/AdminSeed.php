<?php

use Illuminate\Database\Seeder;
use App\User;
use App\Common\Consts\User\UserRoleConsts;

class AdminSeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'name' => 'lejla',
            'email' => 'lejla@draftmatch.com',
            'password' => bcrypt('lejlich'),
            'username' => 'lejla',
            'balance' => 99,
            'role' => UserRoleConsts::$ADMIN
        ]);

    }
}
