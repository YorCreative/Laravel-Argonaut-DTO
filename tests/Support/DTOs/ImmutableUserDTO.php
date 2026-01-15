<?php

namespace YorCreative\LaravelArgonautDTO\Tests\Support\DTOs;

use Illuminate\Support\Carbon;
use YorCreative\LaravelArgonautDTO\ArgonautImmutableDTO;

class ImmutableUserDTO extends ArgonautImmutableDTO
{
    public readonly ?string $firstName;

    public readonly ?string $lastName;

    public readonly string $username;

    public readonly string $email;

    public readonly ?Carbon $registeredAt;

    protected array $casts = [
        'registeredAt' => Carbon::class,
    ];

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:64'],
            'email' => ['required', 'email', 'max:255'],
        ];
    }
}
