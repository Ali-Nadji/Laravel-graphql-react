<?php

use Illuminate\Database\Migrations\Migration;
use Models\Db\Languages\LanguageContent;
use Models\Db\Languages\LanguageGroup;

class AddContentRelancePartner extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->down();

        $groupPartner = LanguageGroup::find(\Ref::LANGUAGE_GROUP_PARTNER_UUID);

        $content = new LanguageContent();
        $content->uuid = generateNewUUID();
        $content->lang_rid = Ref::LANG_FR;
        $content->code = 'XqC9sR';
        $content->language_group_uuid = $groupPartner->uuid;
        $content->instruction = [
            'method' => 'text',
            'params' => ["Jobmaker Relance"],
        ];
        $content->is_published = true;
        $content->published_at = \Carbon\Carbon::now();
        $content->save();

        $content = new LanguageContent();
        $content->uuid = generateNewUUID();
        $content->lang_rid = Ref::LANG_EN;
        $content->code = 'XqC9sR';
        $content->language_group_uuid = $groupPartner->uuid;
        $content->instruction = [
            'method' => 'text',
            'params' => ["Jobmaker Reminder"],
        ];
        $content->is_published = true;
        $content->published_at = \Carbon\Carbon::now();
        $content->save();
        /**************************************************************/
        $content = new LanguageContent();
        $content->uuid = generateNewUUID();
        $content->lang_rid = Ref::LANG_FR;
        $content->code = 'Cyyhch';
        $content->language_group_uuid = $groupPartner->uuid;
        $content->instruction = [
            'method' => 'text',
            'params' => ["Reprenez votre programme Jobmaker"],
        ];
        $content->is_published = true;
        $content->published_at = \Carbon\Carbon::now();
        $content->save();

        $content = new LanguageContent();
        $content->uuid = generateNewUUID();
        $content->lang_rid = Ref::LANG_EN;
        $content->code = 'Cyyhch';
        $content->language_group_uuid = $groupPartner->uuid;
        $content->instruction = [
            'method' => 'text',
            'params' => ["Get back to your Jobmaker program"],
        ];
        $content->is_published = true;
        $content->published_at = \Carbon\Carbon::now();
        $content->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $groupPartner = LanguageGroup::find(\Ref::LANGUAGE_GROUP_PARTNER_UUID);

        $code = ['Cyyhch','XqC9sR'];

        //Delete previsous FR content
        LanguageContent::whereIn('code', $code)
            ->where('lang_rid',\Ref::LANG_FR)
            ->where('language_group_uuid',$groupPartner->getKey())
            ->delete();

        LanguageContent::whereIn('code', $code)
            ->where('lang_rid',\Ref::LANG_EN)
            ->where('language_group_uuid',$groupPartner->getKey())
            ->delete();
    }
}
