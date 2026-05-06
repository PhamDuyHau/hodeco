---
description: Global project rules and constraints for the HD Theme system.
---

# Project Rules & Constraints

## 1. Critical Filesystem Constraints (NEVER BREAK)

**DO NOT modify:**

- `.gitignore`, `node_modules/`, `dist/`, `build/`, `.env`, `vendor/`
- Config files: `vite.config.ts`, `tailwind.config.ts`, `package.json` (without notification)
- Theme/plugin directory structure

## 2. Critical Development Constraints (ALWAYS FOLLOW)

**Before ANY file modification:**

1. Check if `pnpm watch` is running (for asset files)
2. Use `view_file` or `list_dir` to verify paths
3. Read relevant skill file first (see `.agent/README.md` for decision tree)

**After adding PHP classes:**

- Run `composer dump-autoload -o` in theme/plugin directory

## 3. Workflow

```
Read README.md → Identify Task Type → Read Relevant Skill(s) → Implement → Verify Constraints
```
