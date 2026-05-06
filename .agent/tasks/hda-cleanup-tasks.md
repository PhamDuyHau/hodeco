# HDA Plugin — Cleanup & Consolidation Tasks

> **Created:** 2026-02-26 **Plugin:** `wp-content/plugins/hda` **Goal:** Fix logic errors,
> consolidate duplicate code, clean up UI inconsistencies, and ensure a professional codebase.

---

## Summary of Issues Found

After auditing the entire plugin codebase, the following categories of problems were identified:

| Category                          | Count | Severity    |
| --------------------------------- | ----- | ----------- |
| **Fatal Bug (wrong namespace)**   | 1     | 🔴 Critical |
| **Duplicate code / 2 approaches** | 5     | 🟠 High     |
| **UI/UX inconsistencies**         | 3     | 🟡 Medium   |
| **i18n / escaping gaps**          | 1     | 🟡 Medium   |
| **Architectural coupling**        | 2     | 🟢 Low      |

---

## Task 1: Fix Wrong Namespace in Waf.php (CRITICAL BUG)

**File:** `src/Security/AccessControl.php` **Status:** ✅ Done

### Problem

Line 11 has `use HDAddons\Firewall\Firewall;` — this namespace **does not exist**. The correct FQCN
is `HDAddons\Security\Firewall\Firewall`.

This will cause a **fatal error** whenever `Waf.php` tries to resolve `Firewall::OPTION_NAME` or
`Firewall::KEY_ENABLED` (lines 53-54), which happens on every frontend request when the Security
module is enabled.

### Fix

```diff
- use HDAddons\Firewall\Firewall;
+ use HDAddons\Security\Firewall\Firewall;
```

### Risk: 🟢 Low — single line change, no side effects.

---

## Task 2: Consolidate Duplicate Block Response (Waf vs ResponseHandler)

**Files:**

- `src/Security/AccessControl.php` → removed `blockAccess()`, delegates to
  `ResponseHandler::blockSimple()`
- `src/Security/Firewall/ResponseHandler.php` → added `blockSimple()` method

**Status:** ✅ Done

### Problem

Two completely different approaches for sending a 403 block response:

| Feature              | `Waf::blockAccess()`                     | `ResponseHandler::block()`               |
| -------------------- | ---------------------------------------- | ---------------------------------------- |
| HTML quality         | Basic, generic                           | Professional, styled, with detailed info |
| Threat context       | Just reason string in `<!-- comment -->` | Full severity, rule ID, IP, timestamp    |
| nocache headers      | ✅                                       | ✅                                       |
| X-HDA-Blocked header | ❌                                       | ✅                                       |
| Reusable             | ❌ (private, instance method)            | ✅ (static, standalone)                  |

### Fix

- Add a simple static method `ResponseHandler::blockSimple(string $reason, string $ip)` for cases
  without a full `ThreatResult`.
- Refactor `Waf::blockAccess()` to delegate to `ResponseHandler::blockSimple()`.
- This preserves the professional block page for ALL block scenarios.

### Risk: 🟡 Medium — changes frontend-facing block behavior for Waf fallback mode.

---

## Task 3: Remove Duplicate `feed_links` / `wp_oembed` Hook Removals

**Files:**

- `src/Security/Security.php` → `disableRssFeed()` (lines 297-298)
- `src/Optimize/Performance.php` → `runCleanup()` (lines 248-249, 264)

**Status:** ⬜ Not Started

### Problem

When both **Security** (RSS disabled) and **Optimize** (Cleanup enabled) are active:

1. `remove_action('wp_head', 'feed_links_extra', 3)` — called by **both** modules
2. `remove_action('wp_head', 'feed_links', 2)` — called by **both** modules
3. `remove_action('wp_head', 'wp_oembed_add_discovery_links')` — called **twice** within
   `Performance.php` itself (line 166 in `removeEmbedActions()` AND line 264 in `runCleanup()`)

While WordPress silently tolerates duplicate `remove_action()`, this is unnecessary overhead and
signals disorganized code.

### Fix

**In `Performance::runCleanup()`:**

- Guard feed_links removal: skip if Security's RSS-off is active.
- Remove the duplicate `wp_oembed_add_discovery_links` call from `runCleanup()` since
  `removeEmbedActions()` already handles it.

