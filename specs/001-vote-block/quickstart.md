# Quickstart: Vote Block Feature

## Purpose
Enable editors to add interactive vote blocks (2–6 options) to content pages; visitors cast one vote and see results post-vote. Includes heuristic AI suggestions for question/options, GDPR-friendly anonymous deduplication, and admin stats.

## Installation
1. Ensure WordPress 6.6+ and PHP 8.1+.
2. Install/activate the plugin containing this feature.
3. Run build (if needed): `npm install && npm run build` (handled by @wordpress/scripts).

## Adding a Vote Block
1. Edit a post/page in the block editor.
2. Insert "Vote Block".
3. Enter a question text.
4. Set number of options (2–6) and fill labels.
5. (Optional) Click "Suggest" to auto-populate from heuristic analysis; edit as desired.
6. Publish/update post.

## Viewing Results
- Visitors see options; results hidden until first vote.
- After voting, aggregated counts/percentages appear.

## Preventing Duplicate Votes
- Browser token stored locally; server enforces uniqueness via hashed token.
- Clearing storage allows attempt; IP+UA hash fallback reduces abuse.
- Optionally enable "Logged-in only" voting on Settings page for stronger control.

## Admin Settings
Navigate to Settings > Content Vote:
- Toggle external AI integration (if added later).
- Enable logged-in-only voting.
- View global stats: total votes, top blocks, quick links to posts.

## Privacy & GDPR
- No personal data stored in anonymous mode (only salted hash tokens).
- Logged-in mode links submissions to user ID for erasure/export tools.
- External AI (if enabled) requires explicit consent acknowledgement.

## REST API Overview
- List blocks: `GET /wp-json/content-vote/v1/blocks/{postId}`
- Submit vote: `POST /wp-json/content-vote/v1/block/{blockId}/vote` (nonce required)
- Get results: `GET /wp-json/content-vote/v1/block/{blockId}/results`
- Suggest question/options: `GET /wp-json/content-vote/v1/suggest/{postId}` (capability required)

## Testing
- PHPUnit: VoteStorageService, REST endpoints.
- Jest: Block rendering & state.
- E2E (Playwright): Vote flow, duplicate prevention, results reveal.

## Uninstall
- Removes custom submissions table and plugin settings option; does not delete post content.

## Troubleshooting
- Results not showing: Ensure at least one vote submitted.
- Duplicate votes accepted: Confirm cookie/localStorage accessible; check server salt configuration.
- AI suggestions button missing: Feature disabled or permissions insufficient.

## Next Steps
- Implement external AI provider adapter.
- Add analytics export (CSV) for per-block votes.
