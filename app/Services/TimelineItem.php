<?php

namespace App\Services;

use Carbon\CarbonInterface;

/**
 * Read-only presentation item for the unified Sales Case timeline.
 * Never persisted — derived from existing transactional records only.
 */
final class TimelineItem
{
    /**
     * @param  array<int, string>  $descriptionLines
     */
    public function __construct(
        public readonly CarbonInterface $date,
        public readonly string $title,
        public readonly array $descriptionLines = [],
        public readonly ?string $status = null,
        public readonly string $tone = 'primary',
        public readonly ?string $groupLabel = null,
        public readonly ?string $actor = null,
        public readonly string $sourceType = 'sales_case',
        public readonly ?string $sourceId = null,
    ) {}

    /**
     * @return array{date: string, title: string, description: string, status: string|null, tone: string, group_label: string|null, actor: string|null, source_type: string, source_id: string|null}
     */
    public function toArray(): array
    {
        return [
            'date' => $this->date->format('d M Y'),
            'title' => $this->title,
            'description' => implode("\n", $this->descriptionLines),
            'status' => $this->status,
            'tone' => $this->tone,
            'group_label' => $this->groupLabel,
            'actor' => $this->actor,
            'source_type' => $this->sourceType,
            'source_id' => $this->sourceId,
        ];
    }
}
