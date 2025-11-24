# Changelog

All notable changes to the Content Poll plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


## [0.9.2] - 2025-11-24

### Added
- Environment variable and PHP constant support for all AI settings (provider, API keys, models, endpoints).
- Configuration priority: PHP constant → environment variable → database option → default value.
- Admin settings fields show read-only state with source indicator when externally defined.
- Comprehensive environment variable documentation with examples for wp-config.php, .env, and Docker.

### Notes
- Enables deployment-friendly configuration (Docker, CI/CD, wp-config.php) without database storage.
- All 14 AI settings can be configured via `CONTENT_POLL_*` constants or environment variables.
- See docs/AI-PROVIDERS.md for full configuration reference.

## [0.9.1] - 2025-11-24

### Changed
- AI-generated poll options are now randomized to prevent bias toward the first option.

### Added
- ContentPoll AI Flow Architecture documentation with sequence diagrams and detailed code examples.

## [0.9.0] - 2025-11-19

### Changed
- PocketFlow multi-step AI flow is now always used for OpenAI and Azure OpenAI providers; the separate PocketFlow checkbox and option have been removed from the settings UI.
- OpenAI/Azure suggestions now consistently run through the topic-aware multi-step flow (keywords → poll draft → JSON validation), reusing the existing `LLMClient` and PocketFlow nodes.

### Notes
- Heuristic, Anthropic, Gemini, Ollama, and Grok providers are unchanged.
- Heuristic fallback behavior remains: if any external provider (including PocketFlow/OpenAI) fails or returns an empty result, the built-in heuristic suggestion generator is used.

## [0.8.3] - 2025-11-16

### Changed
- Updated JavaScript toolchain devDependencies (`@wordpress/scripts`, `vitest`) and confirmed all JS tests and builds pass on the newer stack.

### Security
- Reviewed `npm audit` output; remaining vulnerabilities are limited to dev-only tooling (`js-yaml`, `webpack-dev-server` via `@wordpress/scripts`).
- Applied `npm audit fix` (non-force); declined `--force` downgrade of `@wordpress/scripts` to avoid reintroducing older tooling with its own issues.
- Fix code security issues found by gitHub Advanted Security Scan.

### Notes
- Dev tooling and audit-only changes; no runtime/plugin behavior or database schema modifications.
- Safe upgrade; no action required for existing installs.


## [0.8.4] - 2025-11-19

### Added
- Optional `PocketFlow` multi-step mode that uses your existing OpenAI/Azure configuration to run a topic-aware poll generation flow (keywords → poll draft → JSON validation). This is controlled via a checkbox in AI Settings and only affects the OpenAI provider.

### Changed
- Introduced an internal `LLMClient` wrapper and lightweight flow/nodes infrastructure to centralize OpenAI/Azure HTTP calls and make the AI suggestion pipeline more modular.
- Updated AI settings so the AI Provider dropdown selects the backend (Heuristic, OpenAI, Anthropic, Gemini, Ollama, Grok), and PocketFlow is modeled as a separate on/off mode instead of its own provider.

### Notes
- This is an internal AI architecture and UX enhancement; no database or REST API changes.
- Heuristic fallback behavior is unchanged: if PocketFlow or any external provider fails, suggestions fall back to the built-in heuristic generator.


## [0.8.2] - 2025-11-15

### Changed
- Results view now displays per-option percentages instead of raw vote counts while keeping a total votes summary for context.
- README updated with detailed CSS customization guidance for the poll block (class names, variables, and examples).

### Notes
- Minor UX/documentation release; no database or schema changes.
- Safe upgrade; no action required.


## [0.8.1] - 2025-11-15

### Changed
- Consolidated AI suggestion prompt into a single constant template and unified JSON parsing logic inside `AISuggestionService`, removing duplicated provider-specific prompt code.

### Fixed
- Improved robustness of AI suggestion JSON parsing (multi-stage extraction: direct decode, brace span recovery, fragment scanning) reducing malformed suggestion fallbacks.
- REST vote/results tests now isolate data using unique block IDs, preventing residual poll data from affecting subsequent test runs.