### Risk: 🟢 Low — removing redundant `remove_action()` calls.

---

## Task 4: Simplify Version Query Removal Coordination

**Files:**

- `src/Security/Security.php` → `hideVersion()` (lines 313-319)
- `src/Optimize/Performance.php` → `runCleanup()` (lines 274-275)

**Status:** ⬜ Not Started

### Problem

`Security::hideVersion()` reads `Optimize::OPTION_NAME` to check if cleanup is enabled — creating
tight coupling between modules. Meanwhile, `Performance::runCleanup()` adds the same filters at
priority `10` while Security uses `PHP_INT_MAX`.

### Fix

- Let Performance own version removal entirely (it's an optimization concern).
- Remove version query logic from `Security::hideVersion()` entirely (both the filter registration
  and the Optimize option check).
- Security's `hideVersion()` should only handle `the_generator` filter and `update_footer`.

### Risk: 🟢 Low — version removal is idempotent; functionally equivalent.

---

## Task 5: Consolidate DatabaseOptimizer Module Loading

**Files:**

- `src/Optimize/Optimize.php` → `__construct()` (line 58)
- `src/GlobalSetting/SettingsHandlerTrait.php` → `getSettingsAwareModules()` (line 67)
- `src/GlobalSetting/GlobalSetting.php` → `getModuleOptionMap()` (line 91)

**Status:** ⬜ Not Started

### Problem

`DatabaseOptimizer` is:

- ✅ Loaded from `Optimize::__construct()` (as a sub-module)
- ✅ Listed in `SettingsHandlerTrait::getSettingsAwareModules()` (for saving)
- ✅ Listed in `GlobalSetting::getModuleOptionMap()` under `'optimize'`
- ❌ NOT in `config.php` as an independent module
- ❌ NOT in `Plugin::getModuleClassMap()`

This is inconsistent. It's treated as a sub-module of Optimize for loading, but as an independent
SettingsAware module for saving.

### Fix

**Align to sub-module pattern** (same as Firewall → Security relationship):

- Remove `DatabaseOptimizer::class` from `getSettingsAwareModules()`
- Delegate save through `Optimize::sanitizeAndSave()` which calls
  `DatabaseOptimizer::sanitizeAndSave()`
- Verify that DatabaseOptimizer options are embedded in Optimize's options.php form.

### Risk: 🟡 Medium — changes save flow for DatabaseOptimizer settings.

---

## Task 6: Fix UI Inconsistencies — Inline Styles & Unescaped Text

**Files:** All `options.php` template files

**Status:** ⬜ Not Started

### Problem

#### A) Inline styles everywhere

Notices, margins, paddings are hardcoded inline:

```html
<div
	class="notice notice-info inline"
	style="margin:0 0 20px;padding:10px 15px;background:#f0f6fc;"
></div>
```

Each notice box has slightly different inline styles, creating visual inconsistency.

#### B) Unescaped static text

Multiple `<div class="desc">` elements contain raw English text without `esc_html_e()`:

```php
<div class="desc">Removes comment forms, admin menu, toolbar...</div>
```

#### C) Mixed i18n

Some strings use `__()` / `esc_html_e()`, others don't. This breaks translation.

### Fix

1. **CSS:** Create shared SCSS classes for common notice/info patterns in
   `resources/styles/settings/`:
    - `.hda-notice-info` — standardized info box
    - `.hda-notice-warning` — standardized warning box Remove all inline `style=""` attributes from
      option panels.

2. **i18n:** Wrap ALL user-facing strings in `esc_html_e()` or `esc_html__()`. No exceptions.

3. **Consistency:** Ensure all fieldset sections follow the same HTML structure pattern.

### Risk: 🟢 Low — CSS and text changes only, no logic changes.

---

## Task 7: Clean Up Waf.php Role & Relationship with Firewall

**Files:**

- `src/Security/Waf.php`
- `src/Security/Security.php` → `initSecuritySubModules()` (lines 115-130)

**Status:** ⬜ Not Started

### Problem

`Waf.php` has a confusing dual role:

1. **Server config generator** — writes IP/country block rules to htaccess/nginx (always needed)
2. **Runtime blocker** — PHP-level blocking as fallback when Firewall is disabled

The name "Waf" is misleading because the actual WAF is `Firewall.php`. Meanwhile, `Waf.php` is
really an "Access Control" or "IP/Country Blocker" module.

### Fix

1. Rename `Waf.php` → `AccessControl.php` (class `AccessControl`)
2. Update all references in:
    - `Security.php` → `initSecuritySubModules()`
    - `Security.php` → `sanitizeAndSave()`
    - `Security/options.php`
    - `GlobalSetting.php` → `getModuleOptionMap()`
3. Clean up the doc comments to clearly state:
    - Generates server-level rules (primary, always runs on save)
    - PHP fallback blocking (only when Firewall module is OFF)

**Note:** DB option key `waf__options` stays unchanged to preserve existing data.

### Risk: 🟡 Medium — renaming class requires updating multiple references.

---

## Task 8: Audit & Fix Remaining `options.php` Form Structure

**Scope:** All `options.php` template files

**Status:** ✅ Done

### Problem

Across the options panels, there are structural inconsistencies:

- Some sections use `<fieldset class="container-fieldset">`, others use `<div class="section">`
- Some checkboxes use `<div class="option"><div class="controls">...`, others don't
- Heading levels and label structure vary across modules
- Some `desc` divs are inside `option`, some outside
- Container nesting is inconsistent (`container flex flex-x gap sm-up-1 md-up-2` in some places, not
  others)

### Fix

Standardize all options panels to follow a single template pattern:

```html
<fieldset class="container-fieldset">
	<legend class="section-legend">Section Title</legend>
	<div class="hda-notice-info">Info text</div>
	<div class="container flex flex-x gap sm-up-1 md-up-2">
		<div class="cell section section-checkbox">
			<label class="heading" for="field_id">Label</label>
			<div class="option">
				<div class="controls">
					<input type="checkbox" ... />
				</div>
				<div class="explain">Short label</div>
			</div>
			<div class="desc"><?php esc_html_e('Description', HDA_TEXTDOMAIN); ?></div>
		</div>
	</div>
</fieldset>
```

### Risk: 🟢 Low — template changes only. Ensure form field `name` attributes remain unchanged.

---

## Execution Order

Tasks should be executed in this order to avoid dependency conflicts:

| Priority | Task                                             | Risk      | Impact      |
| -------- | ------------------------------------------------ | --------- | ----------- |
| 1        | **Task 1** — Fix wrong namespace (critical bug)  | 🟢 Low    | 🔴 Critical |
| 2        | **Task 2** — Consolidate block response          | 🟡 Medium | 🟠 High     |
| 3        | **Task 3** — Remove duplicate hook removals      | 🟢 Low    | 🟡 Medium   |
| 4        | **Task 4** — Simplify version query coordination | 🟢 Low    | 🟡 Medium   |
| 5        | **Task 5** — Consolidate DatabaseOptimizer       | 🟡 Medium | 🟡 Medium   |
| 6        | **Task 6** — Fix UI/CSS inconsistencies          | 🟢 Low    | 🟡 Medium   |
| 7        | **Task 7** — Rename Waf → AccessControl          | 🟡 Medium | 🟢 Low      |
| 8        | **Task 8** — Standardize options.php templates   | 🟢 Low    | 🟢 Low      |

---

## Files Reference

### Core Architecture

- `hda.php` — Entry point
- `src/Plugin.php` — Module loader
- `config.php` — Module registry
- `src/GlobalSetting/GlobalSetting.php` — Admin menu & settings
- `src/GlobalSetting/SettingsHandlerTrait.php` — Save dispatcher

### Security Cluster (primary concern)

- `src/Security/Security.php` — WP hardening
- `src/Security/Waf.php` — IP/Country blocking
- `src/Security/Firewall/Firewall.php` — WAF pipeline
- `src/Security/Firewall/ResponseHandler.php` — 403 page

### Performance/Optimize

- `src/Optimize/Optimize.php` — Cache providers
- `src/Optimize/Performance.php` — Heartbeat/Embeds/Cleanup
- `src/DatabaseOptimizer/DatabaseOptimizer.php` — DB cleanup
