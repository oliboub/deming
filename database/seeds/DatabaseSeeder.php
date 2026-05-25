<?php

use Database\Seeders\AttributeSeeder;
use Database\Seeders\DomainSeeder;
use Database\Seeders\MeasureSeeder;
use Database\Seeders\RiskScoringConfigSeeder;
use Illuminate\Database\Seeder;
use Database\Seeders\UserSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        if (DB::table('users')->count()==0) {

            $lang = ENV('LANG');
            if (($lang!=="fr")&&($lang!=="de"))
                $lang="en";

            DB::table('users')->insert([
                'login' => 'admin@admin.localhost',
                'name' => 'Administrator',
                'title' => 'Pirate Captain',
                'role' => 1,
                'language' => $lang,
                'email' => 'admin@admin.localhost',
                'password' => bcrypt('admin'),
            ]);

            $this->call([
                AttributeSeeder::class,
                // DomainSeeder::class,
                // MeasureSeeder::class,
                RiskScoringConfigSeeder::class,
            ]);


        }
    }
}
