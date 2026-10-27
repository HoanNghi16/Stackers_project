<?php
/** Columns and tables verified against the shared Stackers MySQL schema. */
return [
    'teacher_table' => 'giaovien',
    'teacher_id' => 'magiaovien',
    'teacher_user_fk' => 'manguoidung',
    'teacher_subject_fk' => 'mamonhoc',
    'teacher_school_candidates' => ['matruong'],
    'nomination_column' => 'duocdecu',
    'assigned_tables' => [
        'phancongchamthi',
        'phancongphuckhao',
        'phanconggacthi',
        'phancongrade',
    ],
];
