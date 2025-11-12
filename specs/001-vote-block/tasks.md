# Tasks: Vote Block for Page Content

**Input**: Design documents from `specs/001-vote-block/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/, quickstart.md

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no unmet dependencies)
- **[Story]**: Which user story this task belongs to (US1, US2, US3)
- All tasks include explicit file paths

---
## Phase 1: Setup (Shared Infrastructure)
**Purpose**: Initialize plugin structure, tooling, and baseline configuration.

- [ ] T001 Create `src/php/Blocks/` `src/php/REST/` `src/php/Services/` `src/php/Admin/` `src/php/Security/` and `src/block/vote-block/` directories
- [ ] T002 Initialize block build tooling (ensure `package.json` has @wordpress/scripts) in project root
- [ ] T003 [P] Create initial plugin bootstrap file `content-vote.php` with header and autoload includes
- [ ] T004 [P] Add PHPCS config `phpcs.xml` aligned with constitution
- [ ] T005 [P] Add ESLint config using `@wordpress/eslint-plugin` in root `.eslintrc.js`
- [ ] T006 Add Jest config for block tests `jest.config.js`
- [ ] T007 Add Playwright config for E2E `playwright.config.ts`
- [ ] T008 [P] Add Composer `composer.json` (if absent) with scripts for phpcs & phpunit
- [ ] T009 Add initial README stub `README.md` referencing quickstart & spec
- [ ] T010 [P] Configure text domain loading in `content-vote.php`

---
## Phase 2: Foundational (Blocking Prerequisites)
**Purpose**: Core infrastructure before user stories.

- [ ] T011 Define database schema migration function in `src/php/Services/SchemaMigrator.php`
- [ ] T012 [P] Implement activation hook & run migration in `content-vote.php`
- [ ] T013 [P] Implement uninstall cleanup `uninstall.php` removing custom table & options
- [ ] T014 Implement VoteStorageService skeleton in `src/php/Services/VoteStorageService.php`
- [ ] T015 [P] Implement AISuggestionService heuristic in `src/php/Services/AISuggestionService.php`
- [ ] T016 Implement Settings repository `src/php/Services/SettingsService.php`
- [ ] T017 [P] Register REST namespace base loader `src/php/REST/NamespaceRegistrar.php`
- [ ] T018 Implement security helper (nonce & capability checks) `src/php/Security/SecurityHelper.php`
- [ ] T019 [P] Add i18n helper `src/php/Helpers/I18n.php`
- [ ] T020 Configure logging/error handling wrapper `src/php/Services/LoggingService.php`
- [ ] T021 [P] Add privacy integration hooks (erase/export) `src/php/Services/PrivacyService.php`
- [ ] T022 Add base PHPUnit bootstrap `tests/php/bootstrap.php`
- [ ] T023 [P] Add base PHPUnit test `tests/php/unit/SmokeTest.php`
- [ ] T024 Add Jest setup file `tests/js/setupTests.ts`
- [ ] T025 [P] Add Playwright smoke test draft `tests/e2e/smoke.spec.ts`

**Checkpoint**: Foundation complete, tables migrate, services stubbed, tooling ready.

---
## Phase 3: User Story 1 - Visitor casts a vote (Priority: P1) 🎯 MVP
**Goal**: Allow a visitor to submit exactly one vote and receive confirmation.
**Independent Test**: Visiting a page with one vote block; submitting a vote returns success and prevents duplication.

### Tests (Write first)
- [ ] T026 [P] [US1] PHPUnit test VoteStorageService create & dedup `tests/php/unit/VoteStorageServiceTest.php`
- [ ] T027 [P] [US1] REST contract test POST /block/{blockId}/vote `tests/php/integration/RestVoteEndpointTest.php`
- [ ] T028 [P] [US1] Vitest option logic unit tests `src/block/vote-block/__tests__/options-logic.test.js`
- [ ] T029 [US1] Playwright E2E test single vote flow `tests/e2e/vote-flow.spec.ts`

### Implementation
- [ ] T030 [P] [US1] Create block manifest `src/block/vote-block/block.json`
- [ ] T031 [P] [US1] Implement block edit component `src/block/vote-block/edit.tsx`
- [ ] T032 [P] [US1] Implement block save component (dynamic uses render callback) `src/block/vote-block/save.tsx`
- [ ] T033 [US1] Register block PHP side `src/php/Blocks/VoteBlock.php`
- [ ] T034 [P] [US1] Implement REST controller for voting `src/php/REST/VoteController.php`
- [ ] T035 [US1] Implement dedup logic in VoteStorageService (token + hash) `src/php/Services/VoteStorageService.php`
- [ ] T036 [US1] Add nonce creation & injection to front-end `src/php/Security/SecurityHelper.php`
- [ ] T037 [P] [US1] Front-end JS vote submission handler `src/block/vote-block/vote-submit.ts`
- [ ] T038 [US1] Accessibility: fieldset/legend + focus management in `edit.tsx`
- [ ] T039 [P] [US1] i18n wrap all new static strings `edit.tsx` `save.tsx` `VoteBlock.php`
- [ ] T040 [US1] Add duplicate vote UI message in `edit.tsx`
- [ ] T041 [US1] Wire server response handling & confirmation state `vote-submit.ts`
- [ ] T042 [US1] PHPCS compliance review for new PHP files
- [ ] T043 [US1] ESLint/Jest pass for new JS/TS files

**Checkpoint**: A single block supports voting; duplicate prevented; confirmation UI shown.

---
## Phase 4: User Story 2 - Visitor views aggregated results (Priority: P2)
**Goal**: Display aggregated results only after first vote; real-time update.
**Independent Test**: After multiple votes seeded, results show correct counts and percentages.

### Tests
- [ ] T044 [P] [US2] PHPUnit test aggregation logic `tests/php/unit/AggregationTest.php`
- [ ] T045 [P] [US2] REST contract test GET /block/{blockId}/results `tests/php/integration/RestResultsEndpointTest.php`
- [ ] T046 [P] [US2] Vitest test results component logic (helper functions) `src/block/vote-block/__tests__/results.test.js`
- [ ] T047 [US2] Playwright result visibility & update test `tests/e2e/results-visibility.spec.ts`

### Implementation
- [ ] T048 [P] [US2] Implement transient-based aggregation caching `src/php/Services/VoteStorageService.php`
- [ ] T049 [P] [US2] Add REST results endpoint logic `src/php/REST/ResultsController.php`
- [ ] T050 [US2] Front-end results component `src/block/vote-block/results.tsx`
- [ ] T051 [US2] Live region ARIA for results update `results.tsx`
- [ ] T052 [US2] Hide results pre-vote conditional logic `edit.tsx`
- [ ] T053 [P] [US2] Add percentages calculation & formatting helper `src/php/Services/FormattingService.php`
- [ ] T054 [US2] Update vote-submit flow to re-fetch results `vote-submit.ts`
- [ ] T055 [US2] i18n wrap new strings `results.tsx`
- [ ] T056 [US2] PHPCS/ESLint compliance checks

**Checkpoint**: Results appear correctly after votes; accessibility & i18n ensured.

---
## Phase 5: User Story 3 - Editor configures a vote block (Priority: P3)
**Goal**: Editor can create and configure question, 2–6 options; disallow changes post-vote.
**Independent Test**: Editor creates block with 2, 4, and 6 options; attempts post-vote edits are blocked.

### Tests
- [ ] T057 [P] [US3] PHPUnit test option configuration validation `tests/php/unit/BlockConfigValidationTest.php`
- [ ] T058 [P] [US3] Vitest test dynamic option fields logic `src/block/vote-block/__tests__/options-config.test.js`
- [ ] T059 [US3] Playwright editor configuration flow test `tests/e2e/editor-config.spec.ts`

### Implementation
- [ ] T060 [P] [US3] Add dynamic option count controls in `edit.tsx`
- [ ] T061 [US3] Validation: enforce 2–6 option count `VoteBlock.php`
- [ ] T062 [US3] Disable option edits post-first vote `edit.tsx`
- [ ] T063 [US3] Add server-side guard preventing option mutation after votes `VoteStorageService.php`
- [ ] T064 [US3] AI suggestion trigger button `edit.tsx`
- [ ] T065 [P] [US3] Heuristic suggestion endpoint controller `src/php/REST/SuggestionController.php`
- [ ] T066 [US3] Integrate suggestion response into editor UI `edit.tsx`
- [ ] T067 [US3] Capability checks for suggestion endpoint `SuggestionController.php`
- [ ] T068 [US3] i18n for configuration UI strings
- [ ] T069 [US3] PHPCS/ESLint pass

**Checkpoint**: Editor fully configures and publishes vote block with dynamic options and AI suggestions.

---
## Phase 6: Settings & Admin Stats (Cross-Cutting)
**Purpose**: Provide admin settings page & stats view; link from posts list.

- [ ] T070 Create settings page skeleton `src/php/Admin/SettingsPage.php`
- [ ] T071 [P] Add settings registration & option handling `SettingsService.php`
- [ ] T072 [P] Admin list table column/link for posts with blocks `src/php/Admin/PostListIntegration.php`
- [ ] T073 Implement global stats endpoint `src/php/REST/AdminStatsController.php`
- [ ] T074 [P] Settings page nonce/capability checks `SettingsPage.php`
- [ ] T075 Render stats (top blocks, totals) `SettingsPage.php`
- [ ] T076 [P] Privacy policy content injection hook `PrivacyService.php`
- [ ] T077 Add help tab documentation `SettingsPage.php`
- [ ] T078 PHPCS/ESLint checks for admin additions

---
## Phase 7: Polish & Cross-Cutting Concerns
**Purpose**: Final refinements across all stories.

- [ ] T079 [P] Add CSS variables & theme inheritance review `src/block/vote-block/style.scss`
- [ ] T080 Performance audit: ensure scoped enqueues `VoteBlock.php`
- [ ] T081 [P] Add caching invalidation tests `tests/php/integration/CacheInvalidationTest.php`
- [ ] T082 Security audit: confirm nonce & capability on all REST controllers `src/php/REST/*.php`
- [ ] T083 [P] Add translation POT generation script `bin/make-pot.sh`
- [ ] T084 Accessibility audit checklist doc `docs/accessibility-audit.md`
- [ ] T085 [P] Add README usage examples `README.md`
- [ ] T086 Add changelog entry `CHANGELOG.md`
- [ ] T087 Final PHPCS/ESLint/Test full suite run
- [ ] T088 Tag version & update plugin header `content-vote.php`

---
## Dependencies & Execution Order

### Phase Dependencies
- Phase 1 → Phase 2 → User Story phases (3–5) can start only after Phase 2
- Settings/Admin (Phase 6) can begin after storage & results (Phases 3–4)
- Polish (Phase 7) after all functional phases.

### User Story Independence
- US1 (vote submission) independent MVP.
- US2 relies on votes existing but can implement after US1 or in parallel once storage available.
- US3 block configuration mostly independent; option edit lock requires vote existence check (minor dependency on US1 storage logic).

### Parallel Opportunities Examples
- Directory creation (T001) then T003/T004/T005/T008 in parallel.
- REST controllers: Vote (T034), Results (T049), Suggestion (T065) parallel after foundational services.
- Front-end components: edit.tsx (T031), results.tsx (T050), options logic (T060) can proceed in parallel once block.json (T030) defined.

### Critical Path (MVP)
T001 → T011–T021 (foundation) → T026–T043 (US1) → Deploy MVP.

---
## Implementation Strategy

### MVP Scope (Deliver US1 Only)
- Includes: voting, dedup, confirmation UI, security, i18n, accessibility basics.
- Excludes: results aggregation display, editor AI suggestions, settings stats.

### Incremental Delivery
1. MVP (US1) live for early feedback.
2. Add results view (US2) for engagement metrics.
3. Add editor configuration enhancements + AI suggestions (US3).
4. Add admin settings & stats (Phase 6).
5. Polish & audits (Phase 7).

### Testing Approach
- Test-first for storage & REST endpoints (contract + unit) in each story.
- E2E after implementation per story to validate independence.

---
## Task Summary
- Total Tasks: 88
- User Story Task Counts: US1 (18), US2 (13), US3 (13)
- Parallelizable Tasks: Marked with [P] (estimated 40+)
- Independent Test Criteria: Defined per user story checkpoints.

## Format Validation
All tasks follow required format: `- [ ] T### [P?] [US#?] Description with file path`.

---
## Notes
- Ensure all new PHP files include `declare(strict_types=1);` where appropriate.
- Escape output late (`esc_html`, `esc_attr`) and sanitize all input.
- Do not enqueue block assets globally—scope to presence of block.
- Keep custom table lean; avoid autoloaded large options.

