=== ContentPoll AI ===
Contributors: PerS
Tags: voting, polls, gutenberg, block, survey
Requires at least: 6.8
Tested up to: 6.8
Stable tag: 0.6.4
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

= AI-Powered Suggestions (6 Options!) =

AI reads your page content and generates contextually relevant questions and options:

* **Heuristic** (Default, Free): Extracts keywords from your content, no API needed
* **OpenAI**: GPT models analyze your content for smart question suggestions
* **Anthropic Claude**: Claude 3.5 understands content context deeply
* **Google Gemini**: Gemini 1.5 Flash (free tier available!)
* **Azure OpenAI**: Enterprise Azure OpenAI Service
* **Ollama**: Self-hosted local models (process content privately)

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

= 0.6.4 - 2025-11-14 =
* Added: Very conservative dry-run orphan detector in analytics service to help inspect block_ids that no longer appear in post/page content, without deleting data.
* Internal: No automatic deletions; orphan handling remains opt-in via explicit actions only.
* Notes: Safety-focused patch; no schema or REST API changes.

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

= 0.6.3 =
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

