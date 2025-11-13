=== ContentPoll AI ===
Contributors: PerS
Tags: voting, polls, gutenberg, block, survey
Requires at least: 6.8
Tested up to: 6.8
Stable tag: 0.5.0
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

= Automatic Installation =

1. Go to Plugins → Add New
2. Search for "ContentPoll AI" (formerly Content Vote)
3. Click Install Now
4. Click Activate

= Manual Installation =

1. Download the plugin ZIP file
2. Go to Plugins → Add New → Upload Plugin
3. Choose the ZIP file and click Install Now
4. Activate the plugin

= Configuration (Optional) =

1. Go to Settings → Content Vote
2. Choose an AI provider for suggestions
3. Enter your API key or endpoint details
4. Click Save Settings (plugin tests API connection automatically)

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
.content-vote__option {
    border-radius: 12px; /* Rounder cards */
}

.content-vote__result-fill {
    background: linear-gradient(90deg, #667eea, #764ba2); /* Custom gradient */
}
`

= Can I export vote data? =

Vote data is stored in a custom database table (`wp_vote_block_submissions`). Export functionality is planned for a future release. For now, you can query the database directly if needed.

= Does it slow down my site? =

No. The block is lightweight (~7KB JavaScript gzipped) and results are loaded asynchronously. No impact on page load times.

== Screenshots ==

1. Modern card-style voting interface with A-D labels and hover effects
2. Real-time results with progress bars and vote counts
3. Block editor with AI suggestion button
4. Settings page showing 6 AI provider options
5. Theme-adaptive design in light and dark modes
6. Mobile responsive design

== Changelog ==

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

* Documentation: https://github.com/yourusername/content-vote
* Issues: https://github.com/yourusername/content-vote/issues
* Support Forum: https://wordpress.org/support/plugin/content-vote/

== Development ==

Content Vote is open source! Contributions welcome.

**Repository**: https://github.com/yourusername/content-vote

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

