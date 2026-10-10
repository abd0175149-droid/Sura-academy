<?php

declare(strict_types=1);

use Tests\TestCase;

/*
| Test names start with the requirement ID they prove (constitution Art. 4),
| e.g. it('ATT-07 consumes one credit on attendance'). Infrastructure tests
| without an ID start with the area: "Platform:", "Arch:".
*/

pest()->extend(TestCase::class)->in('Feature', 'Arch');
