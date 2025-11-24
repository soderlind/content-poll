=== ContentPoll AI ===
Contributors: PerS
Tags: voting, polls, gutenberg, block, survey
Requires at least: 6.8
Tested up to: 6.8
Stable tag: 0.9.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Ask readers questions about your content. AI suggests relevant questions by analyzing your page. Beautiful card interface.

== Description ==

**Engage readers with questions about the content they're viewing.**

ContentPoll AI lets you add voting questions to any post or page. The plugin's AI reads your content and suggests relevant questions, making it easy to create polls that relate directly to what visitors are reading.

**Example**: Write a blog post about "10 Photography Tips". Add a ContentPoll AI block, click "Generate Suggestions", and AI might suggest: "Which photography technique would you like to learn more about?" with options like "Portrait Lighting", "Landscape Composition", "Photo Editing", "Equipment Selection".

Visitors vote once, then see beautiful progress bars showing how others voted. Perfect for blog posts, product pages, tutorials, reviews, and any content where you want to know what interests your audience most.

| Previous Name: Content Vote |
= Why ContentPoll AI? =

Unlike generic poll plugins, ContentPoll AI is designed for **content-driven voting**. AI analyzes your page to suggest questions that match what readers just consumed, making voting more relevant and increasing engagement.

= Perfect For =

* **Bloggers**: "Which topic should I write about next?" based on current post
* **Tutorial Sites**: "Which technique would you like to learn more about?"
* **Product Pages**: "What feature matters most to you?"
* **News Sites**: "What's your take on this story?"
* **Review Sites**: "Which aspect influenced your decision?"
* **Documentation**: "Which section needs more detail?"

= Modern Interface =

* ✨ **Card-Style Options**: No buttons – sleek, clickable cards with hover effects
* 📋 **Automatic Labeling**: Options automatically labeled A, B, C, D
* 🎨 **Theme Adaptive**: Inherits colors from your WordPress theme
* 📊 **Visual Results**: Progress bars show vote percentages at a glance
* ✓ **Persistent Selection**: Returning voters see their choice marked
* 📱 **Fully Responsive**: Beautiful on desktop, tablet, and mobile

= AI-Powered Suggestions (7 Options!) =

AI reads your page content and generates contextually relevant questions and options:

* **Heuristic** (Default, Free): Extracts keywords from your content, no API needed. Use it for tests and basic suggestions.
* **OpenAI**: GPT models analyze your content for smart question suggestions
* **Anthropic Claude**: Claude 3.5 understands content context deeply
* **Google Gemini**: Gemini 1.5 Flash (free tier available!)
* **Azure OpenAI**: Enterprise Azure OpenAI Service
* **Ollama**: Self-hosted local models (process content privately)
* **Grok (xAI)**: Real‑time reasoning model from xAI; concise, context-aware suggestions

Configure once at Settings → ContentPoll AI. Then in any post, write your content, add the vote block, and click "Generate Suggestions". AI suggests a question relevant to what readers just read.

= Anonymous & Secure =

* One vote per visitor using secure cookie tokens
* SHA-256 hashing prevents duplicate votes
* No IP addresses or personal data collected
* GDPR compliant with automatic data cleanup on uninstall
* Real-time model validation when configuring AI

= Developer Features =

* Clean REST API for integrations
* Modern React-based block with hooks
* CSP compliant (no inline scripts)
* Full accessibility (ARIA, keyboard navigation)
* Comprehensive test coverage (PHPUnit + Vitest)
* Built with @wordpress/scripts

== Installation ==

= Installation =

