<?php
namespace App\Domain\WorkforcePlanning\Services;

class OverlapDetectionService
{
    public function hasOverlap(
        string $modelClass, 
        string $userColumn, 
        string $userId, 
        string $startColumn, 
        string $endColumn, 
        $newStart, 
        $newEnd, 
        ?string $excludeId = null,
        array $additionalConditions = []
    ): bool {
        $query = $modelClass::where($userColumn, $userId)
            ->where($startColumn, '<=', $newEnd)
            ->where($endColumn, '>=', $newStart);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        foreach ($additionalConditions as $column => $value) {
            $query->where($column, $value);
        }

        return $query->exists();
    }
}