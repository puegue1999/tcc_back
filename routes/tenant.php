<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
