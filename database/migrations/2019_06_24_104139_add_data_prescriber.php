<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Models\Db\Prescriber\Prescriber;
use Models\Db\Prescriber\PrescriberClient;

class AddDataPrescriber extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $output = new \Symfony\Component\Console\Output\ConsoleOutput();

        $output->writeln('------ BEGIN : UPDATE PRESCRIBER FIELDS');

        $prescribers = Prescriber::withTrashed()->get();

        foreach ($prescribers as $prescriber) {
            /** @var \Models\Db\Jmaker\JmakerInvitation $invitation */
            $prescriberClient = PrescriberClient::where('prescriber_uuid','=',$prescriber->uuid)->first();
            if($prescriberClient) {

                /** @var \Models\Db\Clients\Client $client */
                $client = $prescriberClient->client()->first();

                if($client) {

                    /** @var \Models\Db\Clients\ClientContract $contract */
                    $contract = $client->contracts()->first();

                    if($contract) {
                        $prescriber->client_uuid = $prescriberClient->client_uuid;
                        $prescriber->contract_uuid = $contract->uuid;
                        $prescriber->save();
                    } else {
                        $output->writeln('------ CLIENT WITHOUT CONTRACT ' . $client->name);
                    }

                }

            }

        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $prescribers = Prescriber::withTrashed()->get();
        foreach($prescribers as $prescriber)
        {
            $prescriber->client_uuid = null;
            $prescriber->contract_uuid = null;
            $prescriber->save();
        }
    }
}
