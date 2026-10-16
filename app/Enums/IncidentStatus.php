<?php

namespace App\Enums;

enum IncidentStatus: string
{
    case Open = 'open';
    case InReview = 'in_review';
    case Blocked = 'blocked';
    case Resolved = 'resolved';

    public function canTransitionTo(self $newStatus): bool
    {
        return match ($this) {
            self::Open => $newStatus === self::InReview,
            self::InReview => in_array($newStatus, [self::Blocked, self::Resolved], true),
            self::Blocked, self::Resolved => false,
        };
    }
}
