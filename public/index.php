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
                        <h5 class="mb-0">5. Review Pending Claims</h5>
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
                        <h5 class="mb-0">6. Scoreboard</h5>
                    </div>
                    <div class="card-body">
                        <button id="refreshScoreboardBtn" class="btn btn-primary mb-3">Refresh Scoreboard</button>
                        <ul id="scoreboardList" class="list-group list-group-flush"></ul>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/public/js/app.js" defer></script>
</body>
</html>
