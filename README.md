# symfony-docker-template

Template to start a Symfony project with Docker, bundling the tools and architecture
used across my projects.

## Stack

| Service  | Container        | Image                         | Port (host) |
|----------|------------------|-------------------------------|-------------|
| `app`    | `project-app`    | PHP 8.4 FPM Alpine (custom)   | -           |
| `engine` | `project-server` | `nginx:1.30-alpine`           | 8000        |
| `db`     | `project-db`     | `mysql:9.7` (LTS), dev only   | 3306        |
| `adm`    | `project-adm`    | `adminneoorg/adminneo:latest`, dev only | 8089 |

- Symfony 8.1 (requires PHP >= 8.4)
- The PHP image ships with Composer 2, Symfony CLI, Node/npm, Chromium and lint tools
  (shellcheck, shfmt, yamllint)

## Requirements

- Docker with the Compose plugin (`docker compose`)
- [Task](https://taskfile.dev/installation/) (optional, see [Installing Task](#installing-task))

### Installing Task

**macOS**

```bash
brew install go-task
```

**Linux**

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

**Windows**

```powershell
winget install Task.Task
# or
scoop install task
# or
choco install go-task
```

**Any OS (with Node.js)**

```bash
npm install -g @go-task/cli
```

Check the installation with `task --version`. Other methods are listed in the
[official documentation](https://taskfile.dev/docs/installation).

## Getting started

```bash
git clone <repository-url> my-project
cd my-project
docker compose up -d --build
docker compose exec app composer install
```

Or, with Task: `task init`.

- Application: http://localhost:8000
- AdminNeo (database UI): http://localhost:8089 (server `db`, credentials from `.env`)

## Configuration

Versions and credentials are set in `.env`:

| Variable        | Default     | Description                     |
|-----------------|-------------|---------------------------------|
| `PHP_VERSION`   | `8.4`       | PHP image version               |
| `MYSQL_VERSION` | `9.7`       | MySQL image version             |
| `NGINX_VERSION` | `1.30`      | Nginx image version             |
| `DB_ROOT_PASS`  | `admin`     | MySQL root password             |
| `DB_NAME`       | `project`   | Database created on first start |
| `DB_USER`       | `project`   | Application database user       |
| `DB_PASS`       | `ChangeMe`  | Application database password   |

`.env` only holds defaults. Override them in `.env.local` (git-ignored) and keep real secrets
out of the repository.

`DATABASE_URL` is built from `DB_USER`, `DB_PASS`, `DB_NAME` and `MYSQL_VERSION`, so the
credentials are only defined once.

### Docker Compose files

The services are split in two files:

- `docker-compose.yaml`: services needed in every environment (`app`, `engine`)
- `docker-compose.dev.yaml`: development services (`db`, `adm`) and the `app` → `db` dependency

`.env` sets `COMPOSE_FILE=docker-compose.yaml:docker-compose.dev.yaml`, which `docker compose`
reads automatically: in development, both files are loaded by the tasks and by plain
`docker compose` commands.

In production, the database is usually a managed service. Set these variables in the server
(or CI) environment, they take precedence over `.env`:

```bash
COMPOSE_FILE=docker-compose.yaml
DATABASE_URL="mysql://user:password@host:3306/name?serverVersion=9.7&charset=utf8mb4"
```

## Project structure

```
.docker/
├── nginx/default.conf   # Nginx virtual host
├── php/php.ini          # Custom PHP settings (OPcache, realpath cache, timezone, limits)
└── php.Dockerfile       # PHP-FPM image
config/                  # Symfony configuration
public/                  # Web root
src/                     # Application code
AGENTS.md                # Conventions for AI coding agents
```

After changing `php.ini` or `php.Dockerfile`, rebuild the image:

```bash
docker compose up -d --build app
```

## Tasks

`Taskfile.yml` wraps the usual commands. Run `task` to list them.

| Task                    | Description                                          |
|-------------------------|------------------------------------------------------|
| `task init`             | Build, start and install dependencies                |
| `task up`               | Build and start the containers                       |
| `task down`             | Stop the containers (keeps the database volume)      |
| `task restart`          | Restart the containers                               |
| `task reset`            | Stop the containers and delete the database volume   |
| `task ps`               | List the containers                                  |
| `task logs -- [service]`| Follow logs                                          |
| `task shell`            | Open a shell in the PHP container                    |
| `task install`          | Install Composer dependencies                        |
| `task composer -- <args>` | Run Composer                                       |
| `task console -- <args>`  | Run the Symfony console                            |
| `task cc`               | Clear the Symfony cache                              |

Arguments after `--` are passed to the underlying command, e.g.
`task console -- make:controller`.

## Useful commands

```bash
docker compose exec app bin/console <command>   # Symfony console
docker compose exec app composer <command>      # Composer
docker compose logs -f <service>                # Follow a service's logs
docker compose down                             # Stop containers (keeps the database)
docker compose down -v                          # Stop containers and delete the database volume
```

## Upgrading MySQL

The database lives in the `project-db-data` volume, which `docker compose down` keeps.
MySQL can only upgrade an existing data directory from one LTS to the next
(8.0 → 8.4 → 9.7). Skipping a step makes the `db` container crash on startup and
Compose reports `container project-db is unhealthy`.

- **No data to keep:** reset the volume.

  ```bash
  docker compose down -v
  docker compose up -d --build
  ```

- **Data to keep:** dump the database with the old version, reset the volume, then import
  the dump with the new version.

  ```bash
  docker compose exec db sh -c 'mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --databases "$MYSQL_DATABASE"' > dump.sql
  # change MYSQL_VERSION in .env
  docker compose down -v && docker compose up -d
  docker compose exec -T db sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD"' < dump.sql
  ```

## Contributing

Commit messages are written in English and follow
[Conventional Commits](https://www.conventionalcommits.org/). See `AGENTS.md` for details.

## License

See [LICENSE](LICENSE).
