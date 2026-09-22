<?php

namespace App\Services;

use App\Enums\ReportScope;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProgressPdfService
{
    public function __construct(private ProgressReportService $reports) {}

    public function download(User $user, ReportScope $scope, bool $includeXp = true, bool $includeStreak = true, bool $includeBadges = true, bool $includeLessons = true): Response|StreamedResponse
    {
        $this->ensureFontCache();

        $snap = $this->reports->exportSnapshot($user, $scope);
        $full = $this->reports->fullSnapshot($user, $scope);

        $pdf = Pdf::loadView('pdf.progress-report', [
            'snap' => $snap,
            'full' => $full,
            'includeXp' => $includeXp,
            'includeStreak' => $includeStreak,
            'includeBadges' => $includeBadges,
            'includeLessons' => $includeLessons,
            'fontRegular' => resource_path('fonts/NotoSansGeorgian-Regular.ttf'),
            'fontBold' => resource_path('fonts/NotoSansGeorgian-Bold.ttf'),
        ])->setPaper('a4');

        $filename = 'kidzio-'.$scope->value.'-'.$snap->figures->from.'.pdf';

        return $pdf->download($filename);
    }

    public function html(User $user, ReportScope $scope): string
    {
        $this->ensureFontCache();

        $snap = $this->reports->exportSnapshot($user, $scope);
        $full = $this->reports->fullSnapshot($user, $scope);

        return view('pdf.progress-report', [
            'snap' => $snap,
            'full' => $full,
            'includeXp' => true,
            'includeStreak' => true,
            'includeBadges' => true,
            'includeLessons' => true,
            'fontRegular' => resource_path('fonts/NotoSansGeorgian-Regular.ttf'),
            'fontBold' => resource_path('fonts/NotoSansGeorgian-Bold.ttf'),
        ])->render();
    }

    private function ensureFontCache(): void
    {
        File::ensureDirectoryExists(storage_path('fonts'));
    }
}
