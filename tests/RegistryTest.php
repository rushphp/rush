<?php declare(strict_types=1);

use Rush\Manager\Registry;
use Rush\Support\FileSystem;
use Tests\Fixtures\EchoTool;

function installedEcho(): string
{
    return json_encode([
        'packages' => [
            [
                'name' => 'acme/echo-tool',
                'extra' => ['rush' => ['tool' => EchoTool::class]],
            ],
        ],
    ], JSON_THROW_ON_ERROR);
}

test('discovers tools from installed packages', function (): void {
    $root = makeProject([
        'rush.json' => json_encode(['tools' => ['echo' => true]], JSON_THROW_ON_ERROR),
        'vendor/composer/installed.json' => installedEcho(),
    ]);

    $tools = (new Registry($root))->discover();

    expect(array_keys($tools))->toBe(['echo'])
        ->and($tools['echo'])->toBeInstanceOf(EchoTool::class);

    FileSystem::removeDir($root);
});

test('attaches all discovered tools when rush.json is absent', function (): void {
    $root = makeProject([
        'vendor/composer/installed.json' => installedEcho(),
    ]);

    $tools = (new Registry($root))->discover();

    expect(array_keys($tools))->toBe(['echo']);

    FileSystem::removeDir($root);
});

test('skips tools disabled in rush.json', function (): void {
    $root = makeProject([
        'rush.json' => json_encode(['tools' => ['echo' => false]], JSON_THROW_ON_ERROR),
        'vendor/composer/installed.json' => installedEcho(),
    ]);

    expect((new Registry($root))->discover())->toBe([]);

    FileSystem::removeDir($root);
});

test('accepts an explicit class mapping', function (): void {
    $root = makeProject([
        'rush.json' => json_encode([
            'tools' => ['echo' => ['class' => EchoTool::class]],
        ], JSON_THROW_ON_ERROR),
    ]);

    $tools = (new Registry($root))->discover();

    expect(array_keys($tools))->toBe(['echo']);

    FileSystem::removeDir($root);
});

test('throws when an enabled tool is not installed', function (): void {
    $root = makeProject([
        'rush.json' => json_encode(['tools' => ['missing' => true]], JSON_THROW_ON_ERROR),
    ]);

    expect(fn () => (new Registry($root))->discover())
        ->toThrow(RuntimeException::class);

    FileSystem::removeDir($root);
});
