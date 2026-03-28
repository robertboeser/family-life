# Family Life

Family gamification app for tasks, claims, scoreboards, and family voting rounds.

Current status: Phase 2 is implemented and running.
Current focus: stabilization, test coverage, and UX polish (Phase 3).

## Implemented

- Family creation
- String family IDs generated with `Robo\\RoboID\\RoboB32::genID()`
- Add family members with auth tokens
- SQLite database schema and PDO setup
- URL token auth (`/?token=...`)
- Create tasks with points
- Delete tasks (creator only)
- Claim tasks
- Approve or reject claims with score updates
- Claim history endpoint for current member
- Basic scoreboard
- Rank calculation
- Voting module:
	- Open and close voting rounds
	- Create wishes
	- Vote on wishes by spending score budget
	- Enforce cumulative spent-score limit across all rounds
	- Auto-close round when 2 unique members approve closure
	- Mark winner as inactive and keep non-winning wishes active with carried score

## Tech

- Backend: PHP 8+ + SQLite (PDO)
- Frontend: Vanilla JS + CSS
- Dependency management: Composer with PSR-4 autoloading

## Project Structure

- `index.php` - root entrypoint
- `api/index.php` - API entrypoint
- `backend/config/database.php` - PDO + schema initialization
- `backend/api/index.php` - API router and handlers
- `public/index.php` - frontend page
- `public/js/app.js` - frontend logic
- `public/css/styles.css` - frontend styles

## Next

- Add automated tests for service and routing edge cases
- Improve API validation and error consistency
- Polish mobile usability for tasks, claims, and voting flows
- Add a lightweight SQLite backup/export workflow

## Run Locally

From the project root:

```bash
composer install
php -S localhost:8000
```

Then open:

```text
http://localhost:8000/
```

Regenerate autoload files after adding backend classes:

```bash
composer dump-autoload
```

## API

- `POST /api/families`
- `GET /api/families/{familyId}/members`
- `POST /api/families/{familyId}/members`
- `GET /api/me` (auth required)
- `GET /api/tasks` (auth required)
- `POST /api/tasks` (auth required)
- `DELETE /api/tasks/{taskId}` (auth required, creator only)
- `GET /api/claims?status=all|pending|approved|rejected` (auth required)
- `GET /api/claims/mine` (auth required)
- `POST /api/claims` (auth required)
- `PUT /api/claims/{claimId}/approve` (auth required)
- `PUT /api/claims/{claimId}/reject` (auth required)
- `GET /api/scoreboard` (auth required)
- `GET /api/voting/rounds/current` (auth required)
- `POST /api/voting/rounds` (auth required)
- `GET /api/voting/wishes` (auth required)
- `POST /api/voting/wishes` (auth required)
- `POST /api/voting/votes` (auth required)
- `POST /api/voting/rounds/{roundId}/approve-close` (auth required)
- `GET /api/voting/rounds/{roundId}/result` (auth required)

Auth header format:

```text
Authorization: Bearer <auth_token>
```

Family IDs are opaque strings, not numeric autoincrement values.
