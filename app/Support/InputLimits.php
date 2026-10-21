<?php

namespace App\Support;

final class InputLimits
{
    public const TITLE = 160;

    public const DESCRIPTION = 2000;

    public const SEARCH = 100;

    public const CITY = 80;

    public const DUE_MIN = '1990-01-01';

    public const DUE_MAX = '2100-12-31';

    public const PAGE_MAX = 1000;

    public const PER_PAGE_MAX = 50;
}
