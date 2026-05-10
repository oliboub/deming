@extends("layout")

@section("content")
<div data-role="panel"
    data-title-caption='{{ trans("cruds.risk.matrix") }}'
    data-collapsible="false"
    data-title-icon="<span class='mif-grid'></span>">

    {{-- Filtres ----------------------------------------------------------------}}
    <div class="grid mb-4">
        <div class="row">

            {{-- Statut --}}
            <div class="cell-lg-2 cell-md-3">
                <select id="filter-status" data-role="select">
                    <option value="none">-- {{ trans("cruds.risk.fields.choose_status") }} --</option>
                    @foreach (\App\Models\Risk::STATUS_LABELS as $value => $label)
                        <option value="{{ $value }}"
                                @if(($filters['status'] ?? '') === $value) selected @endif>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Propriétaire --}}
            @if (Auth::user()->role !== 3)
            <div class="cell-lg-2 cell-md-3">
                <select id="filter-owner" data-role="select">
                    <option value="none">-- {{ trans("cruds.risk.fields.choose_owner") }} --</option>
                    @foreach ($owners as $owner)
                        <option value="{{ $owner->id }}"
                                @if(($filters['owner'] ?? '') == $owner->id) selected @endif>
                            {{ $owner->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- En retard --}}
            <div class="cell-lg-2 cell-md-3 mt-2">
                <input type="radio" data-role="radio"
                       data-append="{{ trans('cruds.risk.fields.overdue_all') }}"
                       value="0" id="overdue0"
                       {{ ($filters['overdue'] ?? '0') === '0' ? 'checked' : '' }}>
                <input type="radio" data-role="radio"
                       data-append="{{ trans('cruds.risk.fields.overdue_only') }}"
                       value="1" id="overdue1"
                       {{ ($filters['overdue'] ?? '0') === '1' ? 'checked' : '' }}>
            </div>

        </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", () => {
        let ready = false;
        window.addEventListener('load', () => {
            requestAnimationFrame(() => requestAnimationFrame(() => { ready = true; }));
        });

        const getParam = (k) => new URLSearchParams(location.search).get(k) ?? '';

        function navigate(patch = {}) {
            const next = new URL('/risk/matrix', location.origin);
            const all  = {
                status  : document.getElementById('filter-status')?.value ?? getParam('status'),
                owner   : document.getElementById('filter-owner')?.value  ?? getParam('owner'),
                overdue : document.getElementById('overdue1')?.checked ? '1' : '0',
                ...patch,
            };
            for (const [k, v] of Object.entries(all)) {
                if (!v || v === 'none') next.searchParams.delete(k);
                else next.searchParams.set(k, v);
            }
            if (next.toString() !== location.href) location.assign(next.toString());
        }

        const bindChange = (id, key) => {
            const el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('change', () => { if (ready) navigate({ [key]: el.value }); });
        };

        bindChange('filter-status', 'status');
        bindChange('filter-owner',  'owner');

        ['overdue0', 'overdue1'].forEach((id, i) => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('change', () => {
                if (ready) navigate({ overdue: String(i) });
            });
        });
    });
    </script>
    {{-- Fin filtres ------------------------------------------------------------}}

    <div class="grid">
    {{-- Matrice --}}
    <div class="row">
        <div class="cell-lg-10 cell-md-12">
            <div class="overflow-auto">
            <table class="table border text-center" style="table-layout:fixed; min-width:400px">
                <thead>
                    <tr>
                        <th style="width:140px"></th>
                        @foreach ($xAxis as $impact)
                        <th>
                            {{ trans('cruds.risk.fields.impact') }} {{ $impact['value'] }}
                            <br><small class="text-muted">{{ $impact['label'] }}</small>
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                @foreach (array_reverse($yAxis) as $yLevel)
                <tr>
                    <th class="text-right" style="font-size:.85rem">
                        @if ($scoringConfig->usesLikelihood())
                            {{ trans('cruds.risk.fields.likelihood') }} {{ $yLevel['value'] }}
                        @else
                            {{ trans('cruds.risk.fields.probability') }} {{ $yLevel['value'] }}
                            <br><small class="text-muted">{{ $yLevel['label'] }}</small>
                        @endif
                    </th>
                    @foreach ($xAxis as $impact)
                        @php
                            $cell      = $matrix[$yLevel['value']][$impact['value']] ?? [];
                            $count     = count($cell);
                            $score     = $yLevel['value'] * $impact['value'];
                            $threshold = $scoringConfig->thresholdFor($score);
                            $thresholdIndex = $scoringConfig->thresholdIndexFor($score);
                            $bgColor   = $threshold['color'];
                            $txtColor  = '#fff';

                            @endphp
                        <td style="background:{{ $bgColor }};color:{{ $txtColor }};padding:10px;vertical-align:middle;{{ $count > 0 ? 'cursor:pointer' : '' }}"
                            @if($count > 0)
                                onclick="location.href='/risk/index?threshold={{ $thresholdIndex }}'"
                                data-role="hint"
                                data-hint-position="top"
                                data-hint-text="{{ collect($cell)->pluck('name')->take(5)->implode(', ') }}{{ $count > 5 ? ' …' : '' }}"
                            @endif>
                            @if ($count > 0)
                                <div style="font-size:1.5rem;font-weight:700;line-height:1">{{ $count }}</div>
                                <small>{{ $count === 1 ? trans('cruds.risk.singular') : trans('cruds.risk.plural') }}</small>
                            @endif
                        </td>
                    @endforeach
                </tr>
                @endforeach
                </tbody>
            </table>
            </div>

            {{-- Légende
            <div class="mt-2 d-flex gap-2">
                @foreach ($scoringConfig->risk_thresholds as $i => $t)
                    @php
                        $prevMax = $i > 0 ? $scoringConfig->risk_thresholds[$i-1]['max'] + 1 : 1;
                    @endphp
                    <a href="/risk/index?threshold={{ $i }}" class="no-underline">
                        <span class="badge"
                              style="background:{{ $t['color'] }};color:#fff;padding:4px 10px;pointer-events:none">
                        {{ $t['label'] }}
                        @if ($t['max']) {{ $prevMax }}–{{ $t['max'] }}
                        @else &gt; {{ $scoringConfig->risk_thresholds[$i-1]['max'] ?? 0 }} @endif
                    </span>
                    </a>
                @endforeach
            </div>
            --}}
        </div>

        {{-- Répartition par statut --}}
        <div class="cell-lg-2 cell-md-12" style="font-size:1rem">
            <table class="table compact border mt-2">
            <tr>
                <td colspan=3>
            <strong>{{ trans('cruds.risk.fields.by_risks') }}</strong>
            </td>
            </tr>
            @foreach ($scoringConfig->risk_thresholds as $i => $t)
            @php
                $prevMax = $i > 0 ? $scoringConfig->risk_thresholds[$i-1]['max'] + 1 : 1;
            @endphp
            <tr>
                <td>
                    @if(($stats['by_level'][$i] ?? 0) > 0)
                    <a href="/risk/index?threshold={{ $i }}" class="no-underline">
                        <span class="mif-chevron-right"></span>
                    </a>
                    @endif
                </td>
                <td class="text-right">
                    <b>{{ $stats['by_level'][$i] ?? 0 }}</b>
                </td>
                <td class="text-left">
                    <a href="/risk/index?threshold={{ $i }}" class="no-underline">
                        <span class="badge"
                              style="background:{{ $t['color'] }};color:#fff;padding:4px 10px;pointer-events:none">
                            {{ $t['label'] }}
                        </span>
                    </a>
                    @if ($t['max']) {{ $prevMax }}–{{ $t['max'] }}
                    @else &gt; {{ $scoringConfig->risk_thresholds[$i-1]['max'] ?? 0 }} @endif
                </td>
            </tr>
            @endforeach

                <tr>
                    <td>
                        @if($stats['total'] > 0)
                        <a href="/risk/index" class="no-underline">
                            <span class="mif-chevron-right"></span>
                        </a>
                        @endif
                    </td>
                    <td class="text-right">
                        <b>{{ $stats['total'] }}</b>
                    </td>
                    <td class="text-left">
                        <a href="/risk/index" class="no-underline">
                        <b>{{ trans('cruds.risk.fields.total') }}</b>
                        </a>
                    </td>
                </tr>
            <tr>
                <td colspan="3">
                    <strong>{{ trans('cruds.risk.fields.by_status') }}</strong>
                </td>
            </tr>
                @foreach (\App\Models\Risk::STATUS_LABELS as $status => $label)
                <tr>
                    <td>
                        @if(($stats['by_status'][$status] ?? 0) > 0)
                        <a href="/risk/index?status={{$status}}" class="no-underline">
                                <span class="mif-chevron-right"></span>
                            </a>
                        @endif
                    </td>
                    <td class="text-right">
                        <b>{{ $stats['by_status'][$status] ?? 0 }}</b>
                    </td>
                    <td class="text-left">
                        <a href="/risk/index?status={{$status}}" class="no-underline">
                            <span class="badge  {{ \App\Models\Risk::STATUS_COLORS[$status] }}"
                                  style="padding:4px 10px;pointer-events:none">
                                {{ $label }}
                            </span>
                        </a>
                    </td>
                </tr>
                @endforeach
            </table>
        </div>
    </div>

    {{-- Navigation
    <div class="row mt-4">
        <div class="cell-12">
            <a class="button" href="/risk/index">
                <span class="mif-cancel"></span>
                &nbsp;{{ trans("common.cancel") }}
            </a>
        </div>
    </div>
    --}}

</div>
</div>
@endsection