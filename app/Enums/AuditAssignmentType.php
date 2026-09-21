<?php

namespace App\Enums;

enum AuditAssignmentType: string
{
    case LEAD_AUDITOR = 'LEAD_AUDITOR';
    case SUPPORTING_AUDITOR = 'SUPPORTING_AUDITOR';
    case AUDITEE = 'AUDITEE';
}