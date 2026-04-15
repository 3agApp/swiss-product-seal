<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\AdminProductResource;
use Filament\Resources\Pages\ListRecords;

class ListAdminProducts extends ListRecords
{
    protected static string $resource = AdminProductResource::class;
}
