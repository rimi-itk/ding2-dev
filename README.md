# Ding 2 dev

Development setup for <https://github.com/ding2/ding2>.

```ssh
composer install
symfony php vendor/bin/drush make --allow-override --working-copy ding2/project-core.make web
symfony php vendor/bin/drush make --no-core --allow-override --working-copy --contrib-destination=profiles/ding2 ding2/project.make web
```

```sh
docker run --volume ${PWD}:/app --workdir /app/web/profiles/ding2/themes/ddbasic node:6 npm install
docker run --volume ${PWD}:/app --workdir /app/web/profiles/ding2/themes/ddbasic node:6 node_modules/.bin/gulp uglify sass
```

```sh
symfony local:server:start
```
