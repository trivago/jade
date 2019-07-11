<?php

namespace App\Value;

class TaskStatuses
{
    const OPEN = 1;
    const IN_PROGRESS = 2;
    const STOPPED = 3;
    const DONE = 4;
    
    const MAPPING = [
        self::OPEN => [
            self::IN_PROGRESS,
        ],
        self::IN_PROGRESS => [
            self::STOPPED,
            self::DONE,
        ],
        self::STOPPED => [
            self::IN_PROGRESS,
        ],
    ];

    public static function validate($currentStatus, $newStatus)
    {
        if (!array_key_exists(!$currentStatus, self::MAPPING)) {
            throw new \InvalidArgumentException('Invalid status provided');
        }

        if (!in_array($newStatus, self::MAPPING[$currentStatus])) {
            throw new \InvalidArgumentException(sprintf('Can not move from %s to %s', $currentStatus, $newStatus));
        }
    }
}