<?php

declare(strict_types=1);

namespace Conia\Route\Tests\Fixtures;

use Conia\Route\Renderer\Render;
use Psr\Http\Message\RequestInterface as Request;

class TestControllerWithRequest
{
    public function __construct(protected Request $request)
    {
    }

    public function wrongReturnType(): mixed
    {
        // This provokes a json_encode error
        return stream_context_create();
    }

    #[Render('text')]
    public function requestOnly(): string
    {
        return $this->request::class;
    }

    public function routeParams(string $string, float $float, int $int): array
    {
        return [
            'string' => $string,
            'float' => $float,
            'int' => $int,
            'request' => $this->request::class,
        ];
    }
}
