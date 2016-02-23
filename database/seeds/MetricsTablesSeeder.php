<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class MetricsTablesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        DB::table('energy')->truncate();
        DB::table('power')->truncate();

        App\Sensor::all()->each(function ($s, $key) {

            $now = \Carbon\Carbon::now()->subDay()->subHours(3);

            for ($i = 0; $i < 500; $i++) {

                $s->energy()->save(factory(App\Energy::class)->make([
                    'node'       => $s->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'value'      => $i + ((1 / $s->id) * 2),
                ]));

                $s->power()->save(factory(App\Power::class)->make([
                    'node'       => $s->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));

                //move time
                $now->addMinute(15);
            }
        });

        Model::reguard();

    }
}
