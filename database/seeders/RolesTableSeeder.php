<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Role::truncate();

        Role::create([
            'id' => 1,
            'name' => 'admin'
        ]);
        Role::create([
            'id' => 2,
            'name' => 'management'
        ]);
        Role::create([
            'id' => 3,
            'name' => 'leader'
        ]);
        Role::create([
            'id' => 4,
            'name' => 'staff'
        ]);
        Role::create([
            'id' => 5,
            'name' => 'AE'
        ]);

    }
}
