<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Tests\Fixtures;

use Asignua\FilamentSeoFiles\Actions\EditRobotsAction;
use Filament\Actions\Action;
use Filament\Pages\Page;

/**
 * A host page with weaker access than the plugin (a shared "Tools" page) that embeds one
 * of the public actions.
 */
class ToolsPage extends Page
{
    public static function canAccess(): bool
    {
        return true;
    }

    public function editRobotsAction(): Action
    {
        return EditRobotsAction::make();
    }
}
