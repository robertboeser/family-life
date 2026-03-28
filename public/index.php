<?php

declare(strict_types=1);
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Family Life MVP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/public/css/styles.css">
</head>
<body>
    <nav class="navbar navbar-light bg-white shadow-sm mb-4">
        <div class="container">
            <span class="navbar-brand">🏡 Family Life</span>
            <div id="authStatus">No member token selected</div>
        </div>
    </nav>

    <main class="container">
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">1. Family Setup</h5>
                    </div>
                    <div class="card-body">
                        <form id="createFamilyForm" class="mb-3">
                            <div class="input-group">
                                <input id="familyName" type="text" class="form-control" placeholder="Family name" required>
                                <button class="btn btn-primary" type="submit">Create Family</button>
                            </div>
                        </form>

                        <form id="addMemberForm" class="mb-3">
                            <div class="row g-2">
                                <div class="col-auto">
                                    <input id="memberFamilyId" type="number" min="1" class="form-control" placeholder="Family ID" style="width: 100px;" required>
                                </div>
                                <div class="col">
                                    <input id="memberName" type="text" class="form-control" placeholder="Member name" required>
                                </div>
                                <div class="col-auto">
                                    <button class="btn btn-primary" type="submit">Add Member</button>
                                </div>
                            </div>
                        </form>

                        <div class="alert alert-info mb-2" role="alert">
                            <small><strong>Token URL:</strong> <span id="tokenUrlPreview">/?token=...</span></small>
                        </div>
                        <pre id="setupOutput" class="output mb-0"></pre>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">2. Select Session Token</h5>
                    </div>
                    <div class="card-body">
                        <form id="setTokenForm" class="mb-3">
                            <div class="input-group">
                                <input id="sessionToken" type="text" class="form-control" placeholder="Paste auth token" required>
                                <button class="btn btn-primary" type="submit">Use Token</button>
                                <button class="btn btn-outline-secondary" type="button" id="clearTokenBtn">Clear</button>
                            </div>
                        </form>
                        <pre id="meOutput" class="output mb-0"></pre>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">3. Tasks</h5>
                    </div>
                    <div class="card-body">
                        <form id="createTaskForm" class="mb-3">
                            <div class="row g-2">
                                <div class="col">
                                    <input id="taskName" type="text" class="form-control" placeholder="Task name" required>
                                </div>
                                <div class="col-auto">
                                    <input id="taskPoints" type="number" min="1" class="form-control" placeholder="Pts" style="width: 80px;" required>
                                </div>
                                <div class="col-auto">
                                    <button class="btn btn-primary" type="submit">Create</button>
                                </div>
                            </div>
                        </form>

                        <form id="deleteTaskForm" class="mb-3">
                            <div class="row g-2">
                                <div class="col">
                                    <input id="deleteTaskId" type="number" min="1" class="form-control" placeholder="Task ID to delete" required>
                                </div>
                                <div class="col-auto">
                                    <button class="btn btn-outline-danger" type="submit">Delete</button>
                                </div>
                            </div>
                        </form>

                        <ul id="tasksList" class="list-group list-group-flush"></ul>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">4. Claims</h5>
                    </div>
                    <div class="card-body">
                        <form id="claimTaskForm" class="mb-3">
                            <div class="row g-2">
                                <div class="col">
                                    <input id="claimTaskId" type="number" min="1" class="form-control" placeholder="Task ID" required>
                                </div>
                                <div class="col-auto">
                                    <button class="btn btn-primary" type="submit">Claim</button>
                                </div>
                            </div>
                        </form>

                        <div class="mb-3 d-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary" id="refreshClaimsBtn">Refresh</button>
                            <select id="claimFilter" class="form-select form-select-sm" style="flex: 0 0 auto;">
                                <option value="all">all</option>
                                <option value="pending">pending</option>
                                <option value="approved">approved</option>
                                <option value="rejected">rejected</option>
                            </select>
                        </div>

                        <ul id="claimsList" class="list-group list-group-flush"></ul>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">5. My Claim History</h5>
                    </div>
                    <div class="card-body">
                        <button id="refreshMyClaimsBtn" class="btn btn-sm btn-outline-primary mb-3">Refresh My Claims</button>
                        <ul id="myClaimsList" class="list-group list-group-flush"></ul>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">6. Review Pending Claims</h5>
                    </div>
                    <div class="card-body">
                        <form id="reviewForm" class="mb-3">
                            <div class="row g-2">
                                <div class="col-auto">
                                    <input id="reviewClaimId" type="number" min="1" class="form-control" placeholder="Claim ID" style="width: 90px;" required>
                                </div>
                                <div class="col-auto">
                                    <select id="reviewAction" class="form-select">
                                        <option value="approve">approve</option>
                                        <option value="reject">reject</option>
                                    </select>
                                </div>
                                <div class="col-auto">
                                    <button class="btn btn-primary" type="submit">Submit</button>
                                </div>
                            </div>
                        </form>
                        <pre id="reviewOutput" class="output mb-0"></pre>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">7. Scoreboard</h5>
                    </div>
                    <div class="card-body">
                        <button id="refreshScoreboardBtn" class="btn btn-primary mb-3">Refresh Scoreboard</button>
                        <ul id="scoreboardList" class="list-group list-group-flush"></ul>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">8. Voting Round</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex gap-2 flex-wrap mb-3">
                            <button id="createRoundBtn" class="btn btn-primary" type="button">Create Round</button>
                            <button id="refreshVotingBtn" class="btn btn-outline-primary" type="button">Refresh Voting</button>
                        </div>

                        <pre id="votingRoundOutput" class="output mb-3"></pre>

                        <div class="row g-3">
                            <div class="col-lg-4">
                                <form id="createWishForm" class="mb-3">
                                    <label for="wishName" class="form-label">Create Wish</label>
                                    <div class="input-group">
                                        <input id="wishName" type="text" class="form-control" placeholder="Wish name" required>
                                        <button class="btn btn-primary" type="submit">Add</button>
                                    </div>
                                </form>
                            </div>

                            <div class="col-lg-4">
                                <form id="voteWishForm" class="mb-3">
                                    <label class="form-label">Vote on Wish</label>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <input id="voteWishId" type="number" min="1" class="form-control" placeholder="Wish ID" required>
                                        </div>
                                        <div class="col-6">
                                            <input id="voteAmount" type="number" min="1" class="form-control" placeholder="Amount" required>
                                        </div>
                                    </div>
                                    <button class="btn btn-primary mt-2" type="submit">Place Vote</button>
                                </form>
                            </div>

                            <div class="col-lg-4">
                                <form id="closeRoundForm" class="mb-3">
                                    <label class="form-label">Approve Round Closure</label>
                                    <div class="input-group">
                                        <input id="closeRoundId" type="number" min="1" class="form-control" placeholder="Round ID" required>
                                        <button class="btn btn-outline-primary" type="submit">Approve Close</button>
                                    </div>
                                </form>

                                <form id="roundResultForm">
                                    <label class="form-label">Get Round Result</label>
                                    <div class="input-group">
                                        <input id="resultRoundId" type="number" min="1" class="form-control" placeholder="Round ID" required>
                                        <button class="btn btn-outline-secondary" type="submit">Result</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-lg-6">
                                <h6>Active Wishes</h6>
                                <ul id="wishesList" class="list-group list-group-flush"></ul>
                            </div>
                            <div class="col-lg-6">
                                <h6>Voting Actions Output</h6>
                                <pre id="votingActionOutput" class="output mb-0"></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/public/js/app.js" defer></script>
</body>
</html>
