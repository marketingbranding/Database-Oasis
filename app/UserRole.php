<?php

namespace App;

enum UserRole: string
{
    case SuperAdmin = 'Super Admin';
    case HqAdmin = 'HQ Admin';
    case BranchAdmin = 'Branch Admin';
    case BranchManager = 'Branch Manager';
    case Management = 'Management';
    case Auditor = 'Auditor';
}
