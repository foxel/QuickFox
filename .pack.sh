#!/bin/bash

PACK_FILE="pack.tgz";

rm -f "$PACK_FILE";

git archive --format=tar --worktree-attributes HEAD | gzip -c > "$PACK_FILE"


