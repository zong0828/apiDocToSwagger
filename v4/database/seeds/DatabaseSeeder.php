<?php

use Illuminate\Database\Seeder;

/**
 * set the begining data in the db
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(ProjectSeeder::class);
    }
}
