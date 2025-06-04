<?php

namespace App\Services;

use App\Helpers\Help;
use App\Models\User;

class UserService
{
    /**
     * Validate external_id from User.
     *
     * @return string
     */
    public function generateExtId($user)
    {
        if (!isset($user->external_id)) {
            $external_id = Help::generateExtId('USER');

            $cleanExtenalId = $this->removeSpecialCharactersFromExternalId($external_id);

            $user->external_id = $cleanExtenalId;
        } else {
            $user->external_id = $this->removeSpecialCharactersFromExternalId($user->external_id);
        }
    }

    public function removeSpecialCharactersFromExternalId($external_id)
    {
        return preg_replace('/[^a-zA-Z0-9]/', '', $external_id);
    }

    public function getUserByKeys($request)
    {
        return User::where('email', $request['email'])->where('password', $request['password'])->first();
    }
}
