<!DOCTYPE html>
<html lang="ka">
<head>
    <meta charset="utf-8">
    <title>{{ $snap->coverTitle }}</title>
    <style>
        @font-face {
            font-family: 'NotoSansGeorgian';
            font-weight: 400;
            src: url('{{ str_replace('\\', '/', $fontRegular) }}') format('truetype');
        }
        @font-face {
            font-family: 'NotoSansGeorgian';
            font-weight: 700;
            src: url('{{ str_replace('\\', '/', $fontBold) }}') format('truetype');
        }
        body { font-family: 'NotoSansGeorgian', DejaVu Sans, sans-serif; color: #1B1240; font-size: 14px; }
        h1 { font-size: 28px; margin: 0 0 8px; }
        h2 { font-size: 16px; margin: 24px 0 8px; }
        .kicker { font-size: 11px; letter-spacing: 0.12em; color: #5B5178; }
        .meta { color: #5B5178; margin-bottom: 24px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 8px 0; border-bottom: 1px solid #EEE8F8; }
        .num { text-align: right; font-weight: 700; }
        .hint { font-size: 12px; color: #5B5178; margin-top: 16px; }
    </style>
</head>
<body>
    <p class="kicker">{{ $snap->coverKicker }}</p>
    <h1>{{ $snap->coverTitle }}</h1>
    <p class="meta">{{ $snap->coverMeta }} · {{ $snap->rangeLabel }}</p>

    @if ($includeXp)
        <h2>{{ __('reports.xp_earned') }}</h2>
        <table>
            <tr><td>{{ __('reports.xp_earned') }}</td><td class="num">{{ number_format($snap->figures->xp) }}</td></tr>
            <tr><td>{{ __('reports.active_days') }}</td><td class="num">{{ $snap->figures->activeDays }}</td></tr>
            @if ($snap->figures->hasPrevious)
                <tr><td>{{ __('monthly-goals.vs_prev_month') }}</td><td class="num">{{ ($snap->figures->vsPreviousPercent >= 0 ? '+' : '').$snap->figures->vsPreviousPercent }}%</td></tr>
            @endif
        </table>
    @endif

    @if ($includeLessons)
        <h2>{{ __('reports.lessons') }}</h2>
        <table>
            <tr><td>{{ __('reports.lessons') }}</td><td class="num">{{ $snap->figures->packs }}</td></tr>
            @if ($snap->figures->accuracyPercent !== null)
                <tr><td>{{ __('reports.accuracy') }}</td><td class="num">{{ $snap->figures->accuracyPercent }}%</td></tr>
            @endif
            @foreach ($full->subjects as $row)
                <tr>
                    <td>{{ $row->label }}</td>
                    <td class="num">{{ $row->packs }} · {{ $row->masteryPercent }}%</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if ($includeStreak)
        <h2>{{ __('reports.kpi_streak') }}</h2>
        <table>
            <tr><td>{{ __('reports.active_days') }}</td><td class="num">{{ $snap->figures->activeDays }} / {{ $snap->figures->spanDays }}</td></tr>
            @if ($snap->figures->minutesTracked && $snap->figures->minutes !== null)
                <tr><td>{{ __('parent-zone.time_learning') }}</td><td class="num">{{ $snap->figures->minutes }} {{ __('reports.kpi_min') }}</td></tr>
            @endif
        </table>
    @endif

    @if ($includeBadges)
        <h2>{{ __('reports.kpi_badges') }}</h2>
        <table>
            <tr><td>{{ __('reports.kpi_badges_delta', ['n' => $snap->figures->badgesEarned]) }}</td><td class="num">{{ $snap->figures->badgesEarned }}</td></tr>
            @foreach ($full->timeline as $event)
                <tr><td>{{ $event->title }}</td><td class="num">{{ $event->subtitle }}</td></tr>
            @endforeach
        </table>
    @endif

    <p class="hint">{{ $full->minutesHint }}</p>
    <p class="hint">{{ __('reports.export_blurb') }}</p>
</body>
</html>
