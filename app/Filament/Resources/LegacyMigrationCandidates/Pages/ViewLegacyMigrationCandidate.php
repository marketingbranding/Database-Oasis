<?php

namespace App\Filament\Resources\LegacyMigrationCandidates\Pages;

use App\Filament\Resources\LegacyMigrationCandidates\LegacyMigrationCandidateResource;
use App\MigrationReviewDecision;
use App\Models\LegacyMigrationCandidate;
use App\Models\User;
use App\Services\LegacyMigrationReviewService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewLegacyMigrationCandidate extends ViewRecord
{
    protected static string $resource = LegacyMigrationCandidateResource::class;

    protected function getHeaderActions(): array
    {
        $record = $this->getRecord();
        if (! $record instanceof LegacyMigrationCandidate) {
            return [];
        }
        $candidate = $record;
        $reviewService = app(LegacyMigrationReviewService::class);

        return [
            Action::make('reviewCandidate')->label('Review')
                ->icon('heroicon-o-check-badge')
                ->visible(fn (): bool => $candidate->readiness->value === 'REVIEW')
                ->form([
                    Select::make('decision')->label('Keputusan')->options(MigrationReviewDecision::class)->required(),
                    Textarea::make('reason')->label('Alasan')->required(),
                ])
                ->action(function (array $data) use ($candidate, $reviewService): void {
                    $user = User::current() ?? abort(403);
                    $reviewService->review($candidate, $user, MigrationReviewDecision::from($data['decision']), $data['reason']);
                    Notification::make()->title('Review tersimpan')->success()->send();
                }),
            Action::make('resolveBlocker')->label('Resolve Blocker')
                ->icon('heroicon-o-wrench-screwdriver')
                ->visible(fn (): bool => $candidate->readiness->value === 'BLOCKED')
                ->form([
                    TextInput::make('exception_code')->label('Exception Code')->required(),
                    TextInput::make('resolution_type')->label('Resolution Type')->required(),
                    Textarea::make('note')->label('Catatan')->required(),
                ])
                ->action(function (array $data) use ($candidate, $reviewService): void {
                    $user = User::current() ?? abort(403);
                    $reviewService->resolveBlockingException($candidate, $user, $data['exception_code'], $data['resolution_type'], $data['note']);
                    Notification::make()->title('Resolution tersimpan')->success()->send();
                }),
        ];
    }
}