### Notes
- Internal maintenance release focused on reliability and maintainability of AI suggestion pipeline and test isolation.
- No database or schema changes.
- Safe upgrade; no action required.

## [0.8.0] - 2025-11-15

### Added
- Grok (xAI) AI provider for content-aware poll suggestions (`grok-2` model by default).
- Validation hook for Grok API credentials (real-time test on settings save).
- Dynamic settings UI label now clarifies xAI API Key when Grok selected.

### Changed
- AI provider list expanded from 6 to 7 providers across README and readme.txt.
- Settings JavaScript updated to include Grok provider key label logic.

### Notes
- Minor feature release introducing new external AI option. Falls back gracefully to heuristic when Grok credentials are absent or API errors occur.
- No database/schema changes.
- Recommended update for users wanting xAI/Grok integration.

### Security
- Maintains existing sanitation/validation patterns; Grok API errors logged and surfaced via transient admin notice (same as other providers).


## [0.7.6] - 2025-11-14

### Fixed
- **Critical**: Tests no longer delete production vote data - added PHPUNIT_TEST constant and database migration protection during test runs.
- DatabaseManager now skips migrations during PHPUnit tests to prevent unintended schema changes and data loss.
- Test bootstrap includes comprehensive database safety checks (test prefix detection, explicit truncation flag).

### Added
- Test runner script (`run-tests.sh`) with safe mode (default) and explicit truncation mode (requires confirmation).
- wp-config.php configuration for test database prefix support (WP_TESTS_DB_PREFIX environment variable).
- Comprehensive testing documentation (TESTING.md, tests/README.md, docs/TEST-DATABASE-SETUP.md).

### Changed
- Test suite now runs against production database without truncation unless explicitly configured with test prefix or confirmation.
- Bootstrap protection prevents accidental data loss: only truncates if prefix is `test_*`, `phpunit_*`, or `TRUNCATE_TEST_DATA=true`.

### Notes
- Patch release addressing critical issue where running tests would delete all production votes.
- Tests are now safe to run during development without risk of data loss.
- Production data remains protected while maintaining full test coverage.

## [0.7.5] - 2025-11-14

### Changed
- **Architecture**: Refactored database management into dedicated `DatabaseManager` class with singleton pattern.
- **Critical**: Removed `register_activation_hook` for database operations to prevent vote data loss during plugin updates.
- Database initialization now runs on `plugins_loaded` hook with version-based migration tracking instead of activation hook.
- Migration logic uses semantic versioning (DB_VERSION) instead of boolean flag for better upgrade path management.

### Fixed
- **Critical**: Eliminated root cause of vote data loss during plugin updates - activation hooks no longer trigger database migrations.
- Database schema changes are now completely decoupled from plugin file changes (version bumps, code edits).

### Notes
- Architectural release addressing fundamental issue with activation hook triggering on file changes.
- Database operations moved from bootstrap file to dedicated service class for better separation of concerns.
- Migration system now tracks database schema version independently of plugin version.
- Existing installations will migrate automatically on first page load after update.

## [0.7.4] - 2025-11-14

### Fixed
- **Critical**: Migration logic now drops old `uniq_block_token` index even when `poll_id` column already exists, preventing partial migration state where both unique constraints coexist and cause vote data loss.

### Notes
- Patch release fixing critical bug where previous migrations could leave database in inconsistent state with conflicting unique constraints.

## [0.7.3] - 2025-11-14

### Fixed
- Enhanced idempotent migration by checking each index individually before creation, preventing partial migration states.

### Notes
- Patch release improving migration robustness with granular index existence checks.

## [0.7.2] - 2025-11-14

### Fixed
- **Critical**: Migration now checks if indexes exist before dropping/adding them, making activation hook idempotent and preventing data loss when plugin file is updated.

### Performance
- Optimized runtime migration check to run only once using option flag (`content_poll_poll_id_migrated`) instead of checking database schema on every page load.

### Notes
- Patch release fixing critical issue where updating the plugin file would trigger activation hook again, causing migration to fail and lose unique constraint on poll_id, resulting in vote reset.

