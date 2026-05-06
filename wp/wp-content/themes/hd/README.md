# HD Theme (hd2026) - Technical Overview

## Overview

The **HD Theme (hd2026)** is a highly modern, professional WordPress theme designed with a strict engineering focus. It moves away from "spaghetti code" typical in older themes and adopts a software engineering approach using **OOP (Object-Oriented Programming)**, **Composer**, and **Vite**.

## Key Technical Highlights

### 1. Modern Architecture (PHP 8.3+)

-   **Namespace & Autoloading**: Uses PSR-4 autoloading via Composer (`composer.json`). Classes are namespaced under `HD\`, eliminating global namespace pollution.
-   **Singleton Pattern**: Core classes like `Bootstrap`, `Theme`, and services use a `Singleton` trait, ensuring they are initialized only once.
-   **Bootstrapping**: The `functions.php` file is cleaner than usual; it simply checks PHP versions and delegates control to `HD\Bootstrap` which loads the rest of the app.

### 2. Build System (Vite)

-   **Tooling**: Instead of legacy Webpack/Gulp, it uses **Vite** for lightning-fast HMR (Hot Module Replacement) and optimized production builds.
-   **Configuration**: The `vite.config.ts` handles SCSS, JS, and component compilation. It includes intelligent chunking (splitting `vendor`, `dynamic` modules) to keep bundle sizes small.
-   **Dynamic Loading**: The theme intelligently loads assets based on the template. For example, if you are on a "Contact" page template, it dynamically enqueues `components/templates/page-contact.scss/js` only for that page.

### 3. Folder Structure

The organization is clean and separates concerns logically:

-   **`core/`**: backend logic (Admin, API, Events, Services).
-   **`resources/`**: frontend source files (styles, scripts, components).
-   **`inc/`**: procedural helpers/hooks (legacy support or non-class utilities).
-   **`parts/` & `templates/`**: template parts and page templates.

### 4. Performance & Best Practices

-   **Critical CSS**: The asset loader supports critical CSS injection.
-   **Asset Management**: A custom `Asset` utility class handles versioning and enqueueing, ensuring cache busting (`[name].[hash].js`).
-   **Strict Typing**: The code uses PHP type hinting (e.g., `public function init(): void`), which reduces bugs and improves maintainability.

## Logic Separation

-   `core/Theme.php` manages theme setup.
-   `core/Bootstrap.php` handles file loading and cache.
-   `dynamicTemplateInclude` method in `Theme.php` smartly pairs assets with page templates.

## Conclusion

This is a **high-quality, enterprise-grade theme**. It is set up effectively for scalability and team development.

-   **Pros**: Excellent structure, fast build tools, modular, strict coding standards.
-   **Cons/Notes**: Requires a build step (`pnpm dev/build`) and Composer (`composer install`).
