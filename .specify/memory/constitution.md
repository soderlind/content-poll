# Constitution: Spec‑Driven Development for WordPress Projects

> Non‑negotiable principles that every specification, plan, task, and implementation **MUST** follow. Words **MUST/SHOULD/MAY** are to be interpreted per RFC 2119 semantics.

---

## 1) Purpose & Scope

- This constitution governs WordPress plugins, themes, blocks, and headless integrations developed in this repository.
- It constrains the `/speckit.specify`, `/speckit.plan`, `/speckit.tasks`, and `/speckit.implement` outputs. When in doubt, **follow this constitution** over any generated content.
- Out of scope: WordPress Core contributions (different standards) unless explicitly noted.

## 2) Product Guardrails (Non‑Negotiables)

1. **Security first**: No unauthenticated or un‑authorized write operations. Sanitize on input, validate, and **escape on output**. All state‑changing actions **MUST** validate a nonce **and** capability.
2. **Performance**: Enqueue assets only where needed; avoid global front‑end bloat. Database calls are prepared/parameterized and O(n) per request is avoided.
3. **Accessibility**: Deliver WCAG 2.1 AA. All interactive UI is keyboard‑navigable, labeled, and announces dynamic changes.
4. **i18n & l10n**: All user‑facing strings are translatable; block and script translations are wired.
5. **Testability**: Every feature ships with unit tests and at least one integration/e2e path for the happy‑flow.
6. **Backward compatibility**: Respect semantic versioning and stable public APIs of the plugin/theme. Provide migrations and clean uninstall.
7. **Observability**: Fail loud in logs, fail soft in UX. No fatal errors—guard external calls, feature‑flag risky paths.

## 3) Tech Baselines

- **WordPress**: Target latest stable; **minimum supported**: 6.6.
- **PHP**: 8.1+ (strict types where practical, `declare(strict_types=1)` in project PHP entry points).
- **Node**: 20+; **Package manager**: npm or pnpm (pick one per repo).
- **Build**: `@wordpress/scripts` for blocks, webpack/Vite only if justified.
- **Datastores**: WordPress options/table schema, or remote APIs—no ad‑hoc SQL without model/migration.

## 4) Architecture & Patterns

- Prefer **plugin‑first** architecture for features; themes contain presentation only. New UI pieces **SHOULD** be Gutenberg blocks (or block‑powered UIs) with a `block.json` manifest.
- Follow **capability‑based access** checks. Never trust `is_admin()` for authorization.
- REST routes live under `/wp-json/<namespace>/<version>/*` with an explicit `permission_callback`.
- Encapsulate DB access in repositories/services. Never mix rendering with data access.
- Use dependency injection for testability; avoid globals except WordPress‑provided ones.

## 5) Coding Standards & Static Analysis

- **PHP**: Enforce **WordPress Coding Standards (WPCS)** via PHPCS. Project ships `phpcs.xml` with `WordPress-Core`, `WordPress-Extra`, `WordPress-Docs` (and vendor‑specific extensions as needed). Use Composer scripts to run.
- **JS/TS**: Use `@wordpress/eslint-plugin` recommended config + TypeScript where practical. Use Prettier for formatting (no stylistic overrides unless justified).
- **CSS/Sass**: Naming is BEM‑ish or CSS Modules. Use logical properties and prefers‑reduced‑motion fallbacks.
- **Docs**: PHPDoc/JSDoc required for public APIs/filters/actions. Each hook documents parameters and return types.

## 6) Security Rules (Must‑Haves)

- **Capabilities**: Validate with `current_user_can( 'cap' )` (or mapped caps) for admin/REST mutations.
- **Nonces**: Required for all state changes in wp‑admin and front‑end forms/AJAX.
- **Sanitization**: `sanitize_text_field`, `sanitize_email`, `absint`, `wp_kses( …, allowed_html )`, etc., on input.
- **Escaping**: `esc_html`, `esc_attr`, `esc_url`, `wp_kses_post` on output. Escaping happens as late as possible.
- **DB**: Use `$wpdb->prepare()` for raw SQL, otherwise core APIs. No string interpolation in queries.
- **REST**: Always set `permission_callback`. Never `__return_true` for write routes. Validate/sanitize `args`.
- **Files**: No `eval`, no arbitrary file writes/reads outside uploads. Respect uploads MIME validation.

## 7) Data, Schema, and Migrations

- Custom tables require a spec and a migration plan using `dbDelta` or a dedicated migration runner. Version schema via `option` key.
- `autoload` options kept minimal; large data **MUST NOT** be autoloaded.
- **Uninstall**: Provide `uninstall.php` to remove options/custom tables/transients when safe.

## 8) API Contracts

- REST responses are versioned, typed (documented shapes), and stable. Breaking changes require a new version path.
- Use non‑ambiguous HTTP statuses; errors return `WP_Error` with a code and `status`.
- For headless/SSR, document CORS and authentication flows (cookies, Application Passwords, OAuth, or JWT).

