<?php

use Illuminate\Database\Seeder;

class SensorTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // kill data
        DB::table('sensors')->truncate();

        // fill back in
        DB::table('sensors')->insert([
            [
                'id'         => 2,
                'node'       => 2,
                'name'       => 'EVCS',
                'slug'       => 'evcs',
                'config'     => json_encode([]),
                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ],
            [
                'id'         => 3,
                'node'       => 3,
                'name'       => 'AC Fan',
                'slug'       => 'acfan',
                'config'     => json_encode([]),
                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ],
        ]);

        // now we can fill up sensor data
        $this->call(MetricsTablesSeeder::class);

    }
}
