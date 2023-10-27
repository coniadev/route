<?php

declare(strict_types=1);

namespace Conia\Route\Renderer;

use Attribute;
use Conia\Route\Registry;
use Conia\Route\Renderer\Renderer;
use Conia\Route\ResponseWrapper;

/** @psalm-api */
#[Attribute]
class Render
{
    protected array $args;

    public function __construct(protected string $renderer, mixed ...$args)
    {
        $this->args = $args;
    }

    public function render(Registry $registry, mixed $data): string
    {
        return $this->getRenderer($registry)->render($data, ...$this->args);
    }

    public function response(Registry $registry, mixed $data): ResponseWrapper
    {
        return $this->getRenderer($registry)->response($data, ...$this->args);
    }

    protected function getRenderer(Registry $registry): Renderer
    {
        $renderer = $registry->tag(Renderer::class)->get($this->renderer);
        assert($renderer instanceof Renderer);

        return $renderer;
    }
}
