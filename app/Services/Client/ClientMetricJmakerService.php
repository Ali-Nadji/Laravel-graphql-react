<?php namespace App\Services\Client;

use Exception;
use Illuminate\Support\Facades\DB;
use Models\Db\Clients\ClientMetricJmaker;
use Ref;

/**
 *
 * Class JmakerService
 */
class ClientMetricJmakerService
{

    /**
     * Update Jmaker metrics for each client
     * @throws Exception
     */
    public static function updateJmakerMetricsForAllClients()
    {
        ClientMetricJmakerService::updateAndComputeJmakerMetrics();
    }

    /**
     * Update Jmaker metrics for each client
     * @param $clientUUID String Client for which metric will be update
     * @throws Exception
     */
    public static function updateJmakerMetrics($clientUUID)
    {
        ClientMetricJmakerService::updateAndComputeJmakerMetrics($clientUUID);
    }


    /**
     * Update Jmaker metrics
     * @param null $clientUUID if null update for all client, otherwise only for specific client
     * @throws Exception
     */
    private static function updateAndComputeJmakerMetrics($clientUUID = null) {

        $clientFilter =  "";
        if ($clientUUID) {
            $clientFilter = " and j.client_uuid = '".$clientUUID."'";
        }

        $clientMetricJmakerColl =  ClientMetricJmaker::all();

        //*************************
        //sum all metrics
        //*************************

        ClientMetricJmakerService::sumAllJmakerCountState($clientUUID, $clientMetricJmakerColl);

        ClientMetricJmakerService::updateDelayBetweenInvitationAndActivation($clientFilter, $clientMetricJmakerColl);

        ClientMetricJmakerService::computeNewMetrics($clientUUID, $clientMetricJmakerColl);

        //*************************
        //Save all clients metrics.
        //*************************

        foreach ($clientMetricJmakerColl as $clientMetricJmakerTmp) {
            $clientMetricJmakerTmp->save();
        }
    }


    /**
     * Find or create a new JmakerMetrics.
     * @param $clientUUID
     * @param $clientMetricJmakerColl Ref to the collection
     * @return ClientMetricJmaker
     * @throws Exception
     */
    private static function findOrCreateJmakerMetrics($clientUUID, &$clientMetricJmakerColl) {

        $clientMetricJmaker = $clientMetricJmakerColl->where('client_uuid',$clientUUID)->first();

        if(!$clientMetricJmaker) {
            $clientMetricJmaker = new ClientMetricJmaker();
            $clientMetricJmaker->uuid = generateNewUUID();
            $clientMetricJmaker->client_uuid = $clientUUID;
            $clientMetricJmakerColl->push($clientMetricJmaker);
        }

        return $clientMetricJmaker;
    }

