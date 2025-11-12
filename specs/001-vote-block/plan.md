# Implementation Plan: Vote Block for Page Content

**Branch**: `001-vote-block` | **Date**: 2025-11-12 | **Spec**: `specs/001-vote-block/spec.md`
**Input**: Feature specification from `specs/001-vote-block/spec.md`

**Note**: This template is filled in by the `/speckit.plan` command. See `.specify/templates/commands/plan.md` for the execution workflow.

## Summary

Provide a Gutenberg vote block allowing editors to configure 2–6 options for a question and visitors to submit one vote, view aggregated results only after voting, and ensuring GDPR-compliant, anonymous, single-vote enforcement. Include AI-assisted question suggestion generation based on page content analysis, an admin settings page with aggregated vote insights, and adherence to WordPress security (nonces, capabilities), i18n, accessibility, and performance constraints.

## Technical Context

**Language/Version**: PHP 8.3 (plugin), JavaScript/TypeScript (optional) via @wordpress/scripts for block build.
**Primary Dependencies**: Core WordPress APIs (@wordpress/block-editor, @wordpress/data, @wordpress/components), @wordpress/scripts build chain. NEEDS CLARIFICATION: External AI service or local heuristic for question suggestion? (Options: OpenAI API, WP.com AI, on-prem model, or heuristic keyword extraction.)
**Storage**: WordPress database: post meta for per-block configuration; custom table or post meta for vote submissions (NEEDS CLARIFICATION: Custom table vs serialized post meta for scalability). Options API for global settings (GDPR consent integration toggles).
**Testing**: PHPUnit (WordPress test suite) for PHP logic; Vitest + (optional) Testing Library DOM for pure block logic (option count helpers) and future component tests; Playwright/@wordpress/e2e-tests for front-end voting flow.
**Target Platform**: WordPress sites (minimum WP 6.8) standard LAMP/LEMP hosting.
**Project Type**: WordPress plugin feature with Gutenberg block + REST endpoints.
**Performance Goals**: Front-end vote submission round-trip perceived under 2s (from success criteria); block assets <15KB gzipped incremental; DB queries O(1) per vote.
**Constraints**: Must not store personal data; anonymized dedup token must comply with GDPR (NEEDS CLARIFICATION: Use hashed browser nonce + optional consent gating?). Avoid autoloaded large options; support up to high-traffic pages (e.g., thousands votes/day).
**Scale/Scope**: Initially moderate blog/site scale (hundreds to tens of thousands votes per question). NEEDS CLARIFICATION: Expected max votes per block for choosing storage strategy.

**Additional Requirements from User Input**:
- AI analysis of page content to auto-suggest potential question and answer options.
- Prevent multiple votes per person (best-effort without PII; includes GDPR compliance using WordPress consent tools/API).
- Settings page listing votes + link from posts/pages list row action.
- Block settings: set number of options (2–6), styling inherits current theme classes.
- Follow theme styling (no invasive global CSS; prefer CSS variables).

**Resolved Decisions (from research.md)**:
1. AI Suggestion Source: Local heuristic keyword extraction; external AI pluggable later.
2. Storage Model: Custom table for submissions + transient aggregate cache.
3. Deduplication Mechanism: Browser token + hashed salted fallback; optional logged-in enforcement.
4. Max Expected Votes: Plan for up to 250k lifetime votes per block; indexed table.
5. AI Opt-In & Consent: External AI disabled until explicit admin opt-in.

## Constitution Check (Initial)

| Gate | Status | Notes |
|------|--------|-------|
| Security (nonces + caps) | Pending | Will ensure REST vote create uses nonce + capability for editor actions, public vote submission sanitization. |
| Performance (scoped assets) | Pending | Block assets only enqueued when block present. |
| Accessibility (WCAG AA) | Pending | Ensure ARIA live region for results reveal. |
| i18n | Pending | All block UI strings wrapped. |
| Testability | Pending | Plan includes PHPUnit + Vitest + E2E. |
| Backward compatibility | Pending | New feature; versioned REST namespace. |
| Observability | Pending | Log vote submission failures via WP_Error. |

No violations identified; research decisions align with constitution (security, performance, privacy, testability).

## Project Structure

### Documentation (this feature)

```text
specs/[###-feature]/
├── plan.md              # This file (/speckit.plan command output)
├── research.md          # Phase 0 output (/speckit.plan command)
├── data-model.md        # Phase 1 output (/speckit.plan command)
├── quickstart.md        # Phase 1 output (/speckit.plan command)
├── contracts/           # Phase 1 output (/speckit.plan command)
└── tasks.md             # Phase 2 output (/speckit.tasks command - NOT created by /speckit.plan)
```

### Source Code (repository root)
<!--
  ACTION REQUIRED: Replace the placeholder tree below with the concrete layout
  for this feature. Delete unused options and expand the chosen structure with
  real paths (e.g., apps/admin, packages/something). The delivered plan must
  not include Option labels.
-->

```text
src/
├── block/
│   ├── vote-block/          # Gutenberg block source (index.js|tsx, edit, save, block.json)
│   └── ai-suggestions/      # (If separate UI component for AI generation button/modal)
├── php/
│   ├── Blocks/              # PHP registration, render callbacks
│   ├── REST/                # REST controllers (Vote_Submissions_Controller.php)
│   ├── Services/            # VoteStorageService, AISuggestionService wrapper
│   ├── Admin/               # Settings page, list table extensions
│   ├── Security/            # Nonce and capability helpers
│   └── Helpers/             # Sanitization, i18n helpers
tests/
├── unit/
│   ├── VoteStorageServiceTest.php
│   ├── AISuggestionServiceTest.php
├── integration/
│   ├── RestRoutesTest.php
├── e2e/
│   ├── vote-block.spec.js
contracts/
└── openapi.yaml             # Generated API contract
```

**Structure Decision**: Plugin feature integrated into existing repository root under `src/` with segregated PHP and block JS, matching constitution (block-first, scoped enqueues, separation of concerns).

## Complexity Tracking

Currently no constitutional violations proposed. Potential future complexity: custom table addition (if vote volume or query patterns demand). Will justify in research.md if chosen.
