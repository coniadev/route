<?php

declare(strict_types=1);

namespace Conia\Route\Tests\Fixtures;

use Conia\Route\After;
use Psr\Http\Message\ResponseInterface as Response;
use RuntimeException;

class TestAfterAddText implements After
{
    public function handle(mixed $data): Response
    {
        if (!($data instanceof Response)) {
            throw new RuntimeException('Must be a response');
        }

        $data->getBody()->write('-appended');

        return $data;
    }

    public function replace(): bool
    {
        return false;
    }
}
