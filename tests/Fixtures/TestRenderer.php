<?php

declare(strict_types=1);

namespace Conia\Route\Tests\Fixtures;

use Conia\Route\After;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;

class TestRenderer implements After
{
    public function __construct(protected ResponseFactoryInterface $responseFactory)
    {
    }

    public function handle(mixed $data): Response
    {
        $response = $this->responseFactory->createResponse()->withHeader('Content-Type', 'text/plain');
        $response->getBody()->write(print_r($data, return: true));

        return $response;
    }
}
