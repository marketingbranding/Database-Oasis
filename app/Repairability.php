<?php

namespace App;

enum Repairability: string
{
    case AutoFixable = 'AUTO_FIXABLE';
    case ReviewRequired = 'REVIEW_REQUIRED';
    case ManualDecisionRequired = 'MANUAL_DECISION_REQUIRED';
}
