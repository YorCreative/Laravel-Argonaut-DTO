<?php

namespace YorCreative\LaravelArgonautDTO\Tests\Unit;

use ReflectionClass;
use ReflectionMethod;
use YorCreative\LaravelArgonautDTO\ArgonautDTO;
use YorCreative\LaravelArgonautDTO\ArgonautImmutableDTO;
use YorCreative\LaravelArgonautDTO\Tests\TestCase;

class PublicApiSurfaceTest extends TestCase
{
    public function test_argonaut_dto_public_api_surface(): void
    {
        $expected = [
            '__construct',
            'collection',
            'except',
            'getAttributesToUpdate',
            'isValid',
            'merge',
            'only',
            'setAttribute',
            'setAttributes',
            'toArray',
            'toJson',
            'validate',
        ];

        $this->assertPublicMethods(ArgonautDTO::class, $expected);
    }

    public function test_argonaut_immutable_dto_public_api_surface(): void
    {
        $expected = [
            '__construct',
            'collection',
            'except',
            'isValid',
            'only',
            'toArray',
            'toJson',
            'validate',
        ];

        $this->assertPublicMethods(ArgonautImmutableDTO::class, $expected);
    }

    private function assertPublicMethods(string $class, array $expected): void
    {
        $actual = collect((new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC))
            ->map->getName()
            ->sort()
            ->values()
            ->all();

        sort($expected);

        $this->assertSame($expected, $actual, "Public API surface mismatch for $class");
    }
}
