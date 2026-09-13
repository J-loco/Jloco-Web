#!/bin/sh
# Builds public/assets/app.css from assets/css/app.css with the Tailwind standalone CLI.
#   build-assets          minified build
#   build-assets watch    rebuild on change (file events may not cross Windows bind mounts)
set -eu
cd "${ASSETS_ROOT:-/app}"
if [ "${1:-}" = "watch" ]; then
    exec tailwindcss --input assets/css/app.css --output public/assets/app.css --watch=always
fi
exec tailwindcss --input assets/css/app.css --output public/assets/app.css --minify
