# Research & Decisions: Vote Block for Page Content

Date: 2025-11-12
Branch: 001-vote-block

## Decision Log Format
Each decision includes rationale and considered alternatives.

---
## 1. AI Suggestion Source
**Decision**: Initial implementation will use heuristic keyword extraction (title, headings, frequent nouns) + admin editable suggestions; optional pluggable interface for external AI provider added later.
**Rationale**: Avoid external data transfer complexities and GDPR consent overhead in v1; heuristic is fast, zero cost, and privacy-respecting.
**Alternatives Considered**:
- External API (OpenAI/WP.com AI): Better linguistic quality but introduces cost, latency, data transfer compliance steps.
- Local lightweight model (PHP/JS embedding): Complexity and bundle size risk; limited accuracy.
- Manual only: No automation; reduces editor efficiency.

## 2. Storage Model for Votes
**Decision**: Use custom table `{$wpdb->prefix}vote_block_submissions` for vote submissions; store block configuration in post content/block attributes and aggregate counts cached via transient.
**Rationale**: Ensures scalable querying (counts, recent activity) and prevents performance degradation for high-volume blocks; separation of configuration vs submissions.
**Alternatives Considered**:
- Post meta arrays: Simple but inefficient for large volumes and aggregation queries.
- Options API: Not suitable for per-block dynamic data; autoload concerns.
- Custom post type per vote: Overhead and admin clutter.

## 3. Deduplication Mechanism
**Decision**: Use combination of browser-local token (generated UUID stored in cookie/localStorage) + server-side hashed token (salted) in submission table; fallback to IP+UA hash if token missing. Offer admin setting to enforce logged-in-only voting for stronger integrity.
**Rationale**: Balances privacy (no raw IP stored—only a salted, truncated hash) and effectiveness. Local token reduces repeat votes; fallback prevents trivial clearing circumvention.
**Alternatives Considered**:
- Pure IP+UA: Privacy concerns and NAT collisions.
- Logged-in only: Restricts participation and reduces engagement on public sites.
- Account creation prompt: Too heavy for simple sentiment feature.

## 4. Max Expected Votes Per Block
**Decision**: Target up to 250k lifetime votes per block; design table indexes accordingly (index on block_id + hashed_token, block_id + option).
**Rationale**: Provides headroom for popular articles; index strategy prevents slow aggregations.
**Alternatives Considered**:
- 10k cap: Might need migration later; underestimates viral content.
- Unlimited with no index planning: Risk of performance issues.

## 5. AI Opt-In & Consent Handling
**Decision**: AI suggestion heuristic enabled by default; external AI integration disabled until admin explicitly opts in and confirms consent via a settings toggle acknowledging data terms.
**Rationale**: Heuristic is local; external AI requires clear consent path to meet GDPR. Opt-in toggle reduces accidental data sharing.
**Alternatives Considered**:
- Always enabled external AI: Compliance and privacy risk.
- Disabled heuristic: Less editor convenience.

## 6. GDPR Compliance Measures
**Decision**: Avoid personal data collection. Store only hashed tokens (non-reversible with strong salt), no IP in raw form. Provide data export/erase integration: if user logged in and requests erasure, remove their submissions by user_id link (only if logged-in mode active). Document privacy in quickstart.
**Rationale**: Minimal data footprint simplifies compliance; supports WordPress privacy tools when accounts used.
**Alternatives Considered**:
- Store raw IP for stronger dedup: Not compliant without additional disclosures.
- No dedup: Inflated counts possible.

## 7. Theme Styling Integration
**Decision**: Block relies on theme typography and spacing via inheriting container class; only minimal BEM classes added for layout; uses CSS variables and no global stylesheet outside block area.
**Rationale**: Meets performance and styling guardrails; reduces need for custom overrides.
**Alternatives Considered**:
- Heavy custom CSS: Risk of conflicts and performance hit.
- Inline styles only: Harder to maintain and theme adapt.

## 8. Accessibility Strategy
**Decision**: Use semantic form controls (fieldset/legend, radio buttons) and ARIA live region for results reveal; focus management returns to first result container after vote.
**Rationale**: Straightforward pattern aligns with WCAG 2.1 AA.
**Alternatives Considered**:
- Custom div-based controls: Increases accessibility complexity.

## 9. Internationalization Approach
**Decision**: All static strings wrapped with `__()` using plugin text domain; dynamic option texts stored as entered; results summary phrases localized.
**Rationale**: Meets constitution i18n section.
**Alternatives Considered**:
- Hard-coded English only: Non-compliant.

## 10. REST API Versioning
**Decision**: Namespace `content-vote/v1`; future changes requiring breaking fields introduce `v2` while keeping `v1` stable.
**Rationale**: Standard WordPress pattern; supports backward compatibility.
**Alternatives Considered**:
- Unversioned: Harder to evolve safely.

---
## Unresolved Items
None — all clarifications addressed.

## Summary of Decisions Impact
- Custom table chosen: requires migration and uninstall cleanup.
- Local heuristic first: external AI pluggable path future.
- Hybrid dedup mechanism: moderate integrity, preserves privacy.
- Scalable indexes planned upfront.

## Next Steps
Proceed to data model & contract design (Phase 1).
