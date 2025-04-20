<?php

namespace YorCreative\LaravelArgonautDTO\Tests\Support\DTOs;

use Illuminate\Support\Carbon;
use YorCreative\LaravelArgonautDTO\ArgonautDTO;

class UserDTO extends ArgonautDTO
{
    public ?string $firstName = null;

    public ?string $lastName = null;

    public string $username;

    public ?string $fullName = null;

    public string $email;

    public ?Carbon $registeredAt = null;

    protected array $casts = [
        'firstName' => 'string',
        'lastName' => 'string',
        'username' => 'string',
        'email' => 'string',
        'fullName' => 'string',
        'registeredAt' => Carbon::class,
    ];

    protected array $prioritizedAttributes = [
        'firstName', 'lastName',
    ];

    public function rules()
    {
        return [
            'firstName' => ['nullable', 'string', 'max:32'],
            'lastName' => ['nullable', 'string', 'max:32'],
            'username' => ['required', 'string', 'max:64'],
            'email' => ['required', 'string', 'email', 'max:255'],
        ];
    }

    public function setFirstName($value)
    {
        $this->firstName = $value;
        $this->fullName = $this->firstName.' '.$this->lastName;
    }

    public function setLastName($value)
    {
        $this->lastName = $value;
        $this->fullName = $this->firstName.' '.$this->lastName;
    }
}