## [0.7.1] - 2025-11-14

### Changed
- Analytics methods now consistently return `poll_id` fields instead of aliasing to `block_id`, aligning with the canonical poll identifier throughout the codebase.
- Admin Settings Analytics tab updated to reference `poll_id` consistently for clarity.
- Orphan detection and deletion now correctly use `poll_id` column for all database operations.

### Fixed
- **Critical**: Fixed database migration bug where old unique constraint wasn't dropped before adding new one, causing migration failures and vote data loss.
- **Critical**: Fixed orphan deletion bug where `delete_block_votes()` was querying `block_id` column instead of `poll_id`, preventing orphan cleanup from working.
- Improved orphan detection precision by matching exact JSON attribute patterns (`"pollId":"..."` and `"blockId":"..."`) instead of broad substring searches.

### Performance
- Optimized orphan detection from N+1 queries to 2 queries total, dramatically improving performance on sites with many polls (e.g., 100 polls: 101 queries → 2 queries, 98% reduction).

### Notes
- Patch release focused on completing the poll_id migration and fixing critical database migration and orphan management bugs introduced in 0.7.0.
- **Important**: If upgrading from 0.7.0 and experiencing missing votes, the database migration may have failed. Drop the `poll_id` column and re-activate the plugin to trigger proper migration.

## [0.7.0] - 2025-11-14

### Added
- Internal `pollId` attribute for each poll block, decoupling vote identity from Gutenberg's internal `blockId`.

### Changed
- Front-end scripts, REST vote/results controllers, and storage/analytics services now treat `pollId` as the canonical poll identifier, with legacy `blockId` supported as a backward-compatible fallback.
- Orphan detection and the admin Analytics UI now operate on poll identifiers and use "Poll ID" terminology for clarity.
- Results view removes per-option percentage labels in the list and instead appends a total votes summary below the chart for a cleaner, less noisy display.

### Notes
- Minor version bump reflects the internal identity refactor, dedicated `poll_id` database column (with automatic backfill from legacy `block_id`), and UI refinements.

## [0.6.4] - 2025-11-14

### Added
- Very conservative dry-run orphan detector in `VoteAnalyticsService` for inspecting block_ids that no longer appear in post/page content, without performing any deletion.

### Changed
- Hardened vote REST controller to require a valid non-zero `postId` and reject votes without post context (prevents new `post_id = 0` legacy rows).
- Front-end vote script now attempts to resolve `postId` from both `data-post-id` and the block editor store (`core/editor`) before submitting.

### Notes
- Safety-focused patch release; no schema changes. Existing legacy `post_id = 0` records are preserved but new ones are prevented.

## [0.6.3] - 2025-11-14

### Changed
- AI-powered suggestion providers (OpenAI, Anthropic, Gemini, Ollama) now infer the language of the post content within a single request and generate poll questions/options in that same language.
- Simplified `AISuggestionService` by removing the internal PHP language heuristic in favor of model-based language detection.

### Notes
- Patch release focused on improving localization behavior for AI suggestions; no database or REST API changes.

## [0.6.2] - 2025-11-13

### Added
- Results-only view: returning visitors (or revisits after voting) see only aggregated results; options are hidden for a cleaner experience.

### Changed
- Front-end script applies `content-poll--results-only` class post-vote and on revisit; CSS hides options/message.
- Debug reset restores option visibility for development/testing when `WP_DEBUG` is enabled.

### Internal
- Prettier formatting adjustments to vote submission script; assets rebuilt.

### Notes
- Patch release focused on UX; no schema or API changes.

## [0.6.1] - 2025-11-13

### Fixed
- Resolved analytics summary transient regression where cached post summary was being prematurely reset/overwritten.

### Added
- Normalization layer for AI suggestions ensuring each suggestion always includes a question and populated options array.

### Internal
- Consolidated caching logic to single build-and-set flow; removed stray transient writes.
- Improved test reliability around suggestion fallback and caching invalidation.

### Notes
- Patch release focused on stability of analytics and AI suggestion robustness.

## [0.6.0] - 2025-11-13

