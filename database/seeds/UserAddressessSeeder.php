<?php

use App\Models\User;
use Illuminate\Database\Seeder;

class UserAddressessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::all()->each(function(User $user){
        factory(\App\Models\UserAddress::class,random_int(1, 3))->create(['user_id'=>$user->id]);
        });
    }
}
