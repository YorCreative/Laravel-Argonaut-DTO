<?php

namespace YorCreative\LaravelArgonautDTO;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

/**
 * @extends Arrayable<string, mixed>
 */
interface ArgonautDTOContract extends Arrayable, Jsonable {}
