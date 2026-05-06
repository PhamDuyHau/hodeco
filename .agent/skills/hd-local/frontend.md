# Frontend

## Commands

```bash
pnpm watch          # Dev (HMR)
pnpm build          # Production
pnpm build:theme    # Theme only
pnpm build:plugin   # Plugin only
```

## Styling

- **SCSS**: BEM naming, variables in `_variables.scss`
- **Tailwind 4**: Utilities, layout, rapid prototyping

## JS/Animation

- Entry: `resources/scripts/index.js`
- GSAP + ScrollTrigger for animations
- FX modules for lazy loading (see `optimization.md`)

## Breakpoints

`sm:640` `md:768` `lg:1024` `xl:1280` `2xl:1536`
