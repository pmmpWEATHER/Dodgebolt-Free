<?php

declare(strict_types=1);

namespace jack\sumo\math;

/**
 * Class Time
 * @package onevsone\math
 */
class Time {

    /**
     * @param int $time
     * @return string
     */
    public static function calculateTime(int $time): string {
        return gmdate("i:s", $time); 
    }
}
