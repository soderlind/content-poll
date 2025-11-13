Admin Dashboard Vote Analytics Plan

Overview
Create an admin analytics dashboard for the ContentPoll AI plugin that displays vote statistics across posts/pages and provides a dedicated tab separating AI provider settings from analytics. Enhance Posts/Pages list tables with a vote count column linking into filtered analytics views.

Objectives
- Provide per-post and per-block (poll) vote summaries.
- Show global metrics (total votes, average votes per poll, most active poll, recent activity).
- Add tabbed interface: Tab 1 = AI Settings (existing), Tab 2 = Analytics.
- Add admin list table columns for posts/pages: total votes (aggregate of all polls on the post) + link to analytics detail view.
- Ensure all UI strings are translatable (text domain: content-poll).
- Maintain capability gating via manage_options.

Scope
In-Scope:
- PHP-based server-rendered analytics (initial version).
- Block attribute extraction from post content to display poll questions.
- Vote aggregation queries (block-level, post-level, global).
- Navigation tabs within existing Settings page OR separate analytics page (single page with nav tabs recommended).
- Admin columns for posts & pages.
- Optional CSV export stub (placeholder button & non-functional or minimal implementation).
Out-of-Scope (Phase 1):
- JavaScript SPA dashboards.
- Advanced filters (date ranges, taxonomy filters).
- Real-time websockets/live updating.
- Role-based granular permissions beyond manage_options.

Non-Goals
- Do not alter existing vote submission or storage logic.
- Do not introduce external JS frameworks beyond WP admin default.
- Avoid performance-heavy cross-site analytics (focus on current site only).

