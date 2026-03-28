# Family Gamification App - Project Plan

## Project Overview
A web-based gamification app designed for families to manage tasks, earn points, and compete on a scoreboard with automatically generated rank names.

**Current Date:** 28 March 2026

---

## Technology Stack

### Frontend
- **Language:** Vanilla JavaScript (ES6+)
- **Styling:** CSS3
- **Architecture:** Component-based module pattern
- **Storage:** LocalStorage for session management

### Backend
- **Language:** PHP 8+
- **API Style:** RESTful JSON API
- **Database:** SQLite 3 with PDO

### Database
- **Engine:** SQLite
- **Access:** PHP Data Objects (PDO)
- **File Location:** Single `.db` file in project root

---

## Database Schema

### Tables

#### `families`
- `id` (INTEGER, PRIMARY KEY)
- `name` (TEXT, NOT NULL)
- `created_at` (DATETIME, DEFAULT CURRENT_TIMESTAMP)
- `updated_at` (DATETIME, DEFAULT CURRENT_TIMESTAMP)

#### `family_members`
- `id` (INTEGER, PRIMARY KEY)
- `family_id` (INTEGER, FOREIGN KEY → families.id)
- `name` (TEXT, NOT NULL)
- `auth_token` (TEXT, UNIQUE, NOT NULL)
- `score` (INTEGER, DEFAULT 0)
- `created_at` (DATETIME, DEFAULT CURRENT_TIMESTAMP)
- `updated_at` (DATETIME, DEFAULT CURRENT_TIMESTAMP)

#### `tasks`
- `id` (INTEGER, PRIMARY KEY)
- `family_id` (INTEGER, FOREIGN KEY → families.id)
- `name` (TEXT, NOT NULL)
- `points` (INTEGER, NOT NULL)
- `created_by` (INTEGER, FOREIGN KEY → family_members.id)
- `created_at` (DATETIME, DEFAULT CURRENT_TIMESTAMP)
- `updated_at` (DATETIME, DEFAULT CURRENT_TIMESTAMP)

#### `task_claims`
- `id` (INTEGER, PRIMARY KEY)
- `task_id` (INTEGER, FOREIGN KEY → tasks.id)
- `claimed_by` (INTEGER, FOREIGN KEY → family_members.id)
- `status` (TEXT, CHECK(status IN ('pending', 'approved', 'rejected')), DEFAULT 'pending')
- `approved_by` (INTEGER, FOREIGN KEY → family_members.id, NULLABLE)
- `created_at` (DATETIME, DEFAULT CURRENT_TIMESTAMP)
- `updated_at` (DATETIME, DEFAULT CURRENT_TIMESTAMP)

#### `voting_rounds`
- `id` (INTEGER, PRIMARY KEY)
- `family_id` (INTEGER, FOREIGN KEY → families.id)
- `status` (TEXT, CHECK(status IN ('open', 'closed')), DEFAULT 'open')
- `closed_at` (DATETIME, NULLABLE)
- `closed_wish_id` (INTEGER, FOREIGN KEY → wishes.id, NULLABLE)
- `created_at` (DATETIME, DEFAULT CURRENT_TIMESTAMP)
- `updated_at` (DATETIME, DEFAULT CURRENT_TIMESTAMP)

#### `wishes`
- `id` (INTEGER, PRIMARY KEY)
- `family_id` (INTEGER, FOREIGN KEY → families.id)
- `round_id` (INTEGER, FOREIGN KEY → voting_rounds.id)
- `name` (TEXT, NOT NULL)
- `score` (INTEGER, NOT NULL, DEFAULT 0)
- `created_by` (INTEGER, FOREIGN KEY → family_members.id)
- `is_active` (INTEGER, NOT NULL, DEFAULT 1)  # 1 = available for voting, 0 = retired after winning
- `created_at` (DATETIME, DEFAULT CURRENT_TIMESTAMP)
- `updated_at` (DATETIME, DEFAULT CURRENT_TIMESTAMP)

#### `wish_votes`
- `id` (INTEGER, PRIMARY KEY)
- `round_id` (INTEGER, FOREIGN KEY → voting_rounds.id)
- `wish_id` (INTEGER, FOREIGN KEY → wishes.id)
- `member_id` (INTEGER, FOREIGN KEY → family_members.id)
- `amount` (INTEGER, NOT NULL)
- `created_at` (DATETIME, DEFAULT CURRENT_TIMESTAMP)

