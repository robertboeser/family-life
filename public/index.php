<?php

declare(strict_types=1);
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Family Life</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm mb-4">
        <div class="container">
            <span class="navbar-brand fw-bold">🏡 Family Life</span>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-page="setup">Setup</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-page="tasks">Tasks</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-page="claims">Claims</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-page="approvals">Approvals</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-page="voting">Voting</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-page="scoreboard">Scoreboard</a>
                    </li>
                </ul>
            </div>
            <div id="authStatus" class="ms-3 text-end" style="min-width: 200px;">
                <small class="text-muted">No member token selected</small>
            </div>
        </div>
    </nav>

        <!-- SETUP PAGE -->
        <div id="page-setup" class="page-section">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Family Setup</h5>
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
                                        <input id="memberFamilyId" type="text" class="form-control" placeholder="Family ID" style="width: 150px;" required>
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
                                <small><strong>Token URL:</strong></small>
                                <div id="tokenUrlPreview" class="font-monospace text-break" style="font-size: 0.85rem;">/?token=...</div>
                            </div>
                            <pre id="setupOutput" class="output mb-0"></pre>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Select Session Token</h5>
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
            </div>
        </div>

        <!-- TASKS PAGE -->
        <div id="page-tasks" class="page-section d-none">
            <div class="row g-4">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Tasks</h5>
                            <button class="btn btn-sm btn-outline-primary" id="refreshTasksBtn">Refresh</button>
                        </div>
                        <div class="card-body">
                            <div class="row g-3 mb-4">
                                <div class="col-md">
                                    <form id="createTaskForm">
                                        <h6 class="mb-3">Create New Task</h6>
                                        <div class="row g-2">
                                            <div class="col" style="min-width: 200px;">
                                                <input id="taskName" type="text" class="form-control" placeholder="Task name" required>
                                            </div>
                                            <div class="col-auto" style="min-width: 120px;">
                                                <input id="taskPoints" type="number" min="1" class="form-control" placeholder="Points" required>
                                            </div>
                                            <div class="col-auto">
                                                <button class="btn btn-primary" type="submit">Create</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md">
                                    <form id="deleteTaskForm">
                                        <h6 class="mb-3">Delete Task (Creator Only)</h6>
                                        <div class="row g-2">
                                            <div class="col-auto" style="min-width: 150px;">
                                                <input id="deleteTaskId" type="number" min="1" class="form-control" placeholder="Task ID" required>
                                            </div>
                                            <div class="col-auto">
                                                <button class="btn btn-outline-danger" type="submit">Delete</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Available Tasks</h5>
                        </div>
                        <div class="card-body">
                            <form id="claimTaskForm" class="mb-4 pb-3 border-bottom">
                                <h6 class="mb-3">Claim a Task</h6>
                                <div class="row g-2">
                                    <div class="col-auto" style="min-width: 150px;">
                                        <input id="claimTaskId" type="number" min="1" class="form-control" placeholder="Task ID" required>
                                    </div>
                                    <div class="col-auto">
                                        <button class="btn btn-success" type="submit">Claim Task</button>
                                    </div>
                                </div>
                            </form>

                            <div id="tasksListContainer"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CLAIMS PAGE -->
        <div id="page-claims" class="page-section d-none">
            <div class="row g-4">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">All Claims</h5>
                            <div class="d-flex gap-2">
                                <select id="claimFilter" class="form-select form-select-sm" style="flex: 0 0 auto; width: auto;">
                                    <option value="all">All Claims</option>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                                <button class="btn btn-sm btn-outline-primary" id="refreshClaimsBtn">Refresh</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="claimsListContainer"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- APPROVALS PAGE (My Claims) -->
        <div id="page-approvals" class="page-section d-none">
            <div class="row g-4">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">My Claims & History</h5>
                        </div>
                        <div class="card-body">
                            <button class="btn btn-outlined-primary mb-3" id="refreshMyClaimsBtn">Refresh Claims</button>
                            <div id="myClaimsListContainer"></div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Review Pending Claims</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-3"><small>Approve or reject pending claims to update scores.</small></p>
                            <form id="reviewForm" class="mb-3">
                                <div class="row g-2 align-items-end">
                                    <div class="col-auto" style="min-width: 130px;">
                                        <label class="form-label">Claim ID</label>
                                        <input id="reviewClaimId" type="number" min="1" class="form-control" placeholder="Claim ID" required>
                                    </div>
                                    <div class="col-auto" style="min-width: 140px;">
                                        <label class="form-label">Action</label>
                                        <select id="reviewAction" class="form-select">
                                            <option value="approve">Approve</option>
                                            <option value="reject">Reject</option>
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
            </div>
        </div>

        <!-- VOTING PAGE -->
        <div id="page-voting" class="page-section d-none">
            <div class="row g-4">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Voting Round</h5>
                            <div class="d-flex gap-2">
                                <button id="createRoundBtn" class="btn btn-sm btn-primary">Create Round</button>
                                <button id="refreshVotingBtn" class="btn btn-sm btn-outline-primary">Refresh</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <pre id="votingRoundOutput" class="output mb-0"></pre>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Create Wish</h5>
                        </div>
                        <div class="card-body">
                            <form id="createWishForm">
                                <div class="input-group">
                                    <input id="wishName" type="text" class="form-control" placeholder="Wish name" required>
                                    <button class="btn btn-primary" type="submit">Add Wish</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Vote on Wish</h5>
                        </div>
                        <div class="card-body">
                            <form id="voteWishForm">
                                <div class="row g-2">
                                    <div class="col">
                                        <input id="voteWishId" type="number" min="1" class="form-control" placeholder="Wish ID" required>
                                    </div>
                                    <div class="col-auto">
                                        <input id="voteAmount" type="number" min="1" class="form-control" placeholder="Amount" style="width: 100px;" required>
                                    </div>
                                    <div class="col-auto">
                                        <button class="btn btn-primary" type="submit">Vote</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Active Wishes</h5>
                        </div>
                        <div class="card-body">
                            <div id="wishesListContainer"></div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Round Closure</h5>
                        </div>
                        <div class="card-body">
                            <form id="closeRoundForm" class="mb-3 pb-3 border-bottom">
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
                </div>

                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Actions Output</h5>
                        </div>
                        <div class="card-body">
                            <pre id="votingActionOutput" class="output mb-0"></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SCOREBOARD PAGE -->
        <div id="page-scoreboard" class="page-section d-none">
            <div class="row g-4">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Family Scoreboard</h5>
                            <button id="refreshScoreboardBtn" class="btn btn-sm btn-outline-primary">Refresh</button>
                        </div>
                        <div class="card-body">
                            <div id="scoreboardContainer"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/js/app.js" defer></script>
</body>
</html>
