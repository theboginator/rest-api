<?php declare(strict_types=1);

namespace Reconmap\Models;

class Note
{
    public int $user_id;
    public string $visibility;
    public ?int $parent_id;
    public string $parent_type;
    public string $content;
}