#### `voting_round_closure_approvals`
- `id` (INTEGER, PRIMARY KEY)
- `round_id` (INTEGER, FOREIGN KEY → voting_rounds.id)
- `member_id` (INTEGER, FOREIGN KEY → family_members.id)
- `created_at` (DATETIME, DEFAULT CURRENT_TIMESTAMP)
- UNIQUE(`round_id`, `member_id`) to prevent duplicate approvals by same member

---

## Authentication Flow

### URL-Based Authentication
- Each family member receives a unique URL: `/?token={auth_token}`
- Token is 32+ character random string (generated on member creation)
- Token is stored in LocalStorage when user accesses their URL
- Token is sent with every API request in `Authorization` header or query parameter
- No login page required; direct access via URL

### Session Management
- Token persists in LocalStorage until user clears it or manually logs out
- Optional: Add device identification (fingerprinting) to prevent token theft

---

## API Endpoints

### Family Management
- `POST /api/families` → Create new family
  - Request: `{ name: string }`
  - Response: `{ id, name, created_at }`

### Family Members
- `GET /api/families/{familyId}/members` → List all members
  - Response: `[{ id, name, score, rank }]`
- `POST /api/families/{familyId}/members` → Add new member
  - Request: `{ name: string }`
  - Response: `{ id, name, auth_token, score }`
- `GET /api/me` → Get current user info (requires auth token)
  - Response: `{ id, name, family_id, score, rank }`

### Tasks
- `GET /api/tasks` → List all tasks for user's family
  - Response: `[{ id, name, points, created_by }]`
- `POST /api/tasks` → Create new task (requires auth token)
  - Request: `{ name: string, points: integer }`
  - Response: `{ id, name, points, created_by, created_at }`
- `DELETE /api/tasks/{taskId}` → Delete task (only by creator or admin)
  - Response: `{ success: boolean }`

### Task Claims
- `GET /api/claims` → List all claims for user's family (pending, approved, rejected)
  - Query Params: `?status=pending|approved|rejected|all`
  - Response: `[{ id, task_id, task_name, claimed_by, claimed_by_name, status, approved_by, created_at }]`
- `POST /api/claims` → Claim a task (requires auth token)
  - Request: `{ task_id: integer }`
  - Response: `{ id, task_id, status: 'pending', created_at }`
