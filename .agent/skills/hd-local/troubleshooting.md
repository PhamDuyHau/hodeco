# Troubleshooting

## PHP

| Issue            | Fix                             |
| ---------------- | ------------------------------- |
| Class not found  | `composer dump-autoload -o`     |
| Invalid callback | Use `$this->method(...)` syntax |

## Build

| Issue             | Fix                               |
| ----------------- | --------------------------------- |
| HMR not working   | Restart `pnpm watch`, clear cache |
| Missing assets    | Run `pnpm build`                  |
| SCSS import error | Use `@styles/` alias              |

## ACF

| Issue             | Fix                                |
| ----------------- | ---------------------------------- |
| Empty field       | Check field name, pass explicit ID |
| Clone not showing | Re-sync ACF JSON                   |

## FX Module

| Issue       | Fix                         |
| ----------- | --------------------------- |
| Not loading | Check `data-fx-*` attribute |
| JS errors   | Check browser console       |

## Debug

```bash
php -l file.php           # Syntax check
composer dump-autoload -o # Autoloader
tail -f wp-content/debug.log
```
