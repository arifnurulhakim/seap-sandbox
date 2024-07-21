<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Divisi;

class DivisiTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Divisi::truncate();

        Divisi::create([
            'id' => 1,
            'name' => 'admin'
        ]);
        Divisi::create([
            'id' => 2,
            'name' => 'AE'
        ]);
        Divisi::create([
            'id' => 3,
            'name' => 'FE'
        ]);
        Divisi::create([
            'id' => 4,
            'name' => 'BE'
        ]);
        Divisi::create([
            'id' => 5,
            'name' => 'GAD'
        ]);
      
    }
}
