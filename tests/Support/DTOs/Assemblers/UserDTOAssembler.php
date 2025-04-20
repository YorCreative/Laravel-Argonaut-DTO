<?php

namespace YorCreative\LaravelArgonautDTO\Tests\Support\DTOs\Assemblers;

use YorCreative\LaravelArgonautDTO\ArgonautAssembler;
use YorCreative\LaravelArgonautDTO\Tests\Support\DTOs\FullNameDTO;
use YorCreative\LaravelArgonautDTO\Tests\Support\DTOs\UserDTO;

class UserDTOAssembler extends ArgonautAssembler
{
    public static function toUserDTO(object $input): UserDTO
    {
        return new UserDTO([
            'username' => $input?->display_name,
            'firstName' => $input->first_name ?? null,
            'lastName' => $input->last_name ?? null,
            'email' => $input->email,
        ]);
    }

    public static function toFullNameDTO(object $input): FullNameDTO
    {
        if (is_null($input?->first_name) && is_null($input?->last_name)) {
            $fullName = $input->username;
        } else {
            $fullName = $input->first_name.' '.$input->last_name;
        }

        return new FullNameDTO([
            'fullName' => $fullName,
        ]);
    }
}