1. Download [`content-poll.zip`](https://github.com/soderlind/content-poll/releases/latest/download/content-poll.zip)
2. Upload via  `Plugins → Add New → Upload Plugin`
3. Activate via `WordPress Admin → Plugins`

= Configuration (Optional) =

1. Go to Settings → ContentPoll AI
2. Choose an AI provider for suggestions
3. Enter your API key or endpoint details
4. Click Save Settings (plugin tests API connection automatically)

See [AI Provider Integration Guide](https://github.com/soderlind/content-poll/blob/main/docs/AI-PROVIDERS.md) for detailed setup instructions for each AI provider.

== Frequently Asked Questions ==

= Can visitors vote multiple times? =

No. Each visitor gets one vote per voting block, tracked by a secure cookie. When they return, they'll see their previous selection marked with a checkmark.

= How do I use AI suggestions? =

1. **Write your content first** - AI needs content to analyze
2. Go to Settings → Content Vote
3. Select your preferred AI provider (OpenAI, Claude, Gemini, etc.)
4. Enter your API key (get from provider's website)
5. Save settings
6. In your post editor, add a Content Vote block
7. Click "Generate Suggestions"
8. AI reads your post content and suggests a question with options relevant to what you wrote
9. Review and adjust the suggestion before publishing

**Example**: Write a post about "5 Healthy Breakfast Ideas". AI might suggest: "Which breakfast type appeals to you most?" with options like "Quick & Easy", "High Protein", "Plant-Based", "Make-Ahead Meals".

= Which AI provider should I choose? =

* **Heuristic** (Default): Free, no setup, works offline, basic keyword extraction
* **Google Gemini**: Free tier available, fast, good quality
* **OpenAI**: Industry standard, GPT-4 support, requires paid account
* **Anthropic Claude**: Good for nuanced understanding, requires paid account  
* **Ollama**: Completely private, self-hosted, no API costs
* **Azure OpenAI**: Enterprise with SLA, requires Azure subscription
* **Grok (xAI)**: Emerging real-time reasoning; quick, contextual outputs (requires xAI key)

= Does it work with my theme? =

Yes! The plugin uses WordPress CSS custom properties to automatically inherit your theme's colors, fonts, and design system. It adapts to light/dark themes and custom color schemes.

= What data is collected? =

Only anonymous voting data:
* Block identifier (UUID)
* Selected option index (0-5)
* Hashed token (one-way hash, no personal data)

NOT collected: IP addresses, emails, names, user agents, or any personal information.

= Is this GDPR compliant? =

Yes. The plugin collects no personally identifiable information. The uninstall script automatically removes all voting data from your database.

= Can I test before going live? =

Yes! Enable debug mode:
1. Add `define('WP_DEBUG', true);` to wp-config.php
2. A "Reset Vote (Debug)" button appears below the vote block
3. Click to clear your cookie and vote again for testing

= How do I customize the appearance? =

The plugin inherits from your theme automatically, but you can add custom CSS:

`
.content-poll__option {
    border-radius: 12px; /* Rounder cards */
}

.content-poll__result-fill {
    background: linear-gradient(90deg, #667eea, #764ba2); /* Custom gradient */
}
`

= Can I export vote data? =

Vote data is stored in a custom database table (`wp_vote_block_submissions`). Export functionality is planned for a future release. For now, you can query the database directly if needed.

= Does it slow down my site? =

No. The block is lightweight (~7KB JavaScript gzipped) and results are loaded asynchronously. No impact on page load times.

== Changelog ==

= 0.9.1 - 2025-11-24 =
* Changed: AI-generated poll options are now randomized to prevent bias toward the first option
* Added: ContentPoll AI Flow Architecture documentation with sequence diagrams and detailed code examples
* Notes: UX improvement; prevents primacy effect in AI-generated options; safe upgrade

= 0.9.0 - 2025-11-19 =
* Changed: PocketFlow multi-step AI flow is now always used for OpenAI and Azure OpenAI providers; the separate PocketFlow checkbox has been removed from settings.
* Changed: OpenAI/Azure suggestions now always run through the topic-aware multi-step flow (keywords → poll draft → validation) for more structured questions and options.
* Notes: Heuristic, Anthropic, Gemini, Ollama, and Grok providers are unchanged; heuristic fallback still applies when external providers fail.

= 0.8.3 - 2025-11-16 =
* Changed: Updated JavaScript dev tooling (`@wordpress/scripts`, `vitest`) and verified all JS tests and builds pass on the newer stack.
* Security: Reviewed `npm audit` output; remaining issues are limited to dev-only tooling (`js-yaml`, `webpack-dev-server` via `@wordpress/scripts`).
* Security: Applied `npm audit fix` (non-force) and intentionally avoided `--force` downgrade of `@wordpress/scripts` to prevent regressing to older tooling.
* Security: Fix code security issues found by gitHub Advanted Security Scan.
* Notes: Tooling/audit-focused release only; no runtime behavior or database/schema changes. Safe upgrade; no action required.

= 0.8.2 - 2025-11-15 =
* Changed: Results block now shows per-option percentages (with total votes summary) for clearer at-a-glance interpretation
* Changed: README expanded with CSS customization section (class names, variables, and examples) for theming the poll block
* Notes: Minor UX/documentation update; no database changes; safe upgrade

= 0.8.1 - 2025-11-15 =
* Changed: Refactored AISuggestionService (single prompt constant + unified JSON parsing)
* Fixed: More robust AI suggestion JSON parsing (multi-stage recovery) and isolated REST tests (unique block IDs)
* Notes: Internal reliability maintenance; safe upgrade; no schema changes

= 0.8.0 - 2025-11-15 =
* Added: Grok (xAI) AI provider for poll suggestions (model: grok-2 by default)
* Added: Validation and error surfacing for Grok API responses
* Changed: Provider count increased from 6 to 7 (docs & UI updated)
* Notes: Feature release; no schema changes; falls back to heuristic if Grok unavailable


= 0.7.6 - 2025-11-14 =
* Fixed: Critical - Tests no longer delete production vote data during test runs
* Fixed: DatabaseManager skips migrations during PHPUnit tests to prevent data loss
* Added: Safe test runner script with confirmation prompt for database truncation
* Added: wp-config.php support for test database prefix (WP_TESTS_DB_PREFIX)
* Added: Comprehensive testing documentation and safety guides
* Changed: Bootstrap protection prevents truncation unless using test prefix or explicit flag
* Notes: Tests now safe to run during development without risk of losing production votes

= 0.7.5 - 2025-11-14 =
* Changed: Refactored database management into dedicated DatabaseManager class (architectural improvement)
* Fixed: Critical - Removed activation hook for database operations to prevent vote data loss during plugin updates
* Changed: Database initialization now runs on plugins_loaded with version-based migration tracking
* Notes: Addresses root cause of vote resets during updates - activation hooks no longer affect database state

= 0.7.4 - 2025-11-14 =
* Fixed: Critical migration logic bug where old uniq_block_token index wasn't dropped if poll_id column already existed, causing conflicting unique constraints and vote data loss

= 0.7.3 - 2025-11-14 =
* Fixed: Idempotent migration checks now also verify each individual index exists before creation, preventing partial migration failures

= 0.7.2 - 2025-11-14 =
* Fixed: Critical bug where activation hook wasn't idempotent, causing migration failures and vote data loss when plugin file was updated
* Performance: Optimized runtime migration check to run only once using option flag instead of every page load

= 0.7.1 - 2025-11-14 =
* Fixed: Critical database migration bug preventing proper poll_id column addition (dropped old constraint before adding new one)
* Fixed: Critical bug where orphan deletion was querying wrong database column, preventing cleanup
* Fixed: Improved orphan detection precision with exact JSON pattern matching
* Performance: Optimized orphan detection from N+1 queries to 2 total queries (98% reduction for 100 polls)
* Changed: Analytics consistently uses poll_id throughout codebase for clarity
* Changed: Admin Analytics tab references poll_id consistently
* Important: If upgrading from 0.7.0 with missing votes, deactivate and reactivate plugin to trigger proper migration

= 0.7.0 - 2025-11-14 =
* Added: Internal pollId attribute for each poll block, decoupling vote identity from Gutenberg's blockId
* Added: Dedicated poll_id database column with automatic migration and backfill
* Changed: All services now use pollId as canonical identifier with legacy blockId fallback
* Changed: Orphan detection and admin analytics use "Poll ID" terminology
* Changed: Cleaner results display with total votes summary instead of per-option percentages

= 0.6.4 - 2025-11-14 =
* Added: Very conservative dry-run orphan detector in analytics service to help inspect block_ids that no longer appear in post/page content, without deleting data.
* Changed: Vote REST endpoint now requires a valid non-zero postId and rejects votes without post context, preventing new ambiguous `post_id = 0` records.
* Internal: Front-end vote script now resolves postId from both data attributes and the block editor store before submitting.
* Notes: Safety-focused patch; existing legacy `post_id = 0` rows are preserved but no longer created.

= 0.6.3 - 2025-11-14 =
* Changed: AI providers now infer post language from content and generate poll questions/options in the same language in a single request.
* Changed: Simplified AISuggestionService by removing custom PHP language heuristic in favor of model-based detection.
* Notes: Patch release focused on localization behavior of AI suggestions; no schema changes.

= 0.6.2 - 2025-11-13 =
* Added: Results-only view for returning visitors (options hidden; only aggregated results remain).
* Changed: Front-end script and styles add `content-poll--results-only` class; debug reset restores visibility.
* Internal: Minor JS formatting (Prettier) and asset rebuild.
* Notes: Patch UX improvement; no database changes.

= 0.6.1 - 2025-11-13 =
* Fixed: Analytics summary transient regression causing reset/overwrite during summary build.
* Added: AI suggestion normalization guaranteeing question + options array (prevents empty suggestion tests).
* Internal: Consolidated caching logic flow; improved test reliability for suggestion + caching invalidation.
* Notes: Stability patch after major analytics release.


= 0.6.0 - 2025-11-13 =
* Added: Admin Analytics dashboard list now uses `WP_List_Table` with pagination & sortable columns.
* Added: Screen Option to control posts-per-page for poll analytics.
* Added: Orphan poll detection & one-click data deletion (legacy block IDs with removed blocks).
* Added: Transient caching for poll post summary (5‑minute TTL) + automated invalidation on vote/post save.
* Added: PHPUnit tests for caching invalidation (vote + manual helper) with transient stubs.
* Changed: Refactored posts summary rendering to scalable list table instead of manual HTML table.
* Changed: Fallback logic for legacy votes (`post_id = 0`) seamlessly merges into per‑post tallies.
* Performance: Reduced repeated parsing cost via short-lived cached summary; invalidated on change.
* Internal: Clean separation between storage and analytics services; cache helper method added.
* Fixed: Ensured list table headers display (column header setup) & stable pagination.
* Security: Orphan deletion action protected by nonce; no schema migrations needed for legacy vote handling.

= 0.5.1 - 2025-11-13 =
* Added: Regenerated translation template (`content-poll.pot`) after workflow & branding adjustments.
* Added: Ensured `readme.txt` retained in production zip (adjusted workflow exclusions).
* Changed: Refined zip exclusion list for cleaner distribution while keeping `uninstall.php` and essential metadata.
* Internal: Minor housekeeping ahead of analytics/export roadmap.
* Fixed: None.

= 0.5.0 - 2025-11-13 =
* Added: GitHub-based automatic update support via `GitHubPluginUpdater` class (plugin-update-checker integration)
* Added: Archive content verification step in release workflows (lists packaged files pre-publish)
* Changed: Release workflows now align exclusions with `.gitattributes` (lighter production zip)
* Changed: Improved packaging consistency; ensures dev/test/docs are excluded uniformly
* Testing: Added PHPUnit coverage for updater logic (constructor, hook registration, release assets flag)
* Internal: Preparation for future analytics/export feature set

= 0.4.0 - 2025-11-13 =
* Renamed plugin branding from "Content Vote" to "ContentPoll AI"
* Updated language files and documentation wording
* Version synchronization and maintenance groundwork for upcoming analytics/export features
* Changed: Consolidated AI configuration validation notices (removed transient-based admin notice; single standardized settings warning)

= 0.3.0 - 2025-11-12 =
* Added: 3 new AI providers (Anthropic Claude, Google Gemini, Ollama)
* Added: Modern card-style interface (removed buttons)
* Added: Automatic A-D labeling on voting options
* Added: Visual progress bars in results
* Added: Persistent selection - returning voters see their choice checked
* Added: Real-time API validation when saving AI settings
* Added: Theme-adaptive CSS using WordPress custom properties
* Changed: Results now show only the options available (not all 6)
* Changed: Empty message field hidden until there's a message
* Improved: Box-sizing to prevent option overflow
* Improved: Comprehensive error handling with admin notices
* Fixed: Model validation for all AI providers
* Fixed: Duplicate "Settings Saved" messages
* Fixed: Build system to properly compile and load CSS
* Security: Enhanced model existence validation

= 0.2.0 - 2025-11-10 =
* Added: Admin settings page for AI configuration
* Added: OpenAI integration with model selection
* Added: Azure OpenAI Service support
* Added: Debug mode with reset button
* Added: Vote persistence - show results to returning voters
* Changed: Improved nonce verification for REST API
* Fixed: CSP compliance issues
* Fixed: Block rendering on frontend

= 0.1.0 - 2025-11-01 =
* Initial release
* Gutenberg vote block with 2-6 options
* Anonymous deduplicated voting
* REST API endpoints
* Heuristic AI suggestions
* i18n ready

== Upgrade Notice ==

= 0.8.3 =
Tooling and security maintenance release (updated JS dev stack, audit review). No runtime or schema changes; safe upgrade with no manual steps.

= 0.8.2 =
Display now emphasizes per-option percentages instead of raw counts, with total votes preserved. Includes updated CSS customization docs. Safe upgrade; no database changes.

= 0.8.1 =
Internal reliability update (refactored AI suggestion logic + safer tests). Safe upgrade; no database changes.

= 0.8.0 =
Adds Grok (xAI) provider for AI suggestions. Update if you want xAI integration. No database changes; safe upgrade.

= 0.7.6 =
Critical test safety fix: Running tests no longer deletes production vote data. Includes safe test runner and comprehensive database protection. Highly recommended for developers.

= 0.7.5 =
Architectural fix: Database operations moved from activation hook to plugins_loaded. Eliminates vote data loss during plugin updates. Highly recommended upgrade.

= 0.7.4 =
Critical fix for partial migrations leaving conflicting unique constraints. Highly recommended upgrade.

= 0.7.3 =
Enhanced migration reliability with per-index existence checks. Recommended for all users.

= 0.7.2 =
Critical fix: Migration is now idempotent and won't break when plugin file is updated. Recommended upgrade for all users.

= 0.7.1 =
Critical bug fix for database migration that could cause vote data loss. If upgrading from 0.7.0, deactivate and reactivate plugin if votes are missing.

= 0.7.0 =
Introduces dedicated poll_id system for improved reliability. Automatic database migration on upgrade; safe for all installations.

= 0.6.4 =
Improved AI localization: language is inferred from content and polls are generated in that language. Safe upgrade; no manual steps.

= 0.6.2 =
Returning visitors now see a results-only view (options hidden) for cleaner display. Safe upgrade; no manual steps.

= 0.6.1 =
Stability patch: fixes analytics caching regression and ensures consistent AI suggestions. Safe upgrade; no manual steps.

= 0.6.0 =
New analytics list table (pagination, sorting), orphan poll cleanup, and performance caching. Safe upgrade; no manual steps required.

= 0.5.1 =
Refined packaging (readme.txt ensured in release) and refreshed translations. Safe maintenance update; no manual action required.

= 0.5.0 =
Adds GitHub-based updater support and refined release packaging. No action required; existing installs will see future update prompts.

= 0.4.0 =
Rename release: Plugin name changed from "Content Vote" to "ContentPoll AI". No action required; existing blocks and settings continue working.

= 0.3.0 =
Major design update! Beautiful card-style interface with A-D labels and progress bars. Added 3 new AI providers (Claude, Gemini, Ollama). Improved theme integration and user experience.

= 0.2.0 =
New features: OpenAI and Azure OpenAI support, settings page, debug mode, and vote persistence.

= 0.1.0 =
Initial release of Content Vote plugin.

== Privacy Policy ==

This plugin collects anonymous voting data:
* Block identifier (UUID)
* Selected option index (0-5)  
* Hashed voter token (SHA-256, no personal data)

The plugin does NOT collect:
* IP addresses
* Email addresses
* Names or usernames
* Browser information
* Location data
* Any personally identifiable information

**Cookie Usage**: The plugin sets a cookie named `content_vote_token` to prevent duplicate voting. This cookie contains a random token and does not track user behavior across sites.

**AI Provider Privacy**: When using AI suggestions, your post content is sent to the selected AI provider (OpenAI, Anthropic, Google, Azure, or Ollama). Review your chosen provider's privacy policy. The Heuristic option processes content locally without external API calls.

**Data Retention**: Vote data is stored indefinitely in your WordPress database until you uninstall the plugin. The uninstall script automatically removes all data.

== Support ==

Need help? Have feature requests?

* Documentation: https://github.com/soderlind/content-poll
* Issues: https://github.com/soderlind/content-poll/issues


== Development ==

Content Vote is open source! Contributions welcome.

**Repository**: https://github.com/soderlind/content-poll

**Development Setup**:
```
composer install
npm install
npm run start
```

**Testing**:
```
npm test              # All tests + linting
composer test         # PHP unit tests
```

Built with ❤️ using WordPress, modern JavaScript, and thoughtful UX design.

