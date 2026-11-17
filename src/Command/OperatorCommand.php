<?php declare(strict_types=1);

namespace Rush\Manager\Command;

use RuntimeException;
use Rush\Contract\CommandInterface;
use Rush\Contract\ExitCode;
use Rush\Contract\InputInterface;
use Rush\Contract\Option;
use Rush\Contract\OutputInterface;
use Rush\Support\Operator;
use Rush\Support\OperatorFile;


final class OperatorCommand implements CommandInterface
{
    public function __construct(
        private readonly string $projectRoot,
    ) {
    }


    public function name(): string
    {
        return 'operator';
    }

    public function description(): string
    {
        return 'Show or configure the operator identity';
    }

    public function usage(): string
    {
        return 'rush operator [--name=<name> --email=<email>]';
    }

    /** @return list<Option> */
    public function options(): array
    {
        return [
            new Option('name', 'Operator name', true, null, false, 'name'),
            new Option('email', 'Operator email', true, null, false, 'email'),
        ];
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->string('name');
        $email = $input->string('email');

        if ($name === null && $email === null) {
            $operator = OperatorFile::read($this->projectRoot);

            if ($operator === null) {
                throw new RuntimeException(
                    'operator not configured; run ./rush operator --name="..." --email="..."',
                );
            }

            $output->line('name: '. $operator->name);
            $output->line('email: '. $operator->email);

            return ExitCode::Success->value;
        }

        if ($name === null || $email === null) {
            throw new RuntimeException('--name and --email are required together');
        }

        if (trim($name) === '') {
            throw new RuntimeException('--name must be a non-empty string');
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException('--email must be a valid email address');
        }

        OperatorFile::save($this->projectRoot, new Operator(trim($name), trim($email)));

        $output->success('operator configured');

        return ExitCode::Success->value;
    }
}