    /**
     * @param $clientUUID
     * @param $clientMetricJmakerColl
     */
    private static function computeNewMetrics($clientUUID, $clientMetricJmakerColl)
    {

        $clientFilter = "";
        if ($clientUUID) {
            $clientFilter = " and c.uuid = '" . $clientUUID . "'";
        }


        $results = DB::select(DB::raw("
        select 
            c.uuid,
            if(tot.totalPrescriber is null,0,tot.totalPrescriber) as totalPrescriber,
            if(activeP.activePrescriberLast30Days is null, 0, activeP.activePrescriberLast30Days) as activePrescriberLast30Days,
            if(invit.invitationCount is null, 0, invit.invitationCount) as invitationCountLast30Days
        from 
            client as c
            left join 
            (
                select 
                    count(0) as totalPrescriber,
                    client_uuid
                from 
                    prescriber as p
                where 
                    p.deleted_at is null
            group by client_uuid)  as tot on tot.client_uuid = c.uuid
            left join 
            (
                select 
                    count(0) as activePrescriberLast30Days,
                    client_uuid 
                from 
                    prescriber as p
                where 
                    p.loggedin_at > DATE_SUB(NOW(),INTERVAL 30 DAY) and 
                    p.deleted_at is null
                group by client_uuid) as activeP  on activeP.client_uuid = c.uuid
            left join (
                select
                    count(0) as invitationCount,
                    client_uuid
                from 
                    jmaker as j
                 where 
                    j.created_at > DATE_SUB(NOW(),INTERVAL 31 DAY) and 
                    j.deleted_at is null
                group by j.client_uuid ) as invit on invit.client_uuid = c.uuid
        where c.client_status = '" . Ref::CLIENT_STATUS_ACTIVE . "' " . $clientFilter));

        foreach ($results as $result) {

            //dd($result->client_uuid);
            /** @var ClientMetricJmaker $clientMetricJmaker */
            $clientMetricJmaker = ClientMetricJmakerService::findOrCreateJmakerMetrics($result->uuid, $clientMetricJmakerColl);
            $clientMetricJmaker->totalPrescriber = $result->totalPrescriber;
            $clientMetricJmaker->activePrescriberLast30Days = $result->activePrescriberLast30Days;
            $clientMetricJmaker->invitationCountLast30Days = $result->invitationCountLast30Days;
        }

        return $clientMetricJmakerColl;
    }

    /**
     * Update Archive,Active, Onboarding, Active count for all Client or for ClientFilter
     * @param $clientUUID
     * @param $clientMetricJmakerColl
     * @return mixed
     * @throws Exception
     */
    private static function sumAllJmakerCountState($clientUUID, $clientMetricJmakerColl) {

        //*************************
        //ACTIVE, ARCHIVE, ONBOARDING, INVITED METRICS
        //*************************

        $clientFilter =  "";
        if ($clientUUID) {
            $clientFilter = " and cc.client_uuid = '".$clientUUID."'";
        }


        $results = DB::select(DB::raw("select
                cc.client_uuid,
                sum(pmj.jmaker_state_active) as jmaker_state_active,
                sum(pmj.jmaker_state_onboarding) as jmaker_state_onboarding,
                sum(pmj.jmaker_state_invited) as jmaker_state_invited,
                sum(pmj.jmaker_state_archived) as jmaker_state_archived,
                sum(pmj.jmaker_state_deleted) as jmaker_state_deleted,
                sum(pmj.workshop_started) as workshop_started,
                sum(pmj.workshop_finished) as workshop_finished,
                sum(pmj.jmaker_ws_finished_1) as jmaker_ws_finished_1,
                sum(pmj.jmaker_ws_finished_2) as jmaker_ws_finished_2,
                sum(pmj.jmaker_ws_finished_3) as jmaker_ws_finished_3,
                sum(pmj.jmaker_ws_finished_4) as jmaker_ws_finished_4,
                sum(pmj.jmaker_ws_finished_5) as jmaker_ws_finished_5,
                sum(pmj.jmaker_ws_finished_6) as jmaker_ws_finished_6,
                sum(pmj.jmaker_ws_finished_7) as jmaker_ws_finished_7,
                sum(pmj.jmaker_ws_finished_8) as jmaker_ws_finished_8,
                sum(pmj.jmaker_ws_finished_9) as jmaker_ws_finished_9,   
                sum(pmj.step_finished) as step_finished,
                sum(pmj.shared_ct) as shared_ct,
                sum(pmj.distinct_shared_ct) as distinct_shared_ct
            from 
                client_campaign_metric_jmaker as pmj,
                client_campaign as cc
            where
                cc.deleted_at is null and 
                pmj.campaign_uuid = cc.uuid " .$clientFilter." 
            group by cc.client_uuid"));

        foreach($results as $result) {

            //dd($result->client_uuid);
            /** @var ClientMetricJmaker $clientMetricJmaker */
            $clientMetricJmaker = ClientMetricJmakerService::findOrCreateJmakerMetrics($result->client_uuid, $clientMetricJmakerColl);

            $clientMetricJmaker->jmaker_state_active = $result->jmaker_state_active;
            $clientMetricJmaker->jmaker_state_invited = $result->jmaker_state_invited;
            $clientMetricJmaker->jmaker_state_onboarding = $result->jmaker_state_onboarding;
            $clientMetricJmaker->jmaker_state_archived = $result->jmaker_state_archived;
            $clientMetricJmaker->jmaker_state_deleted = $result->jmaker_state_deleted;

            $clientMetricJmaker->workshop_started = $result->workshop_started;
            $clientMetricJmaker->workshop_finished = $result->workshop_finished;

            $clientMetricJmaker->jmaker_ws_finished_1 = $result->jmaker_ws_finished_1;
            $clientMetricJmaker->jmaker_ws_finished_2 = $result->jmaker_ws_finished_2;
            $clientMetricJmaker->jmaker_ws_finished_3 = $result->jmaker_ws_finished_3;
            $clientMetricJmaker->jmaker_ws_finished_4 = $result->jmaker_ws_finished_4;
            $clientMetricJmaker->jmaker_ws_finished_5 = $result->jmaker_ws_finished_5;
            $clientMetricJmaker->jmaker_ws_finished_6 = $result->jmaker_ws_finished_6;
            $clientMetricJmaker->jmaker_ws_finished_7 = $result->jmaker_ws_finished_7;
            $clientMetricJmaker->jmaker_ws_finished_8 = $result->jmaker_ws_finished_8;
            $clientMetricJmaker->jmaker_ws_finished_9 = $result->jmaker_ws_finished_9;

            $clientMetricJmaker->shared_ct = $result->shared_ct;
            $clientMetricJmaker->distinct_shared_ct = $result->distinct_shared_ct;
            $clientMetricJmaker->step_finished = $result->step_finished;
        }

        return $clientMetricJmakerColl;
    }

    /**
     * Update average delay between invitation and activation
     * @param $clientFilter
     * @param $clientMetricJmakerColl
     * @throws Exception
     */
    private static function updateDelayBetweenInvitationAndActivation($clientFilter, $clientMetricJmakerColl) {
        $results = DB::select(DB::raw("select 
                j.client_uuid,
                a.nbDays
            from 
                (select 
                jmaker_uuid,
                datediff(completed_at,created_at) as nbDays
                from jmaker_invitation
                where is_completed is true) as a,
                jmaker as j
            where
                j.uuid = a.jmaker_uuid and j.client_uuid is not null and 
                nbDays >= 0 "
            . $clientFilter . " 
            order by nbDays
        "));

        $avgCompute = [];

        foreach($results as $result) {
            /** @var ClientMetricJmaker $clientMetricJmaker */

            if(!isset($avgCompute[$result->client_uuid])) {
                $avgCompute[$result->client_uuid] = [];
            }

            array_push($avgCompute[$result->client_uuid],$result->nbDays);
        }

        foreach($avgCompute as $key => $tab) {

            $clientMetricJmaker = ClientMetricJmakerService::findOrCreateJmakerMetrics($key, $clientMetricJmakerColl);

            $clientMetricJmaker->trim_avg_delay_between_invit_active = -1;
            $clientMetricJmaker->avg_delay_between_invit_active = array_sum($tab)/count($tab);

            if(count($tab) >= 10) {
                $howManyTrim = (int)(count($tab)*0.1);
                $newTab = array_slice($tab,$howManyTrim,count($tab)-$howManyTrim);
                $clientMetricJmaker->trim_avg_delay_between_invit_active = array_sum($newTab)/count($newTab);
            }
        }
    }
}