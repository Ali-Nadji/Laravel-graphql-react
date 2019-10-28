<?php

namespace App\Http\Requests;

use Infrastructure\Http\ApiRequest;

class ExportCalendarRequest extends ApiRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'loc' => 'string|required',
            'desc' => 'string|required',
            'title' => 'string|required',
        ];
    }
}
