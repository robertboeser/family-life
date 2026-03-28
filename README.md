# Family Life

Phase 1 MVP for a family gamification app.

## Implemented (Phase 1)

- Family creation
- Add family members with auth tokens
- SQLite database schema and PDO setup
- URL token auth (`/?token=...`)
- Create tasks with points
- Claim tasks
- Approve or reject claims with score updates
- Basic scoreboard
- Rank calculation

## Tech

- Backend: PHP 8+ + SQLite (PDO)
- Frontend: Vanilla JS + CSS

## Project Structure

- `index.php` - root entrypoint
- `api/index.php` - API entrypoint
- `backend/config/database.php` - PDO + schema initialization
- `backend/api/index.php` - MVP API router and handlers
- `public/index.php` - frontend page
- `public/js/app.js` - frontend logic
- `public/css/styles.css` - frontend styles

## Run Locally

From the project root:

```bash
php -S localhost:8000
```

Then open:

```text
http://localhost:8000/
```

## API (Phase 1)

- `POST /api/families`
- `GET /api/families/{familyId}/members`
- `POST /api/families/{familyId}/members`
- `GET /api/me` (auth required)
- `GET /api/tasks` (auth required)
- `POST /api/tasks` (auth required)
- `GET /api/claims?status=all|pending|approved|rejected` (auth required)
- `POST /api/claims` (auth required)
- `PUT /api/claims/{claimId}/approve` (auth required)
- `PUT /api/claims/{claimId}/reject` (auth required)
- `GET /api/scoreboard` (auth required)

Auth header format:

```text
Authorization: Bearer <auth_token>
```
