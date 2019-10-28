<?php

namespace Console\Metrics;

use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Models\Db\Clients\ClientWeekMetric;

class UpdateClientWeekMetric extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'metrics:updateClientWeekMetric';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Week metrics for each client';

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

        $end = (new Carbon())->startOfDay();
        $begin = $end->copy()->previous(Carbon::MONDAY);

        $clientWeekMetrics = ClientWeekMetric::where('created_at', '=', $end)->get();

        //Get distinct information
        $resultsQuery = DB::select(DB::raw("
                select
                    client_uuid,
                    count(distinct(jb.jmaker_uuid)) as distinct_jmaker
                from
                    jmaker_behavior as jb,
                    jmaker as j
                where
                    j.uuid = jb.jmaker_uuid and j.client_uuid is not null and
                    jb.created_at between '" . $begin . "' and '" . $end . "'
                group by
                    j.client_uuid"));

        foreach ($resultsQuery as $resultQ) {

            $clientUUID = $resultQ->client_uuid;
            /** @var ClientWeekMetric $clientWeekMetric */
            $clientWeekMetric = $clientWeekMetrics->where('client_uuid', '=', $clientUUID)->first();

            $clientWeekMetric = UpdateClientWeekMetric::createClientWeekMetricIfNecessary($clientWeekMetric, $clientWeekMetrics, $clientUUID, $end);

            $clientWeekMetric->distinct_user = $resultQ->distinct_jmaker;
        }


        $resultsQuery = DB::select(DB::raw("
                    SELECT 
                        client_uuid,
                        jmaker_state_active + jmaker_state_invited as totalJmaker,
                        workshop_finished,
                        jmaker_ws_finished_1 + jmaker_ws_finished_2 + jmaker_ws_finished_3 + jmaker_ws_finished_4 + jmaker_ws_finished_5 + jmaker_ws_finished_6 + jmaker_ws_finished_7 + jmaker_ws_finished_8 + jmaker_ws_finished_9 as jmakerEngaged
                    FROM 
                        client_metric_jmaker_history
                    where created_at = '".$end->copy()->addDays(-1)."'
                    "));

        foreach($resultsQuery as $resultQ) {

            $clientUUID = $resultQ->client_uuid;
            /** @var ClientWeekMetric $clientWeekMetric */
            $clientWeekMetric = $clientWeekMetrics->where('client_uuid', '=', $clientUUID)->first();

            $clientWeekMetric = UpdateClientWeekMetric::createClientWeekMetricIfNecessary($clientWeekMetric, $clientWeekMetrics, $clientUUID, $end);

            $clientWeekMetric->workshop_count = $resultQ->workshop_finished;
            $clientWeekMetric->engaged_count = $resultQ->jmakerEngaged;
            $clientWeekMetric->invitation_count = $resultQ->totalJmaker;

        }


        $resultsQuery = DB::select(DB::raw("
                    SELECT 
                        j.client_uuid,
                        sum(if(JSON_UNQUOTE(JSON_EXTRACT(mr.evaluations, '$.questions[0].value')) is not null,1,0)) as countEval1,
                        sum(if(JSON_UNQUOTE(JSON_EXTRACT(mr.evaluations, '$.questions[1].value')) is not null,1,0)) as countEval2,
                        sum(JSON_UNQUOTE(JSON_EXTRACT(mr.evaluations, '$.questions[0].value'))) as sumEval1,
                        sum(JSON_UNQUOTE(JSON_EXTRACT(mr.evaluations, '$.questions[1].value'))) as sumEval2
                    FROM 
                        mission_run as mr,
                        jmaker as j
                    where 
                        mr.jmaker_uuid = j.uuid and
                        (j.deleted_at is null or j.deleted_at > '".$end."') and  
                        j.client_uuid is not null and
                        mr.status_rid in ('RUN_STATUS_FINISHED','RUN_STATUS_FINISHED_CHECKUP_WITH_JOBMAKER') and
                        mr.completed_at < '".$end."'
                    group by j.client_uuid
                    "));

        foreach($resultsQuery as $resultQ) {

            $clientUUID = $resultQ->client_uuid;
            /** @var ClientWeekMetric $clientWeekMetric */
            $clientWeekMetric = $clientWeekMetrics->where('client_uuid', '=', $clientUUID)->first();

            $clientWeekMetric = UpdateClientWeekMetric::createClientWeekMetricIfNecessary($clientWeekMetric, $clientWeekMetrics, $clientUUID, $end);

            $result = -1;
            $count =  $resultQ->countEval1+$resultQ->countEval2;

            if($count > 0) {
                $result = ($resultQ->sumEval1+$resultQ->sumEval2) /  $count;
            }

            $clientWeekMetric->average_evaluation = $result;
        }

        foreach ($clientWeekMetrics as $clientWeekMetric) {
            $clientWeekMetric->save();
        }
    }

    /**
     * Create client weekMetric if necessary
     * @param $clientWeekMetric
     * @param $clientWeekMetrics
     * @throws Exception
     */
    private function createClientWeekMetricIfNecessary($clientWeekMetric, &$clientWeekMetrics, $clientUUID, $end)
    {

        if (!$clientWeekMetric) {
            $clientWeekMetric = new ClientWeekMetric();
            $clientWeekMetric->uuid = generateNewUUID();
            $clientWeekMetric->client_uuid = $clientUUID;
            $clientWeekMetric->created_at = $end;
            $clientWeekMetric->max_jmaker = 0;
            $clientWeekMetric->goal_invitation_rate = -1;
            $clientWeekMetric->invitation_count = 0;
            $clientWeekMetric->goal_engaged_rate = -1;
            $clientWeekMetric->engaged_count = 0;
            $clientWeekMetric->goal_engaged_workshop_rate = -1;
            $clientWeekMetric->workshop_count = 0;
            $clientWeekMetric->goal_distinct_user = -1;
            $clientWeekMetric->distinct_user = 0;
            $clientWeekMetric->goal_average_evaluation = -1;
            $clientWeekMetric->average_evaluation = 0;
            $clientWeekMetrics->push($clientWeekMetric);
        }

        return $clientWeekMetric;
    }
}
