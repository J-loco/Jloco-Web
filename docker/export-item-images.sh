#!/bin/sh
# Converts the client item sprites listed in var/item-sprites.tsv ("type<TAB>skin", written by
# bin/item-sprites) to public/assets/img/items/<type>/<skin>.png.
#
#   /clips   StarLoco-Client/resources/app/retroclient/clips/items (read-only)
#   /app     StarLoco-Web
#
# Each SWF's first frame is rendered with JPEXS FFDec, trimmed to the item and capped at 96 px.
# Existing PNGs are kept; pass --force to re-export everything.
set -eu

LIST=/app/var/item-sprites.tsv
IMAGEMAGICK=$(command -v magick || command -v convert)
OUT=/app/public/assets/img/items
FORCE=${1:-}

if [ ! -f "$LIST" ]; then
    echo "Missing $LIST: run 'docker compose run --rm starloco_web_tools item-sprites' first." >&2
    exit 1
fi

STAGE=$(mktemp -d)
RENDER=$(mktemp -d)
trap 'rm -rf "$STAGE" "$RENDER"' EXIT

missing=0
queued=0
while IFS="$(printf '\t')" read -r type skin; do
    [ -n "$type" ] || continue
    skin=$(printf '%s' "$skin" | tr -d '\r')
    target="$OUT/$type/$skin.png"
    if [ -f "$target" ] && [ "$FORCE" != "--force" ]; then
        continue
    fi
    source="/clips/$type/$skin.swf"
    if [ ! -f "$source" ]; then
        echo "  no sprite for type $type skin $skin" >&2
        missing=$((missing + 1))
        continue
    fi
    cp "$source" "$STAGE/${type}_${skin}.swf"
    queued=$((queued + 1))
done < "$LIST"

echo "Rendering $queued sprite(s) ($missing missing in the client data)..."
if [ "$queued" -gt 0 ]; then
    # One JVM for every file: FFDec writes <render>/<file name>/1.png for each input.
    java -Djava.awt.headless=true -jar /opt/ffdec/ffdec.jar -format frame:png -select 1 -zoom 2 -export frame "$RENDER" "$STAGE" > /dev/null

    for frame in "$RENDER"/*/1.png; do
        [ -f "$frame" ] || continue
        name=$(basename "$(dirname "$frame")")
        type=${name%%_*}
        skin=${name#*_}
        skin=${skin%.swf}
        mkdir -p "$OUT/$type"
        "$IMAGEMAGICK" "$frame" -trim +repage -resize '96x96>' -strip "$OUT/$type/$skin.png" 2> /dev/null
        # An empty first frame trims down to a 1x1 pixel: no image is better than a blank one.
        if [ "$("$IMAGEMAGICK" identify -format '%wx%h' "$OUT/$type/$skin.png" 2> /dev/null || echo 1x1)" = "1x1" ]; then
            rm -f "$OUT/$type/$skin.png"
            echo "  empty first frame for type $type skin $skin (skipped)" >&2
        fi
    done
fi

echo "Done: $(find "$OUT" -name '*.png' | wc -l) item image(s) in public/assets/img/items."
