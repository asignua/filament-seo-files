<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A blog post with one title and body per language: `title_uk`, `body_en`, …
 *
 * @property int $id
 * @property string $slug
 * @property string|null $title_en
 * @property string|null $title_uk
 * @property string|null $excerpt_en
 * @property string|null $body_en
 * @property string|null $body_uk
 * @property bool $published
 */
class Post extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['published' => 'boolean'];
    }
}
