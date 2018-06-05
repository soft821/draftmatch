<?php

use Illuminate\Database\Seeder;
use App\User;
use App\Common\Consts\User\UserRoleConsts;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'name' => 'admin',
            'email' => 'admin@draftmatch.com',
            'password' => bcrypt('admin123'),
            'username' => 'admin',
            'balance' => 0,
            'role' => UserRoleConsts::$ADMIN
        ]);

        User::create([
            'name' => 'yuri_test',
            'email' => 'yuri_test@draftmatch.com',
            'password' => bcrypt('user123'),
            'username' => 'yuri_test',
            'balance' => 10000,
            'role' => UserRoleConsts::$USER
        ]);

        User::create([
            'name' => 'nedim_test',
            'email' => 'nedim_test@draftmatch.com',
            'password' => bcrypt('user123'),
            'username' => 'nedim_test',
            'balance' => 10000,
            'role' => UserRoleConsts::$USER
        ]);

        User::create([
            'name' => 'haris_test',
            'email' => 'haris.omerovic87@gmail.com',
            'password' => bcrypt('user123'),
            'username' => 'haris_test',
            'balance' => 0,
            'role' => UserRoleConsts::$USER
        ]);


        //factory(App\User::class, 50)->create()->each(function($u) {
        //    $u->posts()->save(factory(App\Post::class)->make());
        //});
    }
}
