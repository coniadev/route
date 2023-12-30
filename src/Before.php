<?php

declare(strict_types=1);

namespace Conia\Route;

use Psr\Http\Message\ServerRequestInterface as Request;

interface Before
{
    public function handle(Request $request): Request;
}
