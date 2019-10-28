<?php

use Illuminate\Database\Migrations\Migration;
use Models\Db\Jmaker\Jmaker;

class CreateJmakerForEachInvitation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        $output = new \Symfony\Component\Console\Output\ConsoleOutput();

        $output->writeln('------ BEGIN : CREATE JMAKER FOR EACH INVITATION');

        $jmakerInvitations = \Models\Db\Jmaker\JmakerInvitation::withTrashed()->get();

        $count = 0;
        $countCreated = 0;
        $countCreatedDeleted = 0;
        $previousValue = 0;

        foreach ($jmakerInvitations as $jmakerInvitation) {
            /** @var \Models\Db\Jmaker\JmakerInvitation $jmakerInvitation */
            $jmaker = $jmakerInvitation->jmaker();

            if($jmaker) {
                $jmaker->registred_at = $jmakerInvitation->completed_at;
                $jmaker->created_at = $jmakerInvitation->created_at;
                $jmaker->save();
                $count++;
            } else {

                $jmaker = new Jmaker();
                $jmaker->uuid = generateNewUUID();
                $jmaker->firstname = isset($jmakerInvitation->data['firstname']) ? $jmakerInvitation->data['firstname'] : '';
                $jmaker->lastname = isset($jmakerInvitation->data['lastname']) ? $jmakerInvitation->data['lastname'] : '';
                $jmaker->username = $jmaker->firstname . ' ' . $jmaker->lastname;
                $jmaker->username_canonical = str_slug($jmaker->username, '-');
                $jmaker->email = $jmakerInvitation->email;

                $jmaker->language_id = $jmakerInvitation->language_id;

                $jmaker->enabled = false;
                $jmaker->password = bcrypt(generateNewUUID());
                $jmaker->locked = false;
                $jmaker->expired = false;
                $jmaker->work_experience = -1;
                $jmaker->credentials_expired = false;
                $jmaker->want_notification = $jmakerInvitation->allow_reminder;

                $jmaker->created_at = $jmakerInvitation->created_at;
                $jmaker->registred_at = null;
                $jmaker->last_page_at = null;

                $jmaker->state = Ref::JMAKER_STATE_INVITED;

                if($jmakerInvitation->is_started && !$jmakerInvitation->is_completed) {
                    $jmaker->state = Ref::JMAKER_STATE_ONBOARDING;
                }

                if($jmakerInvitation->deleted_at) {
                    $countCreatedDeleted++;
                    $jmaker->deleted_at = $jmakerInvitation->deleted_at;
                } else {
                    $countCreated++;
                }

                $jmaker->save();

                $jmakerInvitation->jmaker_uuid = $jmaker->uuid;
                $jmakerInvitation->save();

                if((int)($countCreated / 100) != $previousValue) {
                    $output->writeln('-create ' . $countCreated);
                    $previousValue = (int)($countCreated / 100);
                }

            }
        }

        $output->writeln('------ ' . $count . ' Jmaker already exist');
        $output->writeln('------ ' . $countCreated . ' Jmaker created');
        $output->writeln('------ ' . $countCreatedDeleted . ' Jmaker created for deleted invitation');
        $output->writeln('------ DONE : CREATE JMAKER FOR EACH INVITATION');

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
