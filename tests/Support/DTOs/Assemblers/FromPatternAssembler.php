<?php

namespace YorCreative\LaravelArgonautDTO\Tests\Support\DTOs\Assemblers;

use YorCreative\LaravelArgonautDTO\ArgonautAssembler;
use YorCreative\LaravelArgonautDTO\Tests\Support\DTOs\UserDTO;

/**
 * Test assembler using the `from<ClassName>` naming pattern
 * instead of the more common `to<ClassName>` pattern.
 */
class FromPatternAssembler extends ArgonautAssembler
{
    /**
     * Assembles a UserDTO using the `from` naming convention.
     */
    public static function fromUserDTO(object $input): UserDTO
    {
        return new UserDTO([
            'username' => $input->name ?? 'default-user',
            'email' => $input->email ?? 'default@example.com',
            'firstName' => $input->first_name ?? null,
            'lastName' => $input->last_name ?? null,
        ]);
    }
}
