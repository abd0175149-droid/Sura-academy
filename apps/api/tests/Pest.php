<?php

declare(strict_types=1);

use Tests\TestCase;

/*
| Test names start with the requirement ID they prove (constitution Art. 4),
| e.g. it('<REQ-ID> <behaviour>'). Infrastructure tests
| without an ID start with the area: "Platform:", "Arch:".
*/

pest()->extend(TestCase::class)->in('Feature', 'Arch');
