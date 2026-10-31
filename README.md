# symfony-docker-template

Template to start a Symfony project with Docker, bundling the tools and architecture
used across my projects.

## Stack

| Service  | Container        | Image                         | Port (host) |
|----------|------------------|-------------------------------|-------------|
| `app`    | `project-app`    | PHP 8.4 FPM Alpine (custom)   | -           |
| `engine` | `project-server` | `nginx:1.30-alpine`           | 8000        |
| `db`     | `project-db`     | `mysql:9.7` (LTS)             | 3306        |
| `adm`    | `project-adm`    | `adminneoorg/adminneo:latest` | 8089        |

- Symfony 8.1 (requires PHP >= 8.4)
- The PHP image ships with Composer 2, Symfony CLI, Node/npm, Chromium and lint tools
  (shellcheck, shfmt, yamllint)

## Requirements

- Docker with the Compose plugin (`docker compose`)

## Getting started

```bash
git clone <repository-url> my-project
cd my-project
docker compose up -d --build
docker compose exec app composer install
```

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
| `DB_PASS`       | `Ch@ng3m3!` | Application database password   |

`.env` only holds defaults. Override them in `.env.local` (git-ignored) and keep real secrets
out of the repository.

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
