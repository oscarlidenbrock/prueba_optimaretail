<?php

use App\Enums\IncidentStatus;

test('status change availability identifies actionable and final states', function (IncidentStatus $status, bool $expected) {
    expect($status->hasAvailableTransitions())->toBe($expected);
})->with([
    'open' => [IncidentStatus::Open, true],
    'in review' => [IncidentStatus::InReview, true],
    'blocked' => [IncidentStatus::Blocked, false],
    'resolved' => [IncidentStatus::Resolved, false],
]);

test('available destinations come from the transition matrix and never include the current state', function (IncidentStatus $status, array $expected) {
    expect($status->allowedTransitions())->toBe($expected);
    expect($status->allowedTransitions())->not->toContain($status);
})->with([
    'open' => [IncidentStatus::Open, [IncidentStatus::InReview]],
    'in review' => [IncidentStatus::InReview, [IncidentStatus::Blocked, IncidentStatus::Resolved]],
    'blocked' => [IncidentStatus::Blocked, []],
    'resolved' => [IncidentStatus::Resolved, []],
]);
