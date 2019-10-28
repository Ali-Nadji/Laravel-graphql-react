<?php

/**
 * Generate language for Jobmaker user
 * @param $invitation \Models\Db\Jmaker\JmakerInvitation
 * @param string $language_id
 */
function languageJobmaker($invitation, $language_id = \Ref::LANG_FR)
{

    // Construction du singleton de language
    app()->singleton('lang', function ($app) use ($invitation, $language_id) {

        // Recuperation des patch
        $languageGroups = \Models\Db\Languages\LanguageGroup::where('collection', 'language')->get();

        // BASE
        $base = $languageGroups->filter(function (\Models\Db\Languages\LanguageGroup $languageGroup) {

            if (array_get($languageGroup->config, 'type', false) != \App\Http\Controllers\Inside\LanguageGroupController::TYPE_BASE) {
                return false;
            }

            if (array_get($languageGroup->config, 'interface', false) != \Ref::INTERFACE_JOBMAKER) {
                return false;
            }

            return true;
        })->first();
        throw_if(empty($base), 'Impossible de trouver un language pour l\'interface');


        // On ajout le base
        $instructions[] = n(\Models\Language::class, 'merge', [$base->uuid])->serialize();

        // CAMPAIGN
        if (!empty($invitation)) {

            if ($campaign = $invitation->campaign) {

                if (!empty($adaptation = $campaign->adaptation)) {
                    // Ajout des patchs

                    foreach ((array)$campaign->adaptation as $adaptationUUID) {

                        $languageGroup = $languageGroups->where('uuid', $adaptationUUID)->first();

                        if (!empty($languageGroup)) {
                            $coef = array_get($languageGroup->config, 'score', 0);
                            $instructions[] = n(\Models\Language::class, 'merge', [$adaptationUUID, 10 + $coef])->serialize();
                        }
                    }
                }
            };

            // CLient
            /** @var \Models\Db\Clients\Client $client */
            if ($client = $invitation->client) {

                $culture = $languageGroups->filter(function (\Models\Db\Languages\LanguageGroup $group) {
                    return array_get($group->config, 'type', false) == \App\Http\Controllers\Inside\LanguageGroupController::TYPE_CULTURE;
                });



                // Ajout des patchs
                foreach ((array)$client->adaptation as $adaptationUUID) {
                    $languageGroups = $culture->where('uuid', $adaptationUUID)->first();
                    if (!empty($languageGroups)) {
                        $coef = array_get($languageGroups->config, 'score', 0);
                        $instructions[] = n(\Models\Language::class, 'merge', [$adaptationUUID, 100 + intval($coef)])->serialize();
                    }
                }

                // Cas du pacth client propre
                if ($client->language_group_uuid) {
                    $instructions[] = n(\Models\Language::class, 'merge', [$client->language_group_uuid, 10000])->serialize();
                }
            }
        }
        // Creation du language


        return new \Models\Language($instructions, $language_id, 'jobmaker.' . md5(json_encode($instructions)), true);
    });
}


/**
 * Generate language for the front interface.
 * @param string $language_id
 */
function languageFront($language_id = \Ref::LANG_FR)
{
    //$locale = \Ref::LANG_FR;
    // Construction du singleton de language

    App::setLocale(\Models\Db\Languages\Languages::find($language_id)->locale);

    app()->singleton('lang', function ($app) use ($language_id) {

        // Recuperation des patch
        $languageGroup = \Models\Db\Languages\LanguageGroup::where('collection', 'language')
            ->where('config->type', \App\Http\Controllers\Inside\LanguageGroupController::TYPE_BASE)
            ->where('config->interface', \Ref::INTERFACE_FRONT)
            ->first();

        //
        $instructions = [n(\Models\Language::class, 'merge', [$languageGroup->uuid, 1])->serialize()];
        return new \Models\Language($instructions, $language_id, md5(json_encode($instructions)));
    });
}

/**
 * Render a basic template
 *
 * @param $title
 * @param $content
 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
 */
function basic($title, $content, $view = 'basic')
{
    return view($view, ['title' => $title, 'content' => $content]);
}

/**
 * Met en avant les
 *
 * @param $string
 * @return mixed
 */
