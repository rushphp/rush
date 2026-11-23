[English](README.md) | [Português](README.pt.md)

![PHP](https://img.shields.io/badge/PHP-%20^8.3-777BB4)
![PHPStan-Level](https://img.shields.io/badge/PHPStan-Level%209-224488)
![License](https://img.shields.io/badge/License-MIT-777)

# RUSH

**RUSH** — Registry-driven Unified Shell Helper.

A lightweight, extensible CLI runtime for tools. One binary, one contract, one
lifecycle: register tools, expose commands, keep automation predictable.

> **Pre-1.0:** minor releases may include breaking changes.

- **Registry-driven**: tools register through Composer metadata and `rush.json`.
- **Unified**: deploy, database and project tasks share one interface.
- **Shell Helper**: a practical terminal ally, without framework lock-in.

## 📦 Installation

```bash
composer require --dev rushphp/rush
```

### Requirements

- PHP 8.3 or newer.

## 🚀 Quick start

```bash
rush install
rush operator --name="Developer" --email="developer@example.com"
rush list
```

`rush install` creates the local `.rush/` control layout and asks each tool to
prepare its own configuration.

## 🎛️ Command ergonomics

- `rush --help`, `rush <tool> --help` and `rush <tool> <command> --help`.
- Short flags: `-h` (help), `-v` (version) and clusters such as `-ab`.
- Repeatable options: `--tag=a --tag=b` (read with `InputInterface::strings()`).
- Tool commands must be scoped to the tool key (`code push`); reserved commands
  (`install`, `list`, `operator`) cannot be overridden.
- Tools can opt out of the operator gate with `OperatorPolicyInterface`.

## 🧩 Tools

RUSH ships no tools by default. Install the ones you need:

```bash
composer require --dev rushphp/code-tool
composer require --dev rushphp/schema-tool
```

Tools declare themselves in `composer.json`:

```json
"extra": {
    "rush": {
        "tool": "Rush\\Tool\\Code\\CodeTool"
    }
}
```

Then enable them in `rush.json`:

```json
{
    "tools": {
        "code": true,
        "schema": true
    }
}
```

Without `rush.json`, every discovered tool is attached. With `rush.json`, only
the listed tools are attached:

- `true` — enable a discovered tool;
- `false` — disable a tool;
- `{ "class": "App\\Tool\\App" }` — attach an internal/root tool explicitly.

## 🛠️ Writing a tool

A tool implements `Rush\Contract\ToolInterface` from
[`rushphp/contract`](https://github.com/rushphp/contract):

```php
final class DeployTool implements ToolInterface
{
    public function key(): string { return 'deploy'; }

    public function name(): string { return 'Deploy'; }

    public function description(): string { return 'Deploys the application.'; }

    public function version(): string { return '0.1.0'; }

    public function commands(string $projectRoot): array
    {
        return ['deploy push' => new PushCommand($projectRoot)];
    }

    public function install(string $projectRoot): array
    {
        return [];
    }
}
```

Commands receive `Rush\Contract\InputInterface` and
`Rush\Contract\OutputInterface`; they never touch the terminal directly.

## 🐳 Docker

A runtime image is available in `resources/docker/Dockerfile`:

```bash
docker build -t rushphp/rush:local resources/docker
docker run --rm -it -v "$PWD:/work" -w /work rushphp/rush:local php vendor/bin/rush list
```

## 🤝 Contributions

Contributions are welcome. Open an issue or submit a pull request.

## 📄 License

MIT. See [LICENSE](LICENSE).
