<?php

use Illuminate\Database\Migrations\Migration;
use Models\Db\Jmaker\Jmaker;

class AddDataJmakerClient extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        $output = new \Symfony\Component\Console\Output\ConsoleOutput();

        $output->writeln('------ BEGIN : UPDATE JMAKER FIELDS');

        $jmakers = Jmaker::withTrashed()->get();

        $countB2C = 0;
        $countB2B = 0;
        $countDEMO = 0;

        foreach ($jmakers as $jmaker) {

            /** @var \Models\Db\Jmaker\JmakerInvitation $invitation */
            $invitation = $jmaker->invitation()->withTrashed()->first();

            if($invitation) {

                /** @var \Models\Db\Clients\Client $client */
                $client = $invitation->client()->first();

                if($invitation->invited_by_prescriber_uuid && $invitation->campaign_uuid && $client) {

                    /** @var \Models\Db\Clients\ClientContract $contract */
                    $contract = $client->contracts()->first();

                    if($contract) {
                        $jmaker->client_uuid = $invitation->client_uuid;
                        $jmaker->prescriber_uuid = $invitation->invited_by_prescriber_uuid;
                        $jmaker->campaign_uuid = $invitation->campaign_uuid;
                        $jmaker->contract_uuid = $contract->uuid;
                        $jmaker->save();
                    } else {
                        $output->writeln('------ CLIENT WITHOUT CONTRACT ' . $client->name);
                    }

                    $countB2B++;
                } else {
                    $countDEMO++;
                }

            } else {
                $countB2C++;
            }

        }

        $output->writeln('------ ' . $countB2C . ' Jmaker B2C');
        $output->writeln('------ ' . $countB2B . ' Jmaker B2B linked with Client and Prescriber');
        $output->writeln('------ ' . $countDEMO . ' DEMO Jmaker');
        $output->writeln('------ DONE : UPDATED JMAKER FIELDS');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $jmakers = \Models\Db\Jmaker\Jmaker::withTrashed()->get();

        foreach ($jmakers as $jmaker) {
            $jmaker->state = null;
        };
    }
}
