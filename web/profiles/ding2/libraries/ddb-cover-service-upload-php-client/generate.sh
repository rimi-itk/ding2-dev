#!/usr/bin/env sh

set -eu

java -DapiTests=false -DmodelTests=false -jar /usr/local/opt/swagger-codegen/libexec/swagger-codegen-cli.jar generate -i $1 -l php --config config.json --output "."
rm -rf .swagger-codegen

composer run apply-coding-standards/php-cs-fixer
