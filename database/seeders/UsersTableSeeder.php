<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::truncate();
        User::create([
            'name' => 'admin',
            'email' => 'admin@plexus.id',
            'password' =>bcrypt('password'),
            'role_id' => '1',
            'divisi_id' => '1',
        ]);
        User::create([
            'name' => 'management',
            'email' => 'management@plexus.id',
            'password' =>bcrypt('password'),
            'role_id' => '2',
            'divisi_id' => '1',
        ]);
        User::create([
            'name' => 'leader',
            'email' => 'leader@plexus.id',
            'password' =>bcrypt('password'),
            'role_id' => '3',
            'divisi_id' => '1',
        ]);
        User::create([
            'name' => 'AE',
            'email' => 'AE@plexus.id',
            'password' =>bcrypt('password'),
            'role_id' => '5',
            'divisi_id' => '2',

        ]);

        User::create([
            'name' => 'leader',
            'email' => 'leaderAE@plexus.id',
            'password' =>bcrypt('password'),
            'role_id' => '4',
            'divisi_id' => '2',
        ]);
        User::create([
            'name' => 'leader',
            'email' => 'leaderFE@plexus.id',
            'password' =>bcrypt('password'),
            'role_id' => '5',
            'divisi_id' => '3',
        ]);
        User::create([
            'name' => 'leader',
            'email' => 'leaderBE@plexus.id',
            'password' =>bcrypt('password'),
            'role_id' => '6',
            'divisi_id' => '4',
        ]);
        User::create([
            'name' => 'leader',
            'email' => 'leaderGAD@plexus.id',
            'password' =>bcrypt('password'),
            'role_id' => '7',
            'divisi_id' => '5',
        ]);

    }
}
