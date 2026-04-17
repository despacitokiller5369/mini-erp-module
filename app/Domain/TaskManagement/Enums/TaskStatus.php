<?php

namespace App\Domain\TaskManagement\Enums;

enum TaskStatus: string
{
    case Todo = 'todo';
    case InProgress = 'inprogress';
    case InReview = 'inreview';
    case Done = 'done';

    public function canTransitionTo(TaskStatus $newStatus): bool
    {
        $order = [
            self::Todo->value => 0,
            self::InProgress->value => 1,
            self::InReview->value => 2,
            self::Done->value => 3,
        ];

        return $order[$newStatus->value] > $order[$this->value];
    }
}
