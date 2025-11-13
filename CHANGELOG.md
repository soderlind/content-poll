# Changelog

All notable changes to the Content Vote plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).



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

[0.2.0]: https://github.com/yourusername/content-vote/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/yourusername/content-vote/releases/tag/v0.1.0
[0.5.0]: https://github.com/yourusername/content-vote/compare/v0.4.0...v0.5.0
[0.4.0]: https://github.com/yourusername/content-vote/compare/v0.3.0...v0.4.0
[0.3.0]: https://github.com/yourusername/content-vote/compare/v0.2.0...v0.3.0
[0.5.1]: https://github.com/yourusername/content-vote/compare/v0.5.0...v0.5.1
[0.6.1]: https://github.com/yourusername/content-vote/compare/v0.6.0...v0.6.1
