<?php

namespace Console\Metrics;

use App\Services\Client\ClientMetricJmakerService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Models\Db\Clients\ClientMetricJmaker;
use Models\Db\Clients\ClientMetricJmakerHistory;

class updateClientMetricJmakerHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'metrics:updateClientJmakerHistory';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Save for each Client jmaker metrics';

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

        ClientMetricJmakerService::updateJmakerMetricsForAllClients();

        $clientMetricJmakerColl =  ClientMetricJmaker::all();

        $date =  Carbon::yesterday();

        foreach ($clientMetricJmakerColl as $clientMetricJmaker) {

            /** @var ClientMetricJmakerHistory $clientMetricJmakerHistory */
            $clientMetricJmakerHistory = new ClientMetricJmakerHistory();

            $clientMetricJmakerHistory->uuid = generateNewUUID();

            $clientMetricJmakerHistory->client_uuid = $clientMetricJmaker->client_uuid;
            $clientMetricJmakerHistory->jmaker_state_active = $clientMetricJmaker->jmaker_state_active;
            $clientMetricJmakerHistory->jmaker_state_invited = $clientMetricJmaker->jmaker_state_invited;
            $clientMetricJmakerHistory->jmaker_state_onboarding = $clientMetricJmaker->jmaker_state_onboarding;
            $clientMetricJmakerHistory->jmaker_state_archived = $clientMetricJmaker->jmaker_state_archived;
            $clientMetricJmakerHistory->jmaker_state_deleted = $clientMetricJmaker->jmaker_state_deleted;

            $clientMetricJmakerHistory->workshop_started = $clientMetricJmaker->workshop_started;
            $clientMetricJmakerHistory->workshop_finished = $clientMetricJmaker->workshop_finished;

            $clientMetricJmakerHistory->jmaker_ws_finished_1 = $clientMetricJmaker->jmaker_ws_finished_1;
            $clientMetricJmakerHistory->jmaker_ws_finished_2 = $clientMetricJmaker->jmaker_ws_finished_2;
            $clientMetricJmakerHistory->jmaker_ws_finished_3 = $clientMetricJmaker->jmaker_ws_finished_3;
            $clientMetricJmakerHistory->jmaker_ws_finished_4 = $clientMetricJmaker->jmaker_ws_finished_4;
            $clientMetricJmakerHistory->jmaker_ws_finished_5 = $clientMetricJmaker->jmaker_ws_finished_5;
            $clientMetricJmakerHistory->jmaker_ws_finished_6 = $clientMetricJmaker->jmaker_ws_finished_6;
            $clientMetricJmakerHistory->jmaker_ws_finished_7 = $clientMetricJmaker->jmaker_ws_finished_7;
            $clientMetricJmakerHistory->jmaker_ws_finished_8 = $clientMetricJmaker->jmaker_ws_finished_8;
            $clientMetricJmakerHistory->jmaker_ws_finished_9 = $clientMetricJmaker->jmaker_ws_finished_9;

            $clientMetricJmakerHistory->shared_ct = $clientMetricJmaker->shared_ct;
            $clientMetricJmakerHistory->distinct_shared_ct = $clientMetricJmaker->distinct_shared_ct;
            $clientMetricJmakerHistory->step_finished = $clientMetricJmaker->step_finished;
            $clientMetricJmakerHistory->trim_avg_delay_between_invit_active = $clientMetricJmaker->trim_avg_delay_between_invit_active;
            $clientMetricJmakerHistory->avg_delay_between_invit_active = $clientMetricJmaker->avg_delay_between_invit_active;

            $clientMetricJmakerHistory->totalPrescriber = $clientMetricJmaker->totalPrescriber;
            $clientMetricJmakerHistory->invitationCountLast30Days = $clientMetricJmaker->invitationCountLast30Days;
            $clientMetricJmakerHistory->activePrescriberLast30Days = $clientMetricJmaker->activePrescriberLast30Days;

            $clientMetricJmakerHistory->created_at = $date;
            $clientMetricJmakerHistory->save();
        }
    }
}
