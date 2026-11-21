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
