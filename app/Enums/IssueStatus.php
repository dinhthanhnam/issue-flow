<?php

namespace App\Enums;

enum IssueStatus: string
{
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Done = 'done';
}
