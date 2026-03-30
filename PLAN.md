# Family Life - Current Project Plan

## Snapshot
- Date: 29 March 2026
- Status: Phase 2 is implemented and running. Backend APIs are complete.
- Focus now: UI development with dedicated pages for each major feature.

## Current State Summary
- Date: 29 March 2026
- Status: Phase 3 is complete! Multi-page UI with navigation is implemented and tested.
- Focus now: Post-launch refinements, testing workflows, optional enhancements.
- Family setup is implemented: create family and add members with auth tokens.
- Family IDs are generated opaque strings rather than autoincrement integers.
- URL token auth is implemented: `/?token=...` + bearer token for API calls.
- Task lifecycle is implemented: create, list, claim, and delete (creator only).
- Claim workflow is implemented: review queue, approve/reject, score updates on approval.
- Claim history is implemented: member-specific history endpoint.
- Scoreboard is implemented: sorted ranking with generated rank names.
- Voting module is implemented end-to-end:
  - open round
  - create wishes
  - vote using personal score budget
  - cumulative spent-score enforcement across all rounds
  - close round with 2 unique approvals
  - deterministic winner selection
  - winner wish deactivation
  - non-winning wishes remain active with carried score
- UI is multi-page with full navigation (Setup, Tasks, Claims, Approvals, Voting, Scoreboard)
- All 5 feature pages have dedicated UI with clean form inputs and data lists
- Responsive design covers mobile, tablet, and desktop screens
- Dark mode support included

## Architecture (As Implemented)

### Backend
- Language: PHP 8+
- API style: JSON over REST-like routes
- Data: SQLite via PDO
- Autoload: Composer PSR-4 (`FamilyLife\\Backend\\`)
- Entry points:
  - `api/index.php`
  - `backend/api/index.php`

### Frontend
- Language: Vanilla JavaScript (single-page style interactions)
- Styling: CSS
- Session token storage: LocalStorage
- Main UI logic: `public/js/app.js`

### Database
- SQLite schema auto-initialized in `backend/config/database.php`
- Tables in use:
  - `families`
  - `family_members`
  - `tasks`
  - `task_claims`
  - `voting_rounds`
  - `wishes`
  - `wish_votes`
  - `voting_round_closure_approvals`

## API Surface (Current)
- All endpoints remain the same (see Phase 1-2 list below)
- `POST /api/families`
- `GET /api/families/{familyId}/members`
- `POST /api/families/{familyId}/members`
- `GET /api/me`
- `GET /api/tasks`
- `POST /api/tasks`
- `DELETE /api/tasks/{taskId}`
- `GET /api/claims?status=all|pending|approved|rejected`
- `GET /api/claims/mine`
- `POST /api/claims`
- `PUT /api/claims/{claimId}/approve`
- `PUT /api/claims/{claimId}/reject`
- `GET /api/scoreboard`
- `GET /api/voting/rounds/current`
- `POST /api/voting/rounds`
- `GET /api/voting/wishes`
- `POST /api/voting/wishes`
- `POST /api/voting/votes`
- `POST /api/voting/rounds/{roundId}/approve-close`
- `GET /api/voting/rounds/{roundId}/result`

## Delivery Checklist

### Phase 1 (MVP)
- [x] Family creation
- [x] Add family members with auth tokens
- [x] SQLite schema and PDO setup
- [x] URL token auth
- [x] Create tasks with points
- [x] Claim tasks
- [x] Approve/reject claims with score updates
- [x] Scoreboard
- [x] Rank calculation

### Phase 2 (Enhancements)
- [x] Task deletion (creator only)
- [x] Claim history endpoint (`/claims/mine`)
- [x] Voting rounds and wish creation
- [x] Voting with cumulative spend limits
- [x] Round close approvals and auto-close at 2 approvals
- [x] Winner deactivation and non-winner carry-over

## Next Plan (Phase 3 - UI Development)

### Pages to Build
- [ ] **Tasks Page**: Browse available tasks, filter by status, create new tasks
- [ ] **Claims Page**: View all claims, manage own claims, claim workflow
- [ ] **Approvals Page**: Review pending claims, approve/reject with score updates (admin view)
- [ ] **Voting Page**: Vote on wishes, view voting round status, create wishes
- [ ] **Scoreboard Page**: View family rankings with animated updates

### Features
- [ ] Navigation between pages
- [ ] Session/token persistence across page loads
- [ ] Real-time score updates
- [ ] Form validation and error handling
- [ ] Responsive UI design
- [ ] Add role rules for claim review (optional: reviewer/admin policy).
- [ ] Improve API error consistency and validation messages.
- [ ] Add UI polish for task/claim/voting flows on small screens.
- [ ] Add backup/export strategy for SQLite data.
### Phase 3 (UI Development) - COMPLETED ✓
- [x] Multi-page navigation structure with sticky navbar
- [x] Tasks Page: Browse, create, claim, and delete tasks
- [x] Claims Page: View all claims with filtering (all, pending, approved, rejected)
- [x] Approvals Page: View own claims history and review pending claims
- [x] Voting Page: Manage voting round, create wishes, vote, close round
- [x] Scoreboard Page: Family rankings in responsive table format
- [x] Responsive design: Mobile, tablet, desktop optimized
- [x] Visual polish: Button hover effects, transitions, shadows, rounded corners
- [x] Dark mode CSS support included
- [x] Form validation and error handling
- [x] Session/token persistence via localStorage
- [x] Page navigation with data refresh on page load

## Next Plan (Phase 4 - Optional Refinements and Testing)

### Testing & Documentation
- [ ] Create end-to-end test workflow documentation
- [ ] Add smoke test checklist for manual QA
- [ ] Document all user flows with screenshots (if needed)
- [ ] Add API response examples in comments

### Optional Enhancements
- [ ] Add toast/notification messages for successful actions
- [ ] Add animated score update indicators
- [ ] Add real-time updates via WebSockets (if scaling needed)
- [ ] Add role-based claim review rules (reviewer/admin policy)
- [ ] Add family export/backup feature
- [ ] Add keyboard shortcuts for common actions
- [ ] Add search/filter capabilities for long lists
- [ ] Add data visualization (charts/graphs) for scoreboard
- [ ] Improve API error messages and validation feedback
- [ ] Add comprehensive test suite (unit + integration)

## Risks and Watch Items
- No automated regression suite yet; behavior changes can slip in silently.
- SQLite is fine for small family usage, but backup/recovery needs a clear workflow.
- Token auth is intentionally simple; long-lived token handling should stay documented.

## Definition of Done for Phase 3
- Core user flows are covered by repeatable automated tests.
- Manual smoke checklist exists for auth, claims, scoreboard, and voting lifecycle.
- Error handling and API responses are consistent across all endpoints.
- Mobile usability issues are resolved for primary screens.
