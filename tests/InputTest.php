<?php declare(strict_types=1);

use Rush\Contract\Option;
use Rush\Manager\Input;

test('parses flags, values and arguments', function (): void {
    $input = Input::parse(
        ['--target=staging', '--force', 'argument'],
        [
            new Option('target', 'Target', true),
            new Option('force', 'Force'),
        ],
    );

    expect($input->string('target'))->toBe('staging')
        ->and($input->bool('force'))->toBeTrue()
        ->and($input->arguments())->toBe(['argument']);
});

test('rejects unknown options', function (): void {
    Input::parse(['--unknown'], []);
})->throws(InvalidArgumentException::class);

test('requires a value when the option takes one', function (): void {
    Input::parse(['--target'], [new Option('target', 'Target', true)]);
})->throws(InvalidArgumentException::class);

test('stops parsing options after a literal separator', function (): void {
    $input = Input::parse(['--', '--not-an-option'], [new Option('force', 'Force')]);

    expect($input->arguments())->toBe(['--not-an-option']);
});

test('applies the option default when it is absent', function (): void {
    $input = Input::parse([], [new Option('format', 'Format', true, 'text')]);

    expect($input->string('format'))->toBe('text');
});

test('rejects a missing required option', function (): void {
    Input::parse([], [new Option('target', 'Target', true, null, true)]);
})->throws(InvalidArgumentException::class, 'missing required option: --target');

test('accepts a provided required option', function (): void {
    $input = Input::parse(['--target=staging'], [new Option('target', 'Target', true, null, true)]);

    expect($input->string('target'))->toBe('staging');
});

test('required takes precedence over default', function (): void {
    Input::parse([], [new Option('target', 'Target', true, 'staging', true)]);
})->throws(InvalidArgumentException::class, 'missing required option: --target');

test('exposes a single value as a string list', function (): void {
    $input = Input::parse(['--tag=alpha'], [new Option('tag', 'Tag', true)]);

    expect($input->strings('tag'))->toBe(['alpha'])
        ->and($input->strings('missing'))->toBe([]);
});

test('exposes repeatable values from the constructor', function (): void {
    $input = new Input(['tag' => ['alpha', 'beta']], []);

    expect($input->strings('tag'))->toBe(['alpha', 'beta']);
});

test('reports whether an option has a usable value', function (): void {
    $input = Input::parse(['--tag=alpha', '--force'], [
        new Option('tag', 'Tag', true),
        new Option('force', 'Force'),
    ]);

    expect($input->hasValue('tag'))->toBeTrue()
        ->and($input->hasValue('force'))->toBeFalse()
        ->and($input->hasValue('missing'))->toBeFalse();
});

test('parses short flags and clusters', function (): void {
    $input = Input::parse(['-ab'], [
        new Option('alpha', 'Alpha', false, null, false, 'value', 'a'),
        new Option('beta', 'Beta', false, null, false, 'value', 'b'),
    ]);

    expect($input->bool('alpha'))->toBeTrue()
        ->and($input->bool('beta'))->toBeTrue();
});

test('parses a short option that takes a value', function (): void {
    $input = Input::parse(['-t', 'staging'], [
        new Option('target', 'Target', true, null, false, 'target', 't'),
    ]);

    expect($input->string('target'))->toBe('staging');
});

test('rejects combining a short option that takes a value', function (): void {
    Input::parse(['-ab'], [
        new Option('alpha', 'Alpha', true, null, false, 'value', 'a'),
        new Option('beta', 'Beta', false, null, false, 'value', 'b'),
    ]);
})->throws(InvalidArgumentException::class, 'cannot be combined');

test('collects repeatable options', function (): void {
    $input = Input::parse(['--tag=a', '--tag', 'b'], [
        new Option('tag', 'Tag', true, null, false, 'tag', null, true),
    ]);

    expect($input->strings('tag'))->toBe(['a', 'b']);
});

test('does not consume the next option as a value', function (): void {
    Input::parse(['--name', '--force'], [
        new Option('name', 'Name', true),
        new Option('force', 'Force'),
    ]);
})->throws(InvalidArgumentException::class, '--name requires a value');

test('rejects duplicate option definitions', function (): void {
    Input::parse([], [new Option('a', 'A'), new Option('a', 'B')]);
})->throws(InvalidArgumentException::class, 'duplicate option');

test('rejects duplicate short option definitions', function (): void {
    Input::parse([], [
        new Option('a', 'A', false, null, false, 'value', 'x'),
        new Option('b', 'B', false, null, false, 'value', 'x'),
    ]);
})->throws(InvalidArgumentException::class, 'duplicate short option');