## 9) Blocks & Editor Integrations

- Each block defines `block.json`, uses `registerBlockType`, has `edit` & `save` (or `render_callback` for dynamic blocks), and a stable `name`.
- Scripts/styles are enqueued via `block.json` or `wp_enqueue_*` with dependency extraction. No global bundles on every page.
- Reusable components **SHOULD** use `@wordpress/components` and `@wordpress/data` selectors.
- Provide server‑side rendering only when necessary; prefer static markup with hydration for performance.

## 10) Internationalization & Localization

- Wrap **all user‑visible strings** in i18n functions (`__`, `_x`, `_n`, etc.). Provide a consistent text domain.
- Load text domain, generate POT/PO/MO files, and call `wp_set_script_translations` for block scripts.
- Follow i18n writing guidelines (whole sentences, placeholders, no stray whitespace).

## 11) Accessibility Requirements

- Keyboard access, focus management, and visible focus states are mandatory.
- Use semantic HTML first; ARIA only to enhance, not replace semantics.
- Announce live region updates with `@wordpress/a11y` when appropriate.
- Color contrast meets **AA**.

## 12) Performance Principles

- **Enqueue scope**: Load assets only on screens that need them. Split bundles by screen/block.
- Avoid admin‑ajax for APIs—prefer REST. Cache expensive operations (transients or persistent object cache).
- Images and icons: prefer SVG (sanitized) or sprite sheets. Inline critical CSS for above‑the‑fold when necessary.

## 13) Testing Strategy

- **Unit/Integration**: PHPUnit with the WordPress test suite. Mock external services.
- **E2E**: Playwright (or `@wordpress/e2e-tests` if justified) against a disposable environment.
- **JS**: Jest + React Testing Library for block/editor logic.
- CI must run `phpcs`, `eslint`, tests, and build verification on each PR.

## 14) Build, Packaging & Releases

- Source in `/src`; built assets in `/build` (ignored in VCS unless publishing to wp.org).
- Readme complies with the Plugin Directory spec (if applicable). SemVer for releases.
- Tag Git, update changelog, bump versions in plugin headers and `block.json` as needed.

## 15) Observability & Error Handling

- Do not throw fatal errors in production. Convert exceptions to `WP_Error` or admin notices as appropriate.
- Instrument key flows with `error_log` (dev only) or configured logger; never leak secrets.

## 16) Tooling & Automation

- Composer and npm scripts:
  - `composer phpcs`, `composer phpcbf`, `composer test`
  - `npm run lint:js`, `npm run build`, `npm run test`
- Pre‑commit hooks run linters and tests.

## 17) Governance & Change Control

- This file **MUST** be kept in `.specify/memory/constitution.md` and versioned.
- Changes require PR + approval by tech lead and QA. Breaking policy changes get a minor version bump in the constitution header.
- Specs/plans/tasks that contradict this document must be rewritten before implementation.

## 18) Working with AI Assistants (Copilot/Agents)

- When generating code, the assistant **MUST**:
  - Adhere to WPCS/ESLint rules and fix lint errors before suggesting diffs.
  - Use proper WordPress nonces, capabilities, escaping/sanitization, and REST `permission_callback`.
  - Prefer block‑based UIs with `block.json`; no enqueueing global assets unnecessarily.
  - Add i18n wrappers and text domain.
  - Propose tests (PHPUnit/Jest/Playwright) with the feature.
- The assistant **MUST NOT**:
  - Create routes/actions without auth checks.
  - Introduce heavy dependencies when core APIs suffice.
  - Bypass build tooling defined above.

## 19) Pull Request Checklist (Enforced)

- [ ] Security: capabilities, nonces, sanitization, escaping
- [ ] Performance: scoped enqueues, no needless queries
- [ ] Accessibility: keyboard, labels, contrast, announcements
- [ ] i18n: strings wrapped, domain set, POT updated
- [ ] Tests: unit + integration/e2e where applicable
- [ ] Linting: PHPCS (WPCS) & ESLint/Prettier pass in CI
- [ ] Docs: hooks documented, public APIs explained

---

### Appendix A — Canonical Project Scripts (example)

```json
{
  "scripts": {
    "build": "wp-scripts build",
    "start": "wp-scripts start",
    "lint:js": "eslint --ext .js,.jsx,.ts,.tsx src",
    "test": "jest",
    "e2e": "playwright test"
  }
}
```

### Appendix B — PHPCS Ruleset (excerpt)

```xml
<?xml version="1.0"?>
<ruleset name="Project WPCS">
  <description>WordPress Coding Standards for this project.</description>
  <rule ref="WordPress-Core"/>
  <rule ref="WordPress-Extra"/>
  <rule ref="WordPress-Docs"/>
  <config name="testVersion" value="8.1-"/>
</ruleset>
```

---

**Status**: v1.0 of this constitution. Update via PRs only.