Architecture
- Service Layer: New VoteAnalyticsService performing grouped queries.
- Reuse VoteStorageService for single block aggregation where possible.
- Helper: Parse blocks via parse_blocks() to map blockId -> question/options.
- Admin UI: Extend SettingsPage with tab handling via ?tab=analytics query parameter or instantiate new AnalyticsPage with nav-tab wrapper.
- Optional REST Endpoints (future): /content-poll/v1/analytics/* with permission_callback enforcing current_user_can('manage_options').

Data Model & Storage
Existing table: wp_vote_block_submissions
Fields: id, block_id, post_id, option_index, hashed_token, created_at
Indexes: uniq_block_token (block_id, hashed_token), idx_block_option (block_id, option_index)
Potential Additional Index (optional): KEY idx_post_block (post_id, block_id) for faster per-post aggregation if required after profiling.

Queries (Initial)
- Per Block Aggregate (existing): SELECT option_index, COUNT(*) FROM table WHERE block_id=%s GROUP BY option_index
- Per Post Aggregate (block totals): SELECT block_id, option_index, COUNT(*) cnt FROM table WHERE post_id=%d GROUP BY block_id, option_index
- Global Top Polls: SELECT block_id, COUNT(*) total FROM table GROUP BY block_id ORDER BY total DESC LIMIT %d
- Recent Activity: SELECT block_id, MAX(created_at) last_vote, COUNT(*) total FROM table GROUP BY block_id ORDER BY last_vote DESC LIMIT %d
- Post Totals Column: SELECT COUNT(*) FROM table WHERE post_id=%d

Services
VoteAnalyticsService (new):
Methods:
- get_post_block_option_counts(int $post_id): returns structure blockId => option_index => count
- get_post_block_totals(int $post_id): returns blockId => totalVotes
- get_global_top_polls(int $limit): returns blockId => totalVotes
- get_recent_activity(int $limit): returns blockId => last_vote, totalVotes
- get_post_total_votes(int $post_id): returns integer
- get_block_question_map(array $blockStructures): returns blockId => question/options (via parse_blocks())

Block Parsing
Use parse_blocks($post->post_content):
- Identify blocks where blockName == 'content-poll/vote-block'
- Extract attrs.blockId, attrs.question, attrs.options
- Build mapping for display; gracefully handle missing or legacy blocks without blockId (generate fallback).

Admin UI
Tabs via standard WP nav-tab markup:
<h2 class="nav-tab-wrapper">
  <a href="?page=content-poll-settings&tab=analytics" class="nav-tab ...">Analytics</a>
  <a href="?page=content-poll-settings&tab=settings" class="nav-tab ...">AI Settings</a>
</h2>
Render conditional sections below.

Analytics Tab Sections
1. Summary Cards:
   - Total Votes (sum of all rows)
   - Average Votes per Poll (totalVotes / numberOfDistinctPolls)
   - Most Active Poll (highest total)
   - Recent Activity (most recent timestamp)
2. Per-Post Table:
   Columns: Post Title, Poll Count, Total Votes, Last Activity, View Details
3. Post Detail View (when post_id query arg present):
   - List each poll: Question, Block ID, Total Votes
   - Option Breakdown table (Option Label | Votes | Percentage)
4. Global Top Polls table (Block ID | Question | Total Votes | Last Vote)
5. CSV Export (button; POST with nonce; returns downloadable CSV of current view) – may stub initially.

Admin Columns
Hooks:
- add_filter('manage_post_posts_columns', add column header)
- add_action('manage_post_posts_custom_column', output value)
Same for pages: manage_page_posts_columns, manage_page_posts_custom_column
Column ID: 'content_poll_votes'
Output: integer total votes + link labeled 'View Analytics'

Security & Permissions
- Capability check: current_user_can('manage_options') before rendering analytics.
- Nonce for CSV export actions.
- Sanitize all incoming query params: absint('post_id'), sanitize_text_field('tab').
- Escape all output using esc_html(), esc_attr(), esc_url().

Performance Considerations
- Lazy load heavy aggregates only when analytics tab active.
- Potential caching using transients for global summary (e.g., cache for 5 minutes) in later phase.
- Consider adding idx_post_block index if slow queries observed (not in initial patch).

Internationalization
Wrap all user-facing strings with __() / _e():
Examples:
- __('Analytics', 'content-poll')
- __('Total Votes', 'content-poll')
- __('Average Votes per Poll', 'content-poll')
- __('Most Active Poll', 'content-poll')
- __('Recent Activity', 'content-poll')
- __('View Details', 'content-poll')
- __('Poll Analytics', 'content-poll')
- __('Global Top Polls', 'content-poll')
- __('No votes recorded yet.', 'content-poll')
- __('Export CSV', 'content-poll')

Implementation Phases
Phase 1 (Core):
- Create VoteAnalyticsService
- Extend SettingsPage with tab UI
- Add summary + per-post table (no pagination initially)
- Add post detail view
- Add admin columns (posts & pages)
Phase 2 (Enhancements):
- Global top polls & recent activity modules
- CSV export stub functional
Phase 3 (Optimizations):
- Add optional index
- Transient caching for summary metrics
- REST endpoints for async loading
Phase 4 (UI Polish):
- Filtering (date range, post type)
- Pagination & search
- Chart visualizations (optional)

Testing Strategy
PHPUnit:
- Service query methods with mocked wpdb
- Edge cases: no votes, single vote, multiple polls per post
- Admin columns: ensure output correct when zero votes
- Tab selection logic renders expected content
Manual / Integration:
- Create multiple posts with poll blocks
- Cast votes to generate data
- Validate counts vs database

Future Enhancements (Post MVP)
- CSV export full implementation
- Role-based access (editor-level read-only analytics)
- Dashboard widgets (wp_dashboard_setup)
- AI performance comparison metrics (time-to-suggestion, provider popularity)
- Multi-site network level aggregation

Open Questions
- Separate menu item vs tab? (Current plan: tabs for simplicity.)
- Need pagination for high volume immediately? (Defer.)
- REST vs direct server render? (Initial: direct render.)

Acceptance Criteria
- Admin with manage_options sees Analytics tab.
- Per-post column displays correct total votes.
- Analytics tab loads without fatal errors on sites with 0 polls.
- Post detail view shows option breakdown matching REST results.
- All new strings appear in regenerated POT.

Rollback Strategy
- If performance issues, allow disabling analytics via filter: apply_filters('content_poll_enable_analytics', true).
- Minimal schema changes; avoid irreversible DB migrations initially.

Done Definition (Phase 1)
- Code merged on main
- Tests passing (existing + new)
- POT regenerated including new strings
- README roadmap item updated (checked or expanded)

END
