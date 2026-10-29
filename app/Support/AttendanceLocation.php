<?php

namespace App\Support;

final class AttendanceLocation
{
    /**
     * Maximum accepted browser-reported GPS uncertainty.
     * This is a measurement-quality threshold, not a distance from the office.
     */
    public const MAX_ACCURACY_METERS = 100;
}
