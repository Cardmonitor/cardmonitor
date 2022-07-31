<?php


use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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
            'email' => 'user@example.com',
            'name' => 'Daniel',
            'password' => Hash::make('password'),
        ]);
    }
}
