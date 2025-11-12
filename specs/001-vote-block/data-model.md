# Data Model: Vote Block for Page Content

Date: 2025-11-12

## Overview
Entities supporting configurable vote blocks with AI-assisted suggestions, anonymous single vote enforcement, and aggregated results.

## Entities

### VoteBlock
Represents a configured question embedded via Gutenberg block.
- `id` (string) – Unique block instance identifier (e.g., generated UUID stored in block attributes).
- `post_id` (int) – Parent post/page ID hosting the block.
- `question` (string) – Question text.
- `options` (array[string]) – 2–6 option labels in display order.
- `created_at` (datetime)
- `status` (enum: active, closed) – Closed prevents further votes.
- `ai_suggested` (bool) – Whether initial question/options were auto-generated.

Validation:
- `question` non-empty, <= 255 chars.
- Each option non-empty, <= 100 chars, unique within block.
- Option count between 2 and 6.

### VoteSubmission
Single anonymous submission.
- `id` (auto-increment / bigint)
- `block_id` (string) – Foreign key reference to VoteBlock.id.
- `post_id` (int) – Redundant for faster queries by post.
- `option_index` (tinyint) – 0-based index referencing options array.
- `hashed_token` (char(64)) – SHA-256 hash of browser token + server salt.
- `created_at` (datetime)

Constraints:
- Unique index on (`block_id`, `hashed_token`).
- Index on (`block_id`, `option_index`).

### VoteAggregate (Derived)
Cached summary for fast display.
- `block_id` (string)
- `total_votes` (int)
- `counts` (array[int]) – Parallel to options array.
- `percentages` (array[float]) – Derived = counts[i] / total_votes * 100.
- `generated_at` (datetime)

### AISuggestion
Generated suggestion set prior to editor confirmation.
- `id` (UUID)
- `post_id` (int)
- `question` (string)
- `options` (array[string]) – 2–6 candidate options.
- `created_at` (datetime)
- `source` (enum: heuristic, external)

### Settings (Option)
Global plugin settings stored via `get_option('content_vote_settings')`.
- `enable_external_ai` (bool)
- `consent_acknowledged` (bool)
- `logged_in_only` (bool) – If true, enforce capability + user ID for submissions.
- `results_delay_ms` (int) – For potential UX animation.

### Privacy & Erasure Mapping (Conditional)
If `logged_in_only` enabled:
- Link from VoteSubmission to `user_id` (int, nullable when anonymous mode).
- Erasure tool routine: remove submissions by `user_id` on request.

## State Transitions
VoteBlock Status:
- `active` -> `closed` when editor manually closes (disables new submissions).

## Derived Calculations
Percentages recalculated after each vote or periodically via transient invalidation.

## Edge Case Handling
- Empty counts: results withheld until first vote.
- Closed block: submission endpoint returns error with user-friendly message.
- Option edit disallowed post-votes to preserve submission integrity.

## Data Retention & Cleanup
- Submissions retained indefinitely (business analytics) unless GDPR erasure invoked (logged-in mode).
- On uninstall: remove custom table and settings option.

## Justification
Custom table enables efficient counting and constraints vs post meta nested arrays.

## Open Data Considerations
None pending; all research decisions incorporated.
