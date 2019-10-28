<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Models\Db\Languages\LanguageContent;
use Models\Db\Languages\LanguageGroup;

class AddContentLanguageLimitJmaker extends Migration
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
        $content->code = '1fYbsV';
        $content->language_group_uuid = $groupPartner->uuid;
        $content->instruction = [
            'method' => 'text',
            'params' => ["Vous avez atteint la limite d'invitations autorisées"],
        ];
        $content->is_published = true;
        $content->published_at = \Carbon\Carbon::now();
        $content->save();

        $content = new LanguageContent();
        $content->uuid = generateNewUUID();
        $content->lang_rid = Ref::LANG_EN;
        $content->code = '1fYbsV';
        $content->language_group_uuid = $groupPartner->uuid;
        $content->instruction = [
            'method' => 'text',
            'params' => ["You have reached the maximum invitation allowance"],
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

        $code = ['1fYbsV'];

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
