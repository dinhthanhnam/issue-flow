<?php

namespace App\Enums;

enum ProjectRole: string
{
    case Lead = 'lead';
    case Member = 'member';
    case Viewer = 'viewer';
}