function url_highlight($string)
{
    $matches = [];
    if (preg_match_all('#https?:[^\s]+#', $string, $matches)) {
        foreach ($matches[0] as $url) {
            $string = str_replace($url, html('a', ['target' => '_blank', 'href' => $url], $url), $string);
        }
    }

    return $string;
}

/**
 * Recuperation des utms
 *
 * @return array
 */
function utm()
{

    $utm = [];
    foreach (\request()->all() as $k => $v) {
        if (preg_match('#^utm_.+#', $k)) {
            $utm[$k] = $v;
        }
    }

    return $utm;
}


/**
 * Renvoie des pair de donnée
 *
 * @param $table
 * @param $id
 * @param $value
 * @param null $order
 * @param null $where
 * @return array
 */
function pairs($table, $id, $value, $order = null, $where = null)
{

    // QUERY
    $query = \query($table, [$id, $value]);

    // ORDER
    is_null($order) ? $query->orderBy($value) : $query->orderBy($order);

    if (!is_null($where)) {
        foreach (explode(';', $where) as $expr) {
            call_user_func_array([$query, 'where'], explode(',', $expr));
        }
    }

    return $query->pluck($value, $id)->toArray();
}

/**
 * Alias for \request()->isXmlHttpRequest()
 *
 * @return bool
 */
function is_ajax()
{
    return \request()->isXmlHttpRequest();
}

//TODO CSI CLEAN THIS CODE
function cleanArrayByValue($value,$array) {

    if (is_array($array))  {
        return array_map(function($val) use ($value) {
            if (is_array($val)) {
                return cleanArrayByValue($value,$val);
            } else {
                return $value;
            }
        }, $array);
    } else {
        return $array;
    }
 }
 
 function __($index, ...$params)
 {
    if(session('local') == \REF::LANG_DEBUG) {
        $test = app('lang')->get($index, ...$params);

        if (is_array($test))  {
            $test = cleanArrayByValue($index,$test);
        } else {
            $test = $index;
        }

        return $test;
    } else {
        return app('lang')->get($index, ...$params);
    }
 }
 
/*
function __($index, ...$params)
{
    return app('lang')->get($index, ...$params);
}*/

/**
 * Alias pôur lma création d'un nenuphar
 *
 * @param array ...$params
 * @return \FrenchFrogs\Core\Nenuphar
 */
function n(string $class, string $method = null, array $params = [], string $interpreter = 'default', $extras = [])
{
    return new \FrenchFrogs\Core\Nenuphar($class, $method, $params, $interpreter, $extras);
}

/**
 * Login as and redirect to the url.
 * @param $url
 * @param $jmaker_uuid
 * @return \Illuminate\Http\RedirectResponse
 */
function linkme($url, $jmaker_uuid)
{
    // Récuperation de l'auth
    $auth = auth(\Ref::INTERFACE_JOBMAKER);

    // on verifie que l'urilisateur est le même que celui logué
    $auth->check() && (jmaker()->uuid != $jmaker_uuid) && $auth->logout();

    // si pas authentifié, on fore le login
    !$auth->check() && $auth->loginUsingId($jmaker_uuid);

    return redirect()->to($url);
}

function md2html($markown)
{
    return \Michelf\Markdown::defaultTransform($markown);
}

/**
 *
 *
 * @param $method
 * @param array ...$params
 */
function _parse_lang($method, ...$params)
{
    
    Validator::validate([$method], [\Illuminate\Validation\Rule::in(['tag', 'text'])]);

    do {
        $code = str_random(6);
    } while (\Models\Db\Languages\LanguageContent::where('code', $code)->first());

    $groupJobmaker = \Models\Db\Languages\LanguageGroup::where('config->type', App\Http\Controllers\Inside\LanguageGroupController::TYPE_BASE)
                ->where('config->interface', \Ref::INTERFACE_JOBMAKER)
                ->firstOrFail();

    // Création du contenu
    $content = new \Models\Db\Languages\LanguageContent();
    $content->uuid = generateNewUUID();
    $content->code = $code;
    $content->language_group_uuid = $groupJobmaker->getKey();
    $content->lang_rid = Ref::LANG_FR;
    $content->instruction = ['method' => $method, 'params' => $params];
    $content->is_published = true;
    $content->published_at = \Carbon\Carbon::now();
    $content->save();

    return array($code,$method,$params);
    //return "__('$code')";
}
