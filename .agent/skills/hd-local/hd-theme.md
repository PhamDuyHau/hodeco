# HD Theme

## Namespaces

- `HD\*` → `src/` (Core, Admin, Utilities, Plugins)
- `HD\App\*` → `app/` (API, Events, Modules)

## Structure

```
hd/
├── app/          # HD\App namespace
├── src/          # HD namespace
├── resources/    # JS/SCSS/fonts/img
├── parts/        # Template parts
├── templates/    # Page templates
└── assets/       # Vite output
```

## Hooks

- Action: `hd_*_action`
- Filter: `hd_*_filter`
- Functions: `hd_` prefix

## API

- REST: `/wp-json/hd/v1/[endpoint]`
- Classes extend `AbstractAPI`

**Run**: `composer dump-autoload -o` after adding classes.
