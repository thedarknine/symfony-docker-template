# symfony-docker-template

Template to start a Symfony project with Docker, bundling the tools and architecture
used across my projects: a Taskfile workflow, coding standards and static analysis,
tests, git hooks and a GitHub Actions CI.

## Stack

- **Symfony 8.1** on **PHP 8.4** (FPM, Alpine)
- **Twig** with `intl-extra`, **Symfony UX** Twig Components and Icons
- **AssetMapper** and **Tailwind CSS** (standalone binary, no Node build step)
- **Doctrine ORM** and Migrations on **MySQL 9.7**
- **Symfony Mailer**, caught by **Mailpit** in development

| Service   | Container        | Image                       | Host port   | Environment |
| --------- | ---------------- | --------------------------- | ----------- | ----------- |
| `app`     | `<slug>-app`     | PHP 8.4 FPM Alpine (custom) | -           | all         |
| `engine`  | `<slug>-server`  | `nginx:1.30-alpine`         | `APP_PORT`  | all         |
| `db`      | `<slug>-db`      | `mysql:9.7` (LTS)           | `DB_PORT`   | dev         |
| `adm`     | `<slug>-adm`     | `adminneoorg/adminneo`      | `ADM_PORT`  | dev         |
| `mailpit` | `<slug>-mailpit` | `axllent/mailpit`           | `MAIL_PORT` | dev         |

`<slug>` is `PROJECT_SLUG` from `.env`: several projects created from this template can run
side by side as long as they use different slugs and ports.

The PHP image also ships the development tools: Composer 2, Symfony CLI, Node.js and pnpm,
pcov, hadolint, gitleaks, shellcheck, shfmt and yamllint.

## Requirements

