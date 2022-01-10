# Ding 2 dev

Development setup for <https://github.com/ding2/ding2>.

```ssh
composer install
vendor/bin/drush make --allow-override --working-copy ding2/project-core.make web
vendor/bin/drush make --no-core --allow-override --working-copy --contrib-destination=profiles/ding2 ding2/project.make web
```

```sh
docker run --volume ${PWD}:/app --workdir /app/web/profiles/ding2/themes/ddbasic node:6 npm install
docker run --volume ${PWD}:/app --workdir /app/web/profiles/ding2/themes/ddbasic node:6 node_modules/.bin/gulp uglify sass
```

```sh
symfony local:server:start
```

```sh
cd web
../vendor/bin/drush user:login
```

## pretix

<https://github.com/aakbcms/ding_pretix>

```sh
cd web
git clone https://github.com/aakbcms/ding_pretix sites/all/modules
../vendor/bin/drush pm:enable ding_pretix
```

<https://127.0.0.1:8000/admin/config/ding/pretix>

## Database dump

A dump of a freshly installed Drupal database is located in
[`.docker/drupal/dumps/ding2.sql.gz`](.docker/drupal/dumps/ding2.sql.gz).

To update the dump, run:

```sh
cd web
../vendor/bin/drush sql:dump --structure-tables-list='cache,cache_*,history,search_*,sessions,watchdog' --gzip --result-file=.docker/drupal/dumps/ding2.sql
```
