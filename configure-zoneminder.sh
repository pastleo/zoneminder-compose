#!/bin/sh

set -eu

until MYSQL_PWD="$MYSQL_PASSWORD" mariadb \
  --host="${MYSQL_HOST:-db}" \
  --user="$MYSQL_USER" \
  zm \
  --execute="UPDATE Config SET Value = 'modern' WHERE Name = 'ZM_SKIN_DEFAULT'" \
  2>/dev/null; do
  sleep 2
done