### Added
- Analytics dashboard now uses `WP_List_Table` for "Posts with Polls" (pagination + sorting).
- Screen Option to adjust posts-per-page for analytics list.
- Orphan poll detection (legacy block vote data where block removed) with nonce-protected deletion action.
- Transient caching (5 min) for aggregated post poll summary; invalidation on vote, post save, and orphan deletion.
- PHPUnit tests covering caching invalidation logic (vote + manual invalidate helper).

### Changed
- Replaced manual HTML posts summary table with structured list table for scalability and consistency.
- Enhanced legacy vote fallback logic (post_id=0) to merge seamlessly into per-post analytics without migration.
- Clear separation of storage vs analytics responsibilities; added explicit cache invalidation helper.

### Performance
- Reduced repeated parse/query overhead via short-lived cached summary; invalidated on data mutations.

### Fixed
- Ensured list table renders headers and pagination correctly (column header setup).

### Security
- Orphan vote deletion guarded by per-block nonce; no destructive schema changes required for legacy vote support.

### Notes
- Minor version bump reflects new analytics capabilities and performance improvements.

## [0.5.1] - 2025-11-13

### Added
- Regenerated POT file to reflect latest workflow and branding adjustments.
- Workflow exclusion tweaks to guarantee inclusion of `readme.txt`.

### Changed
- Further refined release zip exclusions (retain uninstall + readme, exclude dev/test tooling).

### Fixed
- None in this release.

### Notes
- Incremental maintenance release preparing ground for analytics/export features.

## [0.5.0] - 2025-11-13

### Added
- GitHub-based automatic update integration via `GitHubPluginUpdater` (plugin-update-checker library).
- Release workflow archive verification step (lists contents before publishing).

### Changed
- Release packaging exclusions aligned with `.gitattributes` for slimmer production zip.
- Unified exclusion patterns between manual and on-release workflows.

### Testing
- Added PHPUnit tests for updater (constructor validation, init hook registration, release assets flag behavior).

### Fixed
- None in this release.

### Security
- No changes; inherits nonce/cookie safeguards from prior versions.

### Notes
- Foundation work for upcoming analytics/export features; updater enables smoother delivery of future enhancements.

## [0.4.0] - 2025-11-13

### Added
- Updated POT files (`content-poll.pot`, `content-vote.pot`) to reflect new/adjusted strings and AI provider labels.
- Changelog alignment for upcoming roadmap items (analytics/export) to prepare for next feature release.

### Changed
- Renamed plugin from "Content Vote" to "ContentPoll AI" (primary public name now unified in headers, settings page, docs).
- Version synchronized across plugin header, stable tag, package metadata, and internationalization resources.
- Documentation wording aligned to new name; legacy references kept only in historical release notes.
- Clarified branding while retaining existing folder slug (`content-poll`) and text domain for backward compatibility.
 - Consolidated AI configuration validation notices to rely solely on WordPress settings error API (removed transient-based admin notice, preventing duplicate warnings).

### Fixed
- Eliminated duplicate AI configuration warning notices by removing transient-based admin notice path.

### Security
- No new security changes; retains enhancements from 0.3.0 (nonce handling, CSP compliance, atomic vote deduplication).

### Notes
- This is an interim maintenance release focused on the branding rename. All existing settings/options continue to work without migration steps. Future feature work (analytics/export) will build on the new name.

## [0.3.0] - 2025-11-13

### Added
- Support for Anthropic Claude AI provider (claude-3-5-sonnet, claude-3-opus, claude-3-sonnet)
- Support for Google Gemini AI provider (gemini-1.5-flash, gemini-1.5-pro) with free tier
- Support for Ollama self-hosted AI models (llama3.2, mistral, etc.)
- Dynamic field visibility in settings: API key and model fields hidden when Heuristic AI selected
- Comprehensive AI provider documentation (see docs/AI-PROVIDERS.md)
- Smart settings UI that adapts based on selected AI provider

