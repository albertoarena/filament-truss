<?php

declare(strict_types=1);

return [
    'navigation_label' => 'Database schema',
    'title' => 'Database schema',

    // A panel user arriving here has usually never heard of Truss, and a
    // diagram with no caption is a picture rather than an answer. This says
    // where it comes from and what it will never contain.
    //
    // It names Laravel Truss outright, because the rest of the page does not:
    // before this the only mention was the label on a link. A page that reads
    // the whole schema should say what is doing the reading, in the line the
    // reader is already on.
    'subheading' => 'The database this panel runs on, read live by Laravel Truss. Structure only: tables, columns, indexes and keys, and never any row data.',

    'documentation_label' => 'Documentation',

    // On a button somewhere else in the panel, so it says where it goes rather
    // than naming the package. "Schema" alone would read as a section of the
    // resource being looked at.
    'focus_action_label' => 'View in schema',
];
