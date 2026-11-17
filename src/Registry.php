<?php declare(strict_types=1);

namespace Rush\Manager;

use JsonException;
use Rush\Contract\ToolInterface;
use RuntimeException;


final class Registry
{
    public function __construct(
        private readonly string $projectRoot,
    ) {
    }


    /**
     * Discovers and filters tools for the project.
     *
     * @return array<string, ToolInterface> keyed by tool key
     */
    public function discover(): array
    {
        $config = $this->config();
        $discovered = $this->discoveredTools();

        if ($config === null) {
            ksort($discovered);

            return $discovered;
        }

        $entries = $config['tools'] ?? null;

        if ( ! is_array($entries)) {
            throw new RuntimeException('rush.json: "tools" must be an object');
        }

        $selected = [];

        foreach ($entries as $key => $entry) {
            if ( ! is_string($key) || $key === '') {
                throw new RuntimeException('rush.json: tool keys must be non-empty strings');
            }

            if ($entry === false) {
                continue;
            }

            if ($entry === true) {
                if ( ! isset($discovered[$key])) {
                    throw new RuntimeException("rush.json: tool '{$key}' is enabled but not installed");
                }

                $selected[$key] = $discovered[$key];
                continue;
            }

            $class = is_string($entry)
                ? $entry
                : (is_array($entry) && is_string($entry['class'] ?? null) ? $entry['class'] : null);

            if ($class === null) {
                throw new RuntimeException("rush.json: invalid entry for tool '{$key}'");
            }

            $tool = $this->instantiate($class);

            if ($tool->key() !== $key) {
                throw new RuntimeException(
                    "rush.json: tool key '{$key}' does not match {$class}::key() = '{$tool->key()}'",
                );
            }

            $selected[$key] = $tool;
        }

        ksort($selected);

        return $selected;
    }


    /** @return array<string, ToolInterface> */
    private function discoveredTools(): array
    {
        $tools = [];
        $seen = [];

        foreach ($this->installedPackages() as $package) {
            $class = self::toolClass($package['extra']);

            if ($class === null || isset($seen[$class])) {
                continue;
            }

            $seen[$class] = true;
            $tool = $this->instantiate($class);
            $tools[$tool->key()] = $tool;
        }

        $root = $this->readJson($this->projectRoot .'/composer.json');

        if (is_array($root)) {
            $class = self::toolClass($root['extra'] ?? null);

            if ($class !== null && ! isset($seen[$class])) {
                $tool = $this->instantiate($class);
                $tools[$tool->key()] = $tool;
            }
        }

        return $tools;
    }


    /**
     * @return list<array{name: string, extra: mixed}>
     */
    private function installedPackages(): array
    {
        $file = $this->projectRoot .'/vendor/composer/installed.json';

        if ( ! is_file($file)) {
            return [];
        }

        $data = $this->readJson($file);

        if ( ! is_array($data)) {
            throw new RuntimeException('invalid vendor/composer/installed.json');
        }

        /** @var mixed $packages */
        $packages = $data['packages'] ?? $data;

        if ( ! is_array($packages)) {
            return [];
        }

        $result = [];

        foreach ($packages as $package) {
            if ( ! is_array($package)) {
                continue;
            }

            $result[] = [
                'name' => is_string($package['name'] ?? null) ? $package['name'] : '',
                'extra' => $package['extra'] ?? null,
            ];
        }

        return $result;
    }


    private static function toolClass(mixed $extra): ?string
    {
        if ( ! is_array($extra)) {
            return null;
        }

        $rush = $extra['rush'] ?? null;

        if (is_string($rush)) {
            return $rush !== '' ? $rush : null;
        }

        if ( ! is_array($rush)) {
            return null;
        }

        $tool = $rush['tool'] ?? null;

        return is_string($tool) && $tool !== '' ? $tool : null;
    }


    private function instantiate(string $class): ToolInterface
    {
        if ( ! class_exists($class)) {
            throw new RuntimeException("tool class not found: {$class}");
        }

        $tool = new $class();

        if ( ! $tool instanceof ToolInterface) {
            throw new RuntimeException("class does not implement ToolInterface: {$class}");
        }

        return $tool;
    }


    /** @return array<array-key, mixed>|null */
    private function config(): ?array
    {
        $file = $this->projectRoot .'/rush.json';

        if ( ! is_file($file)) {
            return null;
        }

        $data = $this->readJson($file);

        if ( ! is_array($data)) {
            throw new RuntimeException('rush.json must contain an object');
        }

        return $data;
    }


    private function readJson(string $file): mixed
    {
        if ( ! is_file($file) || ! is_readable($file)) {
            return null;
        }

        $raw = file_get_contents($file);

        if ($raw === false) {
            throw new RuntimeException("could not read {$file}");
        }

        try {
            return json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("invalid JSON in {$file}: ". $exception->getMessage(), 0, $exception);
        }
    }
}