### Changed
- Settings page now supports 5 AI providers: Heuristic (default), OpenAI, Azure OpenAI, Anthropic Claude, Google Gemini, Ollama
- API Key field label changes dynamically based on provider (e.g., "Anthropic API Key" for Claude)
- Model field adapts its meaning per provider (deployment name for Azure, model name for others)
- Extended sanitization for new provider settings

## [0.2.0] - 2025-11-12

### Added
- Admin settings page (Settings → Content Vote) for configuring AI suggestion service
- Support for OpenAI and Azure OpenAI for AI-powered vote suggestions
- OpenAI integration with configurable model selection (gpt-3.5-turbo, gpt-4, etc.)
- Azure OpenAI Service integration with deployment and endpoint configuration
- Debug mode features when WP_DEBUG is enabled:
  - Reset button to clear votes for testing
  - Always show all vote options in results (including 0 votes)
  - Console logging for troubleshooting
- Display actual option text in results instead of generic "Option N" labels
- Vote persistence: returning voters see "Thank you for voting!" with results
- Page load check for existing votes to show appropriate state

### Changed
- Improved result display to show meaningful option labels (e.g., "Architectural: 5 votes" instead of "Option 1: 5 votes")
- Enhanced JavaScript to handle associative array responses from PHP (counts and percentages)
- Settings page dynamically shows/hides fields based on AI provider selection
- Heuristic AI remains default (no API calls, no costs) unless explicitly configured

### Fixed
- Nonce verification for REST API endpoints (changed from 'content_vote' to 'wp_rest' action)
- Block rendering on frontend by ensuring VoteBlock class is always instantiated
- CSP (Content Security Policy) compliance by removing inline scripts
- JavaScript error when processing vote results (forEach on object instead of array)
- Script enqueuing timing issues resolved with viewScript in block.json

### Security
- Enhanced nonce verification for WordPress REST API compatibility
- Secure cookie handling with SameSite=Lax attribute
- Atomic vote deduplication with INSERT IGNORE to prevent race conditions
- API keys stored securely in WordPress options

## [0.1.0] - 2025-11-01

### Added
- Initial release with Gutenberg vote block
- Dynamic option count (2–6) with configurable question
- Anonymous deduplicated voting system using hashed tokens
- REST API endpoints for voting, results, nonce, and suggestions
- Heuristic AI suggestion for auto-generating questions and options
- Custom database table for vote storage
- Aggregate results with counts and percentages
- i18n ready with text domain 'content-vote'
- Basic accessibility with ARIA attributes
- Uninstall script to clean up database table

### Features
- Block editor interface for creating vote blocks
- Frontend voting with semantic HTML
- Results hidden until first vote cast
- Vote lock after submission to prevent duplicate votes
- Editor-only AI suggestions based on post content


[0.5.0]: https://github.com/soderlind/content-poll/compare/0.4.0...0.5.0
[0.4.0]: https://github.com/soderlind/content-poll/compare/0.3.0...0.4.0
[0.3.0]: https://github.com/soderlind/content-poll/compare/0.2.0...0.3.0
[0.5.1]: https://github.com/soderlind/content-poll/compare/0.5.0...0.5.1
[0.6.4]: https://github.com/soderlind/content-poll/compare/0.6.3...0.6.4
[0.7.0]: https://github.com/soderlind/content-poll/compare/0.6.4...0.7.0
[0.6.3]: https://github.com/soderlind/content-poll/compare/0.6.2...0.6.3
[0.6.2]: https://github.com/soderlind/content-poll/compare/0.6.1...0.6.2
[0.6.1]: https://github.com/soderlind/content-poll/compare/0.6.0...0.6.1
[0.8.1]: https://github.com/soderlind/content-poll/compare/0.8.0...0.8.1
[0.8.2]: https://github.com/soderlind/content-poll/compare/0.8.1...0.8.2
[0.8.3]: https://github.com/soderlind/content-poll/compare/0.8.2...0.8.3
[0.9.0]: https://github.com/soderlind/content-poll/compare/0.8.3...0.9.0
[0.9.1]: https://github.com/soderlind/content-poll/compare/0.9.0...0.9.1
[0.9.2]: https://github.com/soderlind/content-poll/compare/0.9.1...0.9.2