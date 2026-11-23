[English](README.md) | [Português](README.pt.md)

![PHP](https://img.shields.io/badge/PHP-%20^8.3-777BB4)
![PHPStan-Level](https://img.shields.io/badge/PHPStan-Level%209-224488)
![License](https://img.shields.io/badge/License-MIT-777)

# RUSH

**RUSH** — Registry-driven Unified Shell Helper.

Um runtime de CLI leve e extensível para tools. Um binário, um contrato, um
lifecycle: registre tools, exponha comandos e mantenha a automação previsível.

> **Pré-1.0:** releases minor podem incluir mudanças incompatíveis.

- **Registry-driven**: as tools se registram por metadados do Composer e `rush.json`.
- **Unified**: deploy, banco de dados e tarefas do projeto compartilham uma única interface.
- **Shell Helper**: um aliado prático no terminal, sem lock-in de framework.

## 📦 Instalação

```bash
composer require --dev rushphp/rush
```

### Requisitos

- PHP 8.3 ou superior.

## 🚀 Início rápido

```bash
rush install
rush operator --name="Developer" --email="developer@example.com"
rush list
```

`rush install` cria o layout local `.rush/` e solicita que cada tool prepare sua
própria configuração.

## 🎛️ Ergonomia de comandos

- `rush --help`, `rush <tool> --help` e `rush <tool> <command> --help`.
- Flags curtas: `-h` (ajuda), `-v` (versão) e clusters como `-ab`.
- Opções repetíveis: `--tag=a --tag=b` (lidas com `InputInterface::strings()`).
- Comandos de tool devem ser escopados pela key da tool (`code push`); comandos
  reservados (`install`, `list`, `operator`) não podem ser sobrescritos.
- Tools podem dispensar o gate de operador com `OperatorPolicyInterface`.

## 🧩 Tools

O RUSH não inclui tools por padrão. Instale as que precisar:

```bash
composer require --dev rushphp/code-tool
composer require --dev rushphp/schema-tool
```

As tools se declaram no `composer.json`:

```json
"extra": {
    "rush": {
        "tool": "Rush\\Tool\\Code\\CodeTool"
    }
}
```

Depois habilite-as no `rush.json`:

```json
{
    "tools": {
        "code": true,
        "schema": true
    }
}
```

Sem `rush.json`, todas as tools descobertas são anexadas. Com `rush.json`,
apenas as tools listadas são anexadas:

- `true` — habilita uma tool descoberta;
- `false` — desabilita uma tool;
- `{ "class": "App\\Tool\\App" }` — anexa uma tool interna/root explicitamente.

## 🛠️ Escrevendo uma tool

Uma tool implementa `Rush\Contract\ToolInterface`, do pacote
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

Os comandos recebem `Rush\Contract\InputInterface` e
`Rush\Contract\OutputInterface`; eles nunca tocam o terminal diretamente.

## 🐳 Docker

Uma imagem de runtime está disponível em `resources/docker/Dockerfile`:

```bash
docker build -t rushphp/rush:local resources/docker
docker run --rm -it -v "$PWD:/work" -w /work rushphp/rush:local php vendor/bin/rush list
```

## 🤝 Contribuições

Contribuições são bem-vindas. Abra uma issue ou envie um pull request.

## 📄 Licença

MIT. Consulte [LICENSE](LICENSE).
