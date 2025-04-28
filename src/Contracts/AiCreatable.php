<?php

declare(strict_types=1);

namespace Koffielyder\LaravelAiModel\Contracts;

interface AiCreatable
{
    public static function getAiSchema(): array;
    public static function getAiRelations(): array;
}
