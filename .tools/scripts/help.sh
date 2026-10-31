#!/usr/bin/env bash
# Pretty help for Taskfile, styled like the Dashboard Makefile.
# Sections = include namespaces (docker:up -> "Docker"), root tasks -> "General".
# Text in parentheses at the end of a desc is shown as options (purple).
set -euo pipefail

PROJECT_NAME="${PROJECT_NAME:-Project}"
APP_ENV="${APP_ENV:-dev}"
TW="${TARGET_MAX_CHAR_NUM:-24}"
DW="${DESC_MAX_CHAR_NUM:-45}"

# ANSI-C quoting ($'...') stores the real escape characters, so colors
# can be passed as printf arguments instead of in the format string.
BOLD=$'\033[1m'
RESET=$'\033[0m'
YELLOW=$'\033[0;33m'
GREEN=$'\033[0;32m'
BLUE=$'\033[0;34m'
CYAN=$'\033[0;36m'
PURPLE=$'\033[0;35m'

printf '\n'
printf '  %s%s%s%s %s(%s%s%s)%s\n' "$BOLD" "$YELLOW" "$PROJECT_NAME" "$RESET" "$BLUE" "$GREEN" "$APP_ENV" "$BLUE" "$RESET"
printf '  %s───────────────────────────────────────────────────────────────────────────────%s\n\n' "$BLUE" "$RESET"
printf '  %sUsage:%s\n' "$BOLD" "$RESET"
printf '    %stask%s %s<task>%s %s[-- args]%s\n\n' "$YELLOW" "$RESET" "$GREEN" "$RESET" "$PURPLE" "$RESET"
printf '  %sAvailable tasks:%s\n' "$BOLD" "$RESET"

task --list --sort none 2>/dev/null | awk \
    -v yellow="$YELLOW" -v cyan="$CYAN" \
    -v purple="$PURPLE" -v reset="$RESET" \
    -v tw="$TW" -v dw="$DW" '
  /^\* / {
    line = substr($0, 3)
    if (!match(line, /^[^ ]+:[ \t]+/)) next
    head = substr(line, 1, RLENGTH); desc = substr(line, RLENGTH + 1)
    sub(/:[ \t]+$/, "", head); name = head
    sub(/[ \t]+\(aliases:.*\)$/, "", desc)
    gsub(/[ \t]+$/, "", desc)

    # Section from namespace
    ns = "General"
    if (index(name, ":") > 0) { ns = substr(name, 1, index(name, ":") - 1); ns = toupper(substr(ns,1,1)) substr(ns,2) }
    if (ns != current) { printf "\n%s  %s%s\n", cyan, ns, reset; current = ns }

    # Trailing "(...)" -> options
    options = ""
    if (match(desc, /\([^()]*\)$/)) {
      options = purple substr(desc, RSTART + 1, RLENGTH - 2) reset
      desc = substr(desc, 1, RSTART - 1); gsub(/[ \t]+$/, "", desc)
    }
    printf "    %s%-" tw "s%s  %-" dw "s  %s\n", yellow, name, reset, desc, options
  }'
printf '\n'
