<?php declare(strict_types=1);

use Rush\Manager\Output;


test('writes each output level to the correct stream', function (): void {
    $stdout = fopen('php://memory', 'w+');
    $stderr = fopen('php://memory', 'w+');

    if ($stdout === false || $stderr === false) {
        throw new RuntimeException('could not open output streams');
    }

    $output = new Output($stdout, $stderr);
    $output->write('raw');
    $output->line('plain');
    $output->info('info');
    $output->success('success');
    $output->warning('warning');
    $output->error('error');
    $output->separator();

    rewind($stdout);
    rewind($stderr);
    $out = (string) stream_get_contents($stdout);
    $err = (string) stream_get_contents($stderr);

    expect($out)->toContain('plain')
        ->and($out)->toContain('raw')
        ->and($out)->toContain('info')
        ->and($out)->toContain('success')
        ->and($out)->toContain('warning')
        ->and($out)->not->toContain('error')
        ->and($out)->toContain(str_repeat('-', 60))
        ->and($err)->toContain('error')
        ->and($err)->not->toContain('plain');
});
