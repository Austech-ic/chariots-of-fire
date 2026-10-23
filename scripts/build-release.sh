#!/usr/bin/env bash
# Builds an upload-ready zip for shared hosting (includes vendor/ and built
# assets, so the server needs neither Composer nor Node.js).
#
#   scripts/build-release.sh [version] [--update]
#
# Output: release/flaming-chariot-<version>.zip, or with --update
# release/flaming-chariot-<version>-update.zip, which leaves out storage/ so it
# can be extracted over a live site without touching uploads, logs or backups.
set -euo pipefail

cd "$(dirname "$0")/.."
UPDATE=0
ARGS=()
for arg in "$@"; do
    if [ "$arg" = "--update" ]; then UPDATE=1; else ARGS+=("$arg"); fi
done
VERSION="${ARGS[0]:-$(git describe --tags --always --dirty 2>/dev/null || date +%Y%m%d)}"
NAME="flaming-chariot-${VERSION}"
[ "$UPDATE" = 1 ] && NAME="${NAME}-update"
STAGE="release/${NAME}"
ZIP="release/${NAME}.zip"

echo "→ Building frontend assets"
if command -v npm >/dev/null 2>&1; then
    npm ci --silent && npm run build --silent
else
    echo "  npm not found — using committed public/build"
fi

echo "→ Staging committed files"
rm -rf "$STAGE" "$ZIP"
mkdir -p "$STAGE"
git archive --format=tar HEAD | tar -x -C "$STAGE"
cp -R public/build "$STAGE/public/"

echo "→ Installing production PHP dependencies"
(cd "$STAGE" && COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress --quiet)

echo "→ Removing development-only files"
rm -rf "$STAGE"/{tests,node_modules,.github,phpunit.xml,.editorconfig,.styleci.yml,.gitattributes,release}
rm -f "$STAGE/.env" "$STAGE/database/database.sqlite"
# Composer may fall back to git clones when dist downloads fail; never ship VCS data.
find "$STAGE/vendor" -type d \( -name .git -o -name .github \) -prune -exec rm -rf {} +
find "$STAGE/storage" -mindepth 2 -type f ! -name .gitignore -delete 2>/dev/null || true
if [ "$UPDATE" = 1 ]; then
    # The live site's storage/ (uploads, logs, backups, install lock) must stay untouched.
    rm -rf "$STAGE/storage"
fi

echo "→ Creating ${ZIP}"
php -r '
$src = $argv[1]; $zipPath = $argv[2];
$zip = new ZipArchive();
$zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) or exit(1);
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
foreach ($it as $file) {
    $rel = substr($file->getPathname(), strlen($src) + 1);
    $file->isDir() ? $zip->addEmptyDir($rel) : $zip->addFile($file->getPathname(), $rel);
}
$zip->close();
' "$STAGE" "$ZIP"

rm -rf "$STAGE"
echo "✓ ${ZIP} ($(du -h "$ZIP" | cut -f1)) — upload it and follow docs/SHARED_HOSTING.md"
[ "$UPDATE" = 1 ] && echo "  Update package: extract over the existing site (see \"Updating\" in docs/SHARED_HOSTING.md)."
exit 0
