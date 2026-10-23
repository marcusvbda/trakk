#!/usr/bin/env bash
# Formats the file Claude just edited. Silent and non-blocking when tools are not installed yet.

file=$(node -e 'let d="";process.stdin.on("data",c=>d+=c).on("end",()=>{try{process.stdout.write(JSON.parse(d).tool_input?.file_path??"")}catch{}})')
cd "${CLAUDE_PROJECT_DIR:-.}" || exit 0
[ -f "$file" ] || exit 0

case "$file" in
  *.php)
    [ -x vendor/bin/pint ] && vendor/bin/pint --quiet "$file"
    ;;
  *.ts|*.tsx|*.css)
    [ -x node_modules/.bin/prettier ] && node_modules/.bin/prettier --write --log-level silent "$file"
    ;;
esac

exit 0
