#!/usr/bin/env bash
# Build the distributable ZIP of block_coursecare from a commit (repository tool, not packaged).
#
# Usage: tools/package/build.sh [<ref>]   (default ref: HEAD)
# Output: build/block_coursecare-<release>.zip with the root directory coursecare/.
# git archive honours export-ignore in .gitattributes; tools/package/check.php verifies the real content.
set -euo pipefail

ref="${1:-HEAD}"
root="$(git rev-parse --show-toplevel)"
cd "$root"

if [ "$ref" = "HEAD" ] && { ! git diff --quiet HEAD -- || ! git diff --cached --quiet; }; then
    echo "ERROR: tracked files have uncommitted changes; the package must match a commit." >&2
    exit 1
fi

release="$(git show "${ref}:version.php" | sed -n "s/^\$plugin->release = '\(.*\)';$/\1/p")"
if [ -z "$release" ]; then
    echo "ERROR: \$plugin->release not found in version.php at ${ref}." >&2
    exit 1
fi

mkdir -p build
out="build/block_coursecare-${release}.zip"
rm -f "$out"
git archive --format=zip --prefix=coursecare/ -o "$out" "$ref"
echo "$out"
