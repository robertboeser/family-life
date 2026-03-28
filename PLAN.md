# Family Life - Current Project Plan

## Snapshot
- Date: 28 March 2026
- Status: Phase 2 is implemented and running.
- Focus now: stabilization, test coverage, and UX polish.

## Current State Summary
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

## Next Plan (Phase 3)
- [ ] Add automated tests for services and routing edge cases.
- [ ] Add seed/dev helpers for fast local scenario setup.
- [ ] Add role rules for claim review (optional: reviewer/admin policy).
- [ ] Improve API error consistency and validation messages.
- [ ] Add UI polish for task/claim/voting flows on small screens.
- [ ] Add backup/export strategy for SQLite data.

## Risks and Watch Items
- No automated regression suite yet; behavior changes can slip in silently.
- SQLite is fine for small family usage, but backup/recovery needs a clear workflow.
- Token auth is intentionally simple; long-lived token handling should stay documented.

## Definition of Done for Phase 3
- Core user flows are covered by repeatable automated tests.
- Manual smoke checklist exists for auth, claims, scoreboard, and voting lifecycle.
- Error handling and API responses are consistent across all endpoints.
- Mobile usability issues are resolved for primary screens.
