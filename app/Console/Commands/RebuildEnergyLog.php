<?php

namespace App\Console\Commands;

use Firebase\FirebaseLib;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use App\Sensor;
use Illuminate\Support\Facades\DB;

class RebuildEnergyLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:relog';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebuld firebase log for each meter';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $path     = 'https://vivid-heat-6441.firebaseio.com';
        $firebase = new FirebaseLib($path, env('FIRE_BASE_KEY', ''));

        Sensor::all()->each(function ($s, $key) use ($firebase) {

            // get lifetime max
            $totals = $s->energy()
                ->select(DB::raw('(MAX(value) - MIN(value)) as consumption'), 'created_at')
                ->where('instance', 1)
                ->orderBy('created_at', 'DESC')
                ->groupBy(DB::raw('DATE(created_at)'))
                ->get();

            foreach ($totals as $total) {
                $firebase->set('/' . $s->slug . '/log/' . $total->created_at->toDateString(), [
                    'label' => $total->created_at->toIso8601String(),
                    'value' => round($total->consumption, 2)
                ]);
            }
        });
    }
}
