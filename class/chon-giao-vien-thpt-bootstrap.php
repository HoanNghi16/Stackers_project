<?php
declare(strict_types=1);
require_once __DIR__ . '/chon-giao-vien-bootstrap.php';

function phatCreateThptTeacherService(): PhatTeacherSelection
{
    return phatCreateTeacherSelectionForRole('THPT');
}