- `PUT /api/claims/{claimId}/approve` → Approve a claim (requires auth token)
  - Request: `{ approved: boolean }`
  - Response: `{ id, status, updated_at }` (also updates claimer's score if approved)
- `PUT /api/claims/{claimId}/reject` → Reject a claim
  - Response: `{ id, status, updated_at }`

### Scoreboard
- `GET /api/scoreboard` → Get ranked scoreboard for family
  - Response: `[{ id, name, score, rank, rank_from, rank_to, position }]`

### Voting Module
- `GET /api/voting/rounds/current` → Get current open round for user's family
  - Response: `{ id, status, created_at, closure_approvals_count }`
- `POST /api/voting/rounds` → Create a new round (only if no open round exists)
  - Response: `{ id, status: 'open', created_at }`
- `GET /api/voting/wishes` → List active wishes in current round
  - Response: `[{ id, name, score, created_by, created_by_name, is_active }]`
- `POST /api/voting/wishes` → Create a wish in current round
  - Request: `{ name: string }`
  - Rules:
    - A wish starts with `score = 0`
    - A member can create a new wish if they do not already have an active wish in the round
    - The member who created the winning wish in the previous round can create a new wish in the next round
  - Response: `{ id, name, score, created_by, round_id }`
- `POST /api/voting/votes` → Place vote/bet on a wish using personal score
  - Request: `{ wish_id: integer, amount: integer }`
  - Rules:
    - `amount` must be positive
    - Member can only spend score that was never spent in previous rounds
    - Track spent score as cumulative spent amount across all historical rounds
    - Available voting balance = `family_members.score - SUM(wish_votes.amount by member)`
  - Response: `{ wish_id, amount, member_available_after_vote, wish_score_after_vote }`
- `POST /api/voting/rounds/{roundId}/approve-close` → Approve closing a round
  - Rules:
    - At least 2 distinct family members must approve closure
    - Once approvals reach 2, the round is closed automatically
  - Response: `{ round_id, status, approvals_count }`
- `GET /api/voting/rounds/{roundId}/result` → Get closed round winner
  - Response: `{ winning_wish_id, winning_wish_name, winning_score, winning_created_by }`

---

## Frontend Structure

### Pages

#### 1. Home / Scoreboard (`/`)
- Display current user (if authenticated)
- Show scoreboard with all family members
- Display rank names and scores
- Quick access to other pages

#### 2. Family Setup (`/setup`)
- Create new family (modal/page)
- Generate family members with auth tokens
- Show auth URLs to copy/share

#### 3. Tasks Management (`/tasks`)
- List all tasks with points
- Add new task (form)
- Delete task button (only for creator)
- Claim task button

#### 4. My Claims (`/claims`)
- Show claims submitted by current user (pending, approved, rejected)
- History of claimed tasks

#### 5. Pending Claims Review (`/review`)
- List all pending claims for family
- Approve/Reject buttons
- Show who claimed, which task, points value

#### 6. Voting (`/voting`)
- Show current round status (`open`/`closed`)
- List wishes with current score and owner
- Create new wish form
- Vote/bet form (choose wish + amount)
- Show personal available voting balance
- Show close-round approvals and button to approve closure
- After closure, show winning wish and lock it from future voting

### Components (Modules)
- `header.js` - Navigation and user info
- `scoreboard.js` - Display ranked members
- `taskList.js` - Display and manage tasks
- `claimForm.js` - Submit task claim
- `claimReview.js` - Approve/Reject claims
- `api.js` - All API calls
- `auth.js` - Token management and verification
- `rank.js` - Rank name generation from score
- `ui.js` - Common UI utilities (modals, alerts, etc.)
- `voting.js` - Voting page state, rendering, and interactions
- `wishList.js` - Display wishes and current scores
- `voteForm.js` - Submit vote/bet amount
- `roundClosure.js` - Round closure approval UI and result state

---

## Backend Structure

### File Organization
```
backend/
├── config/
│   ├── database.php      # PDO connection setup
│   └── constants.php     # App constants
├── src/
│   ├── Models/
│   │   ├── Family.php
│   │   ├── FamilyMember.php
│   │   ├── Task.php
│   │   └── TaskClaim.php
│   ├── Middleware/
│   │   └── Auth.php      # Token validation
│   ├── Controllers/
│   │   ├── FamilyController.php
│   │   ├── MemberController.php
│   │   ├── TaskController.php
│   │   ├── ClaimController.php
│   │   └── VotingController.php
│   └── helpers.php       # Utility functions
├── api/
│   ├── index.php         # Router
│   ├── families.php
│   ├── members.php
│   ├── tasks.php
│   ├── claims.php
│   ├── scoreboard.php
│   └── voting.php
├── public/
│   ├── index.php         # Frontend entry point
│   ├── js/
│   ├── css/
│   └── assets/
└── database.db           # SQLite database file
```

### Core Features
- **Database Class:** Abstraction layer for PDO
- **Router:** Simple URL-based routing
- **Middleware:** Token authentication on protected endpoints
- **Error Handling:** JSON error responses with proper HTTP status codes
- **CORS:** Handle cross-origin requests if frontend/backend separated

### Voting Rules (Business Logic)
- A wish has `name` and `score`.
- Wishes are created inside a round.
- Members vote by spending their own score as betting points.
- A member's voting budget is limited to score not previously used in historical votes.
- Round closure needs approvals from at least 2 distinct family members.
- Closing a round selects winner by highest `wish.score`.
- Tie handling recommendation: earliest wish by `created_at` wins (must be deterministic).
- Winning wish becomes inactive and cannot be voted on in future rounds.
- All non-winning wishes remain active with their accumulated score and can be voted again in next rounds.
- The member who defined the winning wish can create a new wish after the round closes.

---

## Rank System

### Algorithm
- **Range:** Every 20 points = new rank
- **Rank 1:** 0-19 points
- **Rank 2:** 20-39 points
- **Rank 3:** 40-59 points
- etc.

### Rank Names (Suggested)
- Novice (0-19)
- Apprentice (20-39)
- Journeyman (40-59)
- Master (60-79)
- Grandmaster (80-99)
- Legend (100+)

**Note:** Rank names can be customized per family or made configurable.

### Implementation
- Function: `calculateRank(score)` → Returns rank object with `rank, name, from, to`
- Update on every claim approval
- Display on scoreboard

---

## User Flows

### New Family Setup
1. User visits app
2. Clicks "Create Family"
3. Enters family name
4. Adds family members (name only)
5. System generates auth tokens for each member
6. URLs are displayed for sharing (e.g., `familyapp.com/#token=abc123...`)
7. Each member receives their unique URL to save/share

### Claiming a Task
1. User (authenticated) views tasks
2. Clicks "Claim" on a task
3. Task claim created with status `pending`
4. User sees claim in "My Claims" page

### Approving a Claim
1. Family member views "Pending Claims"
2. Reviews who claimed what task
3. Clicks "Approve" or "Reject"
4. If approved:
   - Claim status → `approved`
   - Claimer's score += task points
   - Rank recalculated
5. Scoreboard updates in real-time (or on refresh)

### Viewing Scoreboard
1. User opens home page
2. Sees all family members ranked by score
3. Current rank name displayed
4. Scores in descending order

### Voting Round Lifecycle
1. Family has one active round (`open`) at a time.
2. Members create wishes (`name`, initial `score = 0`).
3. Members place votes/bets by spending their available voting balance.
4. Votes increase selected wish `score` by the voted amount.
5. Members approve closing the round.
6. When 2 unique approvals exist, round closes automatically.
7. Highest-scoring wish wins and is marked inactive.
8. Winner's creator may create a new wish for the next open round.
9. Non-winning wishes keep their score and remain available for future voting.

---

## Features & Requirements Checklist

### Phase 1: MVP
- [ ] Family creation
- [ ] Add family members with auth tokens
- [ ] Database schema and PDO setup
- [ ] Authentication via URL token
- [ ] Create tasks with points
- [ ] Claim tasks
- [ ] Approve/Reject claims with score updates
- [ ] Basic scoreboard
- [ ] Rank calculation

### Phase 2: Enhancement
- [ ] Task deletion
- [ ] Claim history/archive
- [ ] Voting module with wishes, votes, and round closure approvals
- [ ] Persist cross-round voting spend limits per member
- [ ] Carry over non-winning wishes with existing score

### Phase 3: Advanced
- [ ] Rank name customization
- [ ] Task categories/tags
- [ ] Claim comments/notes
- [ ] Family settings/preferences

### Phase 4: Masterclass
- [ ] Mobile app wrapper
- [ ] Notifications (browser/email)
- [ ] Achievement badges
- [ ] Seasonal competitions
- [ ] Task suggestions
- [ ] Analytics/Reports
- [ ] Real-time scoreboard updates (WebSocket or polling)

---

## Security Considerations

### Authentication
- Tokens should be cryptographically random (use `bin2hex(random_bytes(32))`)
- Never expose tokens in URLs in logs or error messages
- Consider token expiration and refresh mechanisms
- HTTPS required in production

### Data Validation
- Validate all user inputs (task name, points, member name)
- Sanitize inputs to prevent injection attacks
- Verify token ownership on all operations

### Database
- Use prepared statements with PDO to prevent SQL injection
- Implement proper foreign key constraints
- Regular backups of SQLite file

### API
- CORS headers properly configured
- Rate limiting on endpoints (prevent spam claims)
- Input size limits

---

## Development Considerations

### Frontend
- No build step required (vanilla JavaScript)
- Consider lazy loading for large families
- Responsive design for mobile devices
- Graceful degradation without JavaScript

### Backend
- Single SQLite file easier for deployment
- PHP setup requirement (shared hosting compatible)
- Consider environment variables for configuration
- Logging for debugging

### Testing
- Manual testing checklist
- Test multiple family scenarios
- Test token expiration and reuse
- Test concurrent claims and approvals

---

## Deployment Strategy

### Local Development
1. Server is already set up: Project root is served from http://family-life.localhost/
   Alternatively set up PHP built-in server: `php -S localhost:8000`
2. SQLite file auto-created on first API call
3. Frontend served from same server

### Production
1. Host on PHP-enabled server (Apache/Nginx)
2. SQLite database file with proper permissions
3. HTTPS configuration
4. Regular backups of database file

---

## Future Enhancements

- [ ] User profiles with avatars
- [ ] Custom rank themes
- [ ] Family events and timeline
- [ ] Integration with calendar
- [ ] Mobile notifications
- [ ] Analytics dashboard
- [ ] Export data/reports
- [ ] Multi-family management

