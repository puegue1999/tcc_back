<?php

namespace App\Http\Controllers\Users;

use App\Settings\AdvancedSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserSaveRequest extends FormRequest
{

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'email'       => 'required|string',
            'password'    => 'required|string',
            'name'        => 'required|string',
        ];
    }
}
