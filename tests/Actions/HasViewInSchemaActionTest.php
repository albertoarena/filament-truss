<?php

declare(strict_types=1);

use AlbertoArena\FilamentTruss\Actions\Concerns\HasViewInSchemaAction;
use AlbertoArena\FilamentTruss\Tests\Fixtures\Book;
use Filament\Facades\Filament;

/**
 * The trait exists so a panel opts a resource in with one line rather than
 * repeating the model to table join everywhere it wants the button.
 *
 * It asks a resource only for `getModel()`, which every Filament resource has,
 * so the fake below is the whole contract and a real resource is not needed to
 * prove it.
 */
class FakeBookResource
{
    use HasViewInSchemaAction;

    public static function getModel(): string
    {
        return Book::class;
    }
}

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('testing'));
    app()->detectEnvironment(fn () => 'local');
    config()->set('truss.excluded_tables', []);
});

it('gives a resource the button, pointed at its own table', function () {
    expect(FakeBookResource::viewInSchemaAction()->getUrl())->toContain('focus=books');
});

it('carries the same guards as the action it builds', function () {
    config()->set('truss.enabled', false);

    expect(FakeBookResource::viewInSchemaAction()->isVisible())->toBeFalse();
});