- Docker with the Compose plugin (`docker compose`)
- [Task](https://taskfile.dev/installation/)
- `jq` and `lsof`, used by the health checks (`brew install jq lsof` on macOS)

### Installing Task

#### macOS

```bash
brew install go-task
```

#### Linux

```bash
# Ubuntu / Debian and other distributions with Snap
sudo snap install task --classic

# Fedora
sudo dnf install go-task

# Arch Linux
sudo pacman -S go-task

# Any distribution: install script (binary in ~/.local/bin, which must be in your PATH)
sh -c "$(curl --location https://taskfile.dev/install.sh)" -- -d -b ~/.local/bin
```

On Fedora and Arch, the binary may be named `go-task`: add `alias task=go-task` to your shell
configuration.

#### Windows

```powershell
winget install Task.Task
# or
scoop install task
# or
choco install go-task
```

#### Any OS (with Node.js)

```bash
npm install -g @go-task/cli
```

Check the installation with `task --version`. Other methods are listed in the
[official documentation](https://taskfile.dev/docs/installation).

## Getting started

```bash
git clone <repository-url> my-project
cd my-project
task init
```

`task init` asks for the project name, slug and host ports (press Enter to keep the
defaults), writes them in `.env`, then builds and starts the containers, installs the
dependencies, creates the database, runs the migrations and checks the project health.
It can be run again at any time.

- Application: <http://localhost:8000>
- AdminNeo (database UI): <http://localhost:8089> (server `db`, credentials from `.env`)
- Mailpit (emails sent by the application): <http://localhost:8025>

Run `task` to list all the available tasks.

### Linux

Docker Desktop (macOS, Windows) maps the file ownership of the mounted project. On Linux,
the `app` container must use your user ids to write in the project (`vendor/`, `var/`...).
`task init` saves them in `.env.local`; to set them by hand:

```bash
echo "HOST_UID=$(id -u)" >> .env.local
echo "HOST_GID=$(id -g)" >> .env.local
task docker:build
```

## Configuration

`.env` holds the project defaults and is committed. Override values in `.env.local`
(git-ignored) and keep real secrets out of the repository.

| Variable                          | Default                            | Description                                            |
| --------------------------------- | ---------------------------------- | ------------------------------------------------------ |
| `PROJECT_NAME`                    | `MyProject`                        | Project name, also used in the PHP license headers     |
| `PROJECT_SLUG`                    | `project`                          | Prefix of the container, network and volume names      |
| `APP_PORT`                        | `8000`                             | Application (nginx) host port                          |
| `DB_PORT`                         | `3306`                             | MySQL host port                                        |
| `ADM_PORT`                        | `8089`                             | AdminNeo host port                                     |
| `MAIL_PORT`                       | `8025`                             | Mailpit web UI host port                               |
| `PHP_VERSION`                     | `8.4`                              | PHP image version                                      |
| `MYSQL_VERSION`                   | `9.7`                              | MySQL image version                                    |
| `NGINX_VERSION`                   | `1.30`                             | Nginx image version                                    |
| `DB_ROOT_PASS`                    | `admin`                            | MySQL root password                                    |
| `DB_NAME` / `DB_USER` / `DB_PASS` | `project` / `project` / `ChangeMe` | Application database, created on first start           |
| `DATABASE_URL`                    | built from `DB_*`                  | Doctrine connection, credentials defined once          |
| `MAILER_DSN`                      | `smtp://mailpit:1025`              | Every email goes to Mailpit in development             |
| `COMPOSE_FILE`                    | base + dev files                   | Compose files loaded by `docker compose` and the tasks |
| `HOST_UID` / `HOST_GID`           | `82` (www-data)                    | Linux only, in `.env.local`: ids of the `app` user     |

### Docker Compose files

- `docker-compose.yaml`: services needed in every environment (`app`, `engine`)
- `docker-compose.dev.yaml`: development services (`db`, `adm`, `mailpit`), the `app` → `db`
  dependency and the host user ids
- `docker-compose.ci.yaml`: CI only, stores the image layers in the GitHub Actions cache

`COMPOSE_FILE` in `.env` loads the base and dev files, for the tasks and for plain
`docker compose` commands alike.

## Project structure

```bash
.docker/
├── nginx/default.conf   # Nginx virtual host
├── php/php.ini          # PHP settings (OPcache, realpath cache, timezone, limits)
├── install-tools.sh     # System packages, PHP extensions and QA tools of the image
└── php.Dockerfile       # PHP-FPM image
.github/                 # CI workflow and Dependabot
.tools/
├── cache/               # Caches of the QA tools (git-ignored)
├── config/              # Configuration of every QA tool
├── scripts/             # Help, console output, init, pre-commit and production build scripts
└── tasks/               # Task domains included by Taskfile.yml
assets/                  # JS, CSS (Tailwind) and locked icons, served by AssetMapper
config/                  # Symfony configuration
src/                     # Application code
templates/               # Twig templates and components
tests/                   # Unit/ and Functional/ test suites
AGENTS.md                # Conventions for AI coding agents
```

After changing `php.ini`, `php.Dockerfile` or `install-tools.sh`, rebuild the image with
`task docker:build`.

## Tasks

`Taskfile.yml` wraps every command, run in the `app` container. Run `task` for the full list.
Arguments after `--` are passed to the underlying command, e.g.
`task symfony:console -- make:entity`. Tasks marked `fix=1` only check by default and apply
the fixes with `fix=1`, e.g. `task cs:all fix=1`.

| Domain     | Tasks                                                                                                |
| ---------- | ---------------------------------------------------------------------------------------------------- |
| Shortcuts  | `init`, `up`, `down`, `restart`, `cc`, `test`, `health`, `qa`                                        |
| `docker:`  | `build`, `up`, `down`, `restart`, `reset`, `ps`, `logs`, `shell`                                     |
| `symfony:` | `install`, `composer`, `pnpm`, `console`, `cc`, `tailwind`, `watch`, `icons`, `build-prod`           |
| `db:`      | `create`, `diff`, `migrate`, `reset`                                                                 |
| `test:`    | `all`, `unit`, `functional`, `coverage`                                                              |
| `cs:`      | `all`, `php`, `twig`, `front` (`fix=1`)                                                              |
| `lint:`    | `all`, `yaml`, `shell`, `docker`, `secrets`                                                          |
| `quality:` | `all`, `phpstan`, `rector` (`fix=1`), `audit`, `grumphp`, `hooks`                                    |
| `check:`   | `health`, `tools`, `docker`, `env`, `directories`, `ports`, `containers`, `dependencies`, `database` |

## Code quality

| Tool                                  | Task              | Checks                                                   |
| ------------------------------------- | ----------------- | -------------------------------------------------------- |
| PHP CS Fixer                          | `cs:php`          | PHP coding standards, MIT license header                 |
| Twig CS Fixer, `lint:twig`            | `cs:twig`         | Twig coding standards and syntax                         |
| Biome                                 | `cs:front`        | JS and CSS                                               |
| yamllint, shellcheck, shfmt, hadolint | `lint:*`          | YAML, shell scripts, Dockerfile                          |
| markdownlint                          | `lint:markdown`   | Markdown files                                           |
| gitleaks                              | `lint:secrets`    | Secrets in the git history                               |
| PHPStan (level 6)                     | `quality:phpstan` | Static analysis, with the Symfony and PHPUnit extensions |
| Rector                                | `quality:rector`  | PHP, Symfony, Twig, Doctrine and PHPUnit upgrades        |
| `composer audit`, `pnpm audit`        | `quality:audit`   | Known vulnerabilities in the dependencies                |
| PHPUnit                               | `test:*`          | Unit and functional tests                                |

`task qa` runs everything (cs, lint, quality, tests) before committing. `task qa fix=1`
applies the coding standards fixes but never the Rector refactorings, which stay a
deliberate step (`task quality:rector fix=1`, then `task cs:all fix=1`).

### Git hooks

GrumPHP installs the git hooks on `composer install`. They run in the `app` container,
so **the containers must be running to commit**.

- `pre-commit`, on the staged files: composer, debug calls (`dd(`, `dump(`...), PHP CS Fixer,
  Twig CS Fixer, PHPStan, gitleaks, Biome and yamllint
- `commit-msg`: [Conventional Commits](https://www.conventionalcommits.org/)

## Continuous integration

`.github/workflows/ci.yml` runs on pull requests and on pushes to `main` and `develop`.
GitHub-hosted runners have Docker, so the CI runs the same tasks as locally.

- **QA**: `task up`, `task symfony:install` and `task qa`. On pull requests to `main` and
  pushes on `main`, it also checks the production build (`task symfony:build-prod`).
  The image layers are cached between runs.
- **Conventional commits** (pull requests): commitlint checks every commit, with the same
  rules as the local hook (`.tools/config/commitlint.config.cjs`).

Dependabot opens weekly pull requests for the GitHub Actions, Composer and pnpm
dependencies, with minor and patch updates grouped by ecosystem.

## Deployment

The template does not impose a hosting: the steps below apply to a server with Docker as
well as to a plain PHP server. Check them locally with `task symfony:build-prod`, which runs
the build in a temporary copy without touching your working copy.

1. Set the environment variables on the server (they take precedence over `.env`):

   ```bash
   APP_ENV=prod
   APP_SECRET=<random 32-character string>
   DATABASE_URL="mysql://user:password@host:3306/name?serverVersion=9.7&charset=utf8mb4"
   MAILER_DSN=<smtp://user:password@host:port>
   # With Docker Compose only: start the base services, without the dev ones
   COMPOSE_FILE=docker-compose.yaml
   ```

2. Build the application:

   ```bash
   composer install --no-dev --optimize-autoloader --classmap-authoritative
   php bin/console tailwind:build --minify
   php bin/console asset-map:compile
   php bin/console cache:clear
   php bin/console doctrine:migrations:migrate --no-interaction
   ```

3. Tune PHP for production (see `.docker/php/php.ini`):

   ```ini
   opcache.validate_timestamps = 0
   opcache.preload = /path/to/project/config/preload.php
   opcache.preload_user = www-data
   ```

   With `validate_timestamps = 0`, restart PHP-FPM after each deployment.

The web server serves `public/`, where `asset-map:compile` writes the versioned assets
(`public/assets/`).

## Upgrading MySQL

The database lives in the `<slug>-db-data` volume, which `task down` keeps.
MySQL can only upgrade an existing data directory from one LTS to the next
(8.0 → 8.4 → 9.7). Skipping a step makes the `db` container crash on startup and
Compose reports `container <slug>-db is unhealthy`.

- **No data to keep:** reset the volume with `task docker:reset`, then `task up`.

- **Data to keep:** dump the database with the old version, reset the volume, then import
  the dump with the new version.

  ```bash
  docker compose exec db sh -c 'mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --databases "$MYSQL_DATABASE"' > dump.sql
  # change MYSQL_VERSION in .env
  task docker:reset && task up
  docker compose exec -T db sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD"' < dump.sql
  ```

## Contributing

Commit messages are written in English and follow
[Conventional Commits](https://www.conventionalcommits.org/): the `commit-msg` hook and the
CI check them. See `AGENTS.md` for the code conventions.

## License

MIT, see [LICENSE](LICENSE).
