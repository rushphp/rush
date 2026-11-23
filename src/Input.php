<?php declare(strict_types=1);

namespace Rush\Manager;

use InvalidArgumentException;
use Rush\Contract\InputInterface;
use Rush\Contract\Option;


final class Input implements InputInterface
{
    /**
     * @param array<string, string|bool|int|list<string>> $options
     * @param list<string> $arguments
     */
    public function __construct(
        private readonly array $options = [],
        private readonly array $arguments = [],
    ) {
    }


    /**
     * @param list<string> $tokens
     * @param list<Option> $definitions
     */
    public static function parse(array $tokens, array $definitions): self
    {
        $known = [];
        $shorts = [];

        foreach ($definitions as $option) {
            if (isset($known[$option->name])) {
                throw new InvalidArgumentException("duplicate option: --{$option->name}");
            }

            $known[$option->name] = $option;

            if ($option->short !== null) {
                if (isset($shorts[$option->short])) {
                    throw new InvalidArgumentException("duplicate short option: -{$option->short}");
                }

                $shorts[$option->short] = $option;
            }
        }

        /** @var array<string, string|bool|int|list<string>> $options */
        $options = [];
        /** @var list<string> $arguments */
        $arguments = [];
        $literal = false;

        for ($index = 0, $count = count($tokens); $index < $count; $index++) {
            $token = $tokens[$index];

            if ($literal) {
                $arguments[] = $token;
                continue;
            }

            if ($token === '--') {
                $literal = true;
                continue;
            }

            if (str_starts_with($token, '--')) {
                $index = self::parseLong($tokens, $index, $known, $options);
                continue;
            }

            if (str_starts_with($token, '-') && $token !== '-') {
                $index = self::parseShort($tokens, $index, $shorts, $options);
                continue;
            }

            $arguments[] = $token;
        }

        foreach ($definitions as $option) {
            if (array_key_exists($option->name, $options)) {
                continue;
            }

            if ($option->required) {
                throw new InvalidArgumentException("missing required option: --{$option->name}");
            }

            if ($option->default !== null) {
                $options[$option->name] = $option->default;
            }
        }

        return new self($options, $arguments);
    }


    public function has(string $name): bool
    {
        return array_key_exists($name, $this->options);
    }

    public function bool(string $name): bool
    {
        return ($this->options[$name] ?? false) === true;
    }

    public function string(string $name, ?string $default = null): ?string
    {
        $value = $this->options[$name] ?? $default;

        return is_string($value) ? $value : $default;
    }

    public function int(string $name, int $default): int
    {
        $value = $this->options[$name] ?? null;

        if (is_int($value)) {
            return $value;
        }

        return is_string($value) && ctype_digit($value) ? (int) $value : $default;
    }

    /** @return list<string> */
    public function strings(string $name): array
    {
        $value = $this->options[$name] ?? null;

        if (is_string($value)) {
            return $value === '' ? [] : [$value];
        }

        if (is_array($value)) {
            return array_values(array_filter($value, static fn (string $item): bool => $item !== ''));
        }

        return [];
    }

    public function hasValue(string $name): bool
    {
        $value = $this->options[$name] ?? null;

        if (is_string($value)) {
            return $value !== '';
        }

        if (is_int($value)) {
            return true;
        }

        if (is_array($value)) {
            return $value !== [];
        }

        return false;
    }

    /** @return list<string> */
    public function arguments(): array
    {
        return $this->arguments;
    }


    /**
     * @param list<string> $tokens
     * @param array<string, Option> $known
     * @param array<string, string|bool|int|list<string>> $options
     */
    private static function parseLong(array $tokens, int $index, array $known, array &$options): int
    {
        $name = substr($tokens[$index], 2);
        $value = '';
        $hasValue = false;

        if (str_contains($name, '=')) {
            [$name, $value] = explode('=', $name, 2);
            $hasValue = true;
        }

        $option = $known[$name] ?? null;

        if ($option === null) {
            throw new InvalidArgumentException("unknown option: --{$name}");
        }

        if ( ! $option->takesValue) {
            if ($hasValue) {
                throw new InvalidArgumentException("--{$name} does not accept a value");
            }

            $options[$name] = true;

            return $index;
        }

        if ( ! $hasValue) {
            $next = $tokens[$index + 1] ?? null;

            if ($next === null || ($next !== '-' && str_starts_with($next, '-'))) {
                throw new InvalidArgumentException("--{$name} requires a value");
            }

            $value = $next;
            $index++;
        }

        if ($value === '') {
            throw new InvalidArgumentException("--{$name} requires a value");
        }

        self::assign($options, $option, $value);

        return $index;
    }


    /**
     * @param list<string> $tokens
     * @param array<string, Option> $shorts
     * @param array<string, string|bool|int|list<string>> $options
     */
    private static function parseShort(array $tokens, int $index, array $shorts, array &$options): int
    {
        $cluster = substr($tokens[$index], 1);
        $length = strlen($cluster);

        for ($position = 0; $position < $length; $position++) {
            $char = $cluster[$position];
            $option = $shorts[$char] ?? null;

            if ($option === null) {
                throw new InvalidArgumentException("unknown option: -{$char}");
            }

            if ( ! $option->takesValue) {
                $options[$option->name] = true;
                continue;
            }

            if ($length > 1) {
                throw new InvalidArgumentException("-{$char} requires a value and cannot be combined");
            }

            $next = $tokens[$index + 1] ?? null;

            if ($next === null || $next === '' || ($next !== '-' && str_starts_with($next, '-'))) {
                throw new InvalidArgumentException("-{$char} requires a value");
            }

            self::assign($options, $option, $next);

            return $index + 1;
        }

        return $index;
    }


    /** @param array<string, string|bool|int|list<string>> $options */
    private static function assign(array &$options, Option $option, string $value): void
    {
        if ( ! $option->repeatable) {
            $options[$option->name] = $value;

            return;
        }

        $values = $options[$option->name] ?? [];

        if ( ! is_array($values)) {
            $values = [];
        }

        $values[] = $value;
        $options[$option->name] = $values;
    }
}
