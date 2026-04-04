<?php

namespace App\Enums;

enum ProductStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case ClarificationNeeded = 'clarification_needed';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Completed = 'completed';
}
