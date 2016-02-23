<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Log;
use App\Energy;
use Firebase\FirebaseLib;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use App\Sensor;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class AggregateData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:aggregate {range=today : Available options: today, day, week}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggregate data we get from sensors';

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
        $deamonUp = true; //

        //
        /**
         *  we need:
         *  - last 30 min
         *  - last 1 day
         *  - last week
         *  - append log for the day
         */
        $range = $this->argument('range');

        \Log::error($range);

        $path     = 'https://vivid-heat-6441.firebaseio.com';
        $firebase = new FirebaseLib($path, 'TOKEN');

        Sensor::all()->each(function ($s, $key) use (&$deamonUp, $firebase, $range) {

            // get lifetime max
            $total = $s->energy()->where('instance', 1)->orderBy('created_at', 'DESC')->first();

            // push to FB
            if (!is_null($total)) {
                $firebase->set('/' . $s->slug . '/total', $total->value);
            }

            switch ($range) {
                case 'day':
                    $aggregate = $this->aggregateDay($s->node);
                    break;
                case 'week':
                    $aggregate = $this->aggregateWeek($s->node);
                    break;
                default:
                    $aggregate = $this->aggregateToday($s->node);
                    // update fire base log
                    if (!empty($aggregate['today'][0]->consumption)) {
                        $firebase->set('/' . $s->slug . '/log/' . \Carbon\Carbon::now()->toDateString(), [
                            'label' => \Carbon\Carbon::now()->toIso8601String(),
                            'value' => round($aggregate['today'][0]->consumption, 2)
                        ]);
                    }
                    break;
            }

            // get last reading
            $meterStats = json_decode($firebase->get('/' . $s->slug . '/stats'), true);

            // set up new
            if (is_null($meterStats)) {
                $meterStats = [
                    'last30min' => 0,
                    'hourAgo'   => 0,
                    'today'     => 0,
                    'yesterday' => 0,
                    'thisWeek'  => 0,
                    'lastWeek'  => 0,
                    'thisMonth' => 0,
                    'lastMonth' => 0,
                ];
            }

            // going to try not to set empty values
            foreach ($meterStats as $k => $v) {
                if (array_key_exists($k, $aggregate)) {
                    $meterStats[$k] = round($aggregate[$k][0]->consumption, 2);
                }
            }

            // set
            $firebase->set('/' . $s->slug . '/stats', $meterStats);

            // check last 30 for data
            if ($deamonUp) { // if it is down, it is down
                if ($meterStats['hourAgo'] == 0) { // look at last 30 min
                    $deamonUp = false;
                }
            }
        });


        $ping = DB::table('energy')
            ->where('instance', 1)
            ->where('node', 3)
            ->where('created_at', '>=', Carbon::now()->subHours(2)->toDateTimeString())
            ->count();


        // check if we need to restart
        if ($ping < 2) {
            Log::critical('Need to restarting pm2 service');
//            $process = new Process('pm2 restart service');asdasd
//            $process->run();

            // email me for now
//            Mail::raw("Restarting pm2 service:\n\n" . $process->getOutput(), function ($m) {
            Mail::raw("You need to restarting pm2 service.\n\n We had " . $ping . " entries for node 3 instance 1 in last 2 hours",
                function ($m) {
                    $m->from('home@315design.com', 'MyHome');

                    $m->to('kozlov.m.a@gmail.com', 'Mikhail Kozlov')->subject('Energy Data is missing');
            });
        }
    }

    /**
     * get aggregated data for the node
     *
     * @param $node
     *
     * @return array
     */
    public function aggregateToday($node)
    {
        return [
            //-- last 30 min
            //SELECT (MAX(value) - MIN(value)) as consumption, DATE_SUB(NOW(), INTERVAL 30 MINUTE) as f, NOW() as t FROM energy WHERE instance = 1 AND node = 2 AND created_at BETWEEN DATE_SUB(NOW(),INTERVAL 30 MINUTE) AND NOW();
            'last30min' => DB::select('SELECT (MAX(value) - MIN(value)) as consumption, DATE_SUB(NOW(), INTERVAL 30 MINUTE) as f, NOW() as t FROM energy WHERE instance = 1 AND node = :node AND created_at BETWEEN DATE_SUB(NOW(),INTERVAL 30 MINUTE) AND NOW()',
                ['node' => $node]),

            //-- hour ago
            //SELECT (MAX(value) - MIN(value)) as consumption, DATE_SUB(NOW(), INTERVAL 2 HOUR) as f, DATE_SUB(NOW(), INTERVAL 1 HOUR) as t FROM energy WHERE instance = 1 AND node = 2 AND created_at BETWEEN DATE_SUB(NOW(), INTERVAL 2 HOUR) AND DATE_SUB(NOW(), INTERVAL 1 HOUR);
            'hourAgo'   => DB::select('SELECT (MAX(value) - MIN(value)) as consumption, DATE_SUB(NOW(), INTERVAL 2 HOUR) as f, DATE_SUB(NOW(), INTERVAL 1 HOUR) as t FROM energy WHERE instance = 1 AND node = :node AND created_at BETWEEN DATE_SUB(NOW(), INTERVAL 2 HOUR) AND DATE_SUB(NOW(), INTERVAL 1 HOUR)',
                ['node' => $node]),

            //-- today
            //SELECT (MAX(value) - MIN(value)) as consumption, CURDATE() as f, NOW() as t FROM energy WHERE instance = 1 AND node = 2 AND DATE(created_at) = CURDATE();
            'today'     => DB::select('SELECT (MAX(value) - MIN(value)) as consumption, CURDATE() as f, NOW() as t FROM energy WHERE instance = 1 AND node = :node AND DATE(created_at) = CURDATE()',
                ['node' => $node]),

            //-- this week
            //SELECT (MAX(value) - MIN(value)) as consumption, MIN(created_at) as f, MAX(created_at) as t FROM energy WHERE instance = 1 AND node = 2 AND  YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1);
            'thisWeek'  => DB::select('SELECT (MAX(value) - MIN(value)) as consumption, MIN(created_at) as f, MAX(created_at) as t FROM energy WHERE instance = 1 AND node = :node AND  YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)',
                ['node' => $node]),

            //-- this month
            //SELECT (MAX(value) - MIN(value)) as consumption, MIN(created_at) as f, MAX(created_at) as t FROM energy WHERE instance = 1 AND node = 2 AND YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE());
            'thisMonth' => DB::select('SELECT (MAX(value) - MIN(value)) as consumption, MIN(created_at) as f, MAX(created_at) as t FROM energy WHERE instance = 1 AND node = :node AND YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())',
                ['node' => $node]),

        ];
    }

    /**
     * get aggregated data for the node
     *
     * @param $node
     *
     * @return array
     */
    public function aggregateDay($node)
    {
        return [
            //-- yesterday
            //SELECT (MAX(value) - MIN(value)) as consumption, MIN(created_at) as f, MAX(created_at) as t FROM energy WHERE instance = 1 AND  node = 2 AND DATE(created_at) BETWEEN DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND DATE_SUB(CURDATE(), INTERVAL 1 DAY);
            'yesterday' => DB::select('SELECT (MAX(value) - MIN(value)) as consumption, MIN(created_at) as f, MAX(created_at) as t FROM energy WHERE instance = 1 AND node = :node AND DATE(created_at) BETWEEN DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND DATE_SUB(CURDATE(), INTERVAL 1 DAY)',
                ['node' => $node]),
        ];
    }

    /**
     * get aggregated data for the node
     *
     * @param $node
     *
     * @return array
     */
    public function aggregateWeek($node)
    {
        // for last month aggregation, we need to know the year
        $now  = Carbon::now();
        $year = $now->year;
        if ($now->month = 1) {
            $year = $year - 1;
        }

        return [

            //-- last week
            //SELECT (MAX(value) - MIN(value)) as consumption, MIN(created_at) as f, MAX(created_at) as t FROM energy WHERE instance = 1 AND node = 2 AND YEARWEEK(created_at, 1) BETWEEN YEARWEEK(DATE_SUB(CURDATE(),INTERVAL 2 WEEK)) AND YEARWEEK(DATE_SUB(CURDATE(),INTERVAL 1 WEEK));
            'lastWeek'  => DB::select('SELECT (MAX(value) - MIN(value)) as consumption, MIN(created_at) as f, MAX(created_at) as t FROM energy WHERE instance = 1 AND node = :node AND YEARWEEK(created_at, 1) BETWEEN YEARWEEK(DATE_SUB(CURDATE(),INTERVAL 2 WEEK)) AND YEARWEEK(DATE_SUB(CURDATE(),INTERVAL 1 WEEK))',
                ['node' => $node]),

            //-- last month
            //SELECT (MAX(value) - MIN(value)) as consumption, MIN(created_at) as f, MAX(created_at) as t FROM energy WHERE node = 2 AND YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(DATE_SUB(CURDATE(),INTERVAL 1 MONTH));
            'lastMonth' => DB::select('SELECT (MAX(value) - MIN(value)) as consumption, MIN(created_at) as f, MAX(created_at) as t FROM energy WHERE instance = 1 AND node = :node AND YEAR(created_at) = :year AND MONTH(created_at) = MONTH(DATE_SUB(CURDATE(),INTERVAL 1 MONTH))',
                [
                    'node' => $node,
                    'year' => $year
                ]),
        ];
    }

}
