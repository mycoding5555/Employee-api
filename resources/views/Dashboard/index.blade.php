@extends('layout.main')

@section('title', 'ផ្ទាំងគ្រប់គ្រង')
@section('page-title', 'ផ្ទាំងគ្រប់គ្រង')
@section('page-subtitle', 'ទិដ្ឋភាពទូទៅនៃប្រព័ន្ធគ្រប់គ្រងបុគ្គលិក')

@section('content')
<div class="dash">

    {{-- ═══ Stat Cards ═══ --}}
    <div class="row g-3 mb-4 mt-1">
        <div class="col-6 col-lg-3">
            <div class="stat-card stat-card--indigo">
                <div class="stat-card__icon"><i class="bi bi-people-fill"></i></div>
                <div class="stat-card__body">
                    <span class="stat-card__num" id="stat-total">—</span>
                    <span class="stat-card__lbl">មន្រ្តីរាជការសរុប</span>
                </div>
                <div class="stat-card__wave"></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card stat-card--emerald">
                <div class="stat-card__icon"><i class="bi bi-building"></i></div>
                <div class="stat-card__body">
                    <span class="stat-card__num" id="stat-departments">—</span>
                    <span class="stat-card__lbl">អគ្គនាយកដ្ឋាន</span>
                </div>
                <div class="stat-card__wave"></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card stat-card--sky">
                <div class="stat-card__icon"><i class="bi bi-diagram-3-fill"></i></div>
                <div class="stat-card__body">
                    <span class="stat-card__num" id="stat-child-departments">—</span>
                    <span class="stat-card__lbl">នាយកដ្ឋានសរុប</span>
                </div>
                <div class="stat-card__wave"></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card stat-card--amber">
                <div class="stat-card__icon"><i class="bi bi-person-vcard-fill"></i></div>
                <div class="stat-card__body">
                    <span class="stat-card__num" id="stat-id-card">—</span>
                    <span class="stat-card__lbl">មានអត្តសញ្ញាណប័ណ្ណ</span>
                </div>
                <div class="stat-card__wave"></div>
            </div>
        </div>
    </div>

    {{-- ═══ Charts ═══ --}}
    <div class="row g-3">
        <div class="col-12 col-md-4">
            <div class="chart-card">
                <div class="chart-card__header">
                    <div class="chart-card__dot chart-card__dot--indigo"></div>
                    <span class="chart-card__title">សមាមាត្រភេទ</span>
                </div>
                <div class="chart-card__body">
                    <div class="chart-wrap">
                        <canvas id="genderChart"></canvas>
                        <div class="chart-center" id="genderCenter">
                            <span class="chart-center__num">—</span>
                            <span class="chart-center__lbl">សរុប</span>
                        </div>
                    </div>
                </div>
                <div class="chart-card__legend" id="genderLegend"></div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="chart-card">
                <div class="chart-card__header">
                    <div class="chart-card__dot chart-card__dot--emerald"></div>
                    <span class="chart-card__title">សមាមាត្រមាន/គ្មានរូបថត</span>
                </div>
                <div class="chart-card__body">
                    <div class="chart-wrap">
                        <canvas id="photoChart"></canvas>
                        <div class="chart-center" id="photoCenter">
                            <span class="chart-center__num">—</span>
                            <span class="chart-center__lbl">សរុប</span>
                        </div>
                    </div>
                </div>
                <div class="chart-card__legend" id="photoLegend"></div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="chart-card">
                <div class="chart-card__header">
                    <div class="chart-card__dot chart-card__dot--sky"></div>
                    <span class="chart-card__title">សមាមាត្រអត្តសញ្ញាណប័ណ្ណ</span>
                </div>
                <div class="chart-card__body">
                    <div class="chart-wrap">
                        <canvas id="idCardChart"></canvas>
                        <div class="chart-center" id="idCardCenter">
                            <span class="chart-center__num">—</span>
                            <span class="chart-center__lbl">សរុប</span>
                        </div>
                    </div>
                </div>
                <div class="chart-card__legend" id="idCardLegend"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.dash { max-width: 1200px; margin: 0 auto; }

/* ═══════════ Stat Cards ═══════════ */
.stat-card {
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    gap: .9rem;
    border-radius: 16px;
    padding: 1.3rem 1.4rem;
    color: #fff;
    min-height: 100px;
    transition: transform .2s ease, box-shadow .2s ease;
}
.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 28px rgba(0,0,0,.15);
}
.stat-card__icon {
    width: 52px; height: 52px; min-width: 52px;
    border-radius: 14px;
    background: rgba(255,255,255,.2);
    backdrop-filter: blur(4px);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem;
    color: #fff;
}
.stat-card__body { display: flex; flex-direction: column; min-width: 0; z-index: 1; }
.stat-card__num {
    font-size: 1.75rem;
    font-weight: 800;
    color: #fff;
    line-height: 1.1;
    letter-spacing: -.02em;
}
.stat-card__lbl {
    font-size: .75rem;
    color: rgba(255,255,255,.85);
    font-weight: 500;
    margin-top: 2px;
    letter-spacing: .02em;
}
.stat-card__wave {
    position: absolute;
    right: -20px; bottom: -20px;
    width: 120px; height: 120px;
    border-radius: 50%;
    background: rgba(255,255,255,.08);
}
.stat-card__wave::after {
    content: '';
    position: absolute;
    right: -10px; bottom: -10px;
    width: 80px; height: 80px;
    border-radius: 50%;
    background: rgba(255,255,255,.06);
}

/* Gradient variants */
.stat-card--indigo  { background: linear-gradient(135deg, #667eea 0%, #4f46e5 100%); box-shadow: 0 4px 14px rgba(79,70,229,.3); }
.stat-card--emerald { background: linear-gradient(135deg, #34d399 0%, #059669 100%); box-shadow: 0 4px 14px rgba(16,185,129,.3); }
.stat-card--sky     { background: linear-gradient(135deg, #38bdf8 0%, #0284c7 100%); box-shadow: 0 4px 14px rgba(14,165,233,.3); }
.stat-card--amber   { background: linear-gradient(135deg, #fbbf24 0%, #d97706 100%); box-shadow: 0 4px 14px rgba(217,119,6,.3); }

/* ═══════════ Chart Cards ═══════════ */
.chart-card {
    background: #fff;
    border-radius: 16px;
    padding: 0;
    box-shadow: 0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04);
    overflow: hidden;
    transition: box-shadow .2s ease;
}
.chart-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,.08), 0 8px 28px rgba(0,0,0,.06);
}
.chart-card__header {
    display: flex;
    align-items: center;
    gap: .5rem;
    padding: 1rem 1.25rem .6rem;
}
.chart-card__dot {
    width: 8px; height: 8px;
    border-radius: 50%;
}
.chart-card__dot--indigo  { background: #4f46e5; }
.chart-card__dot--emerald { background: #10b981; }
.chart-card__dot--sky     { background: #0ea5e9; }
.chart-card__title {
    font-size: .8rem;
    font-weight: 600;
    color: #475569;
    letter-spacing: .02em;
}
.chart-card__body {
    padding: .5rem 1.25rem .25rem;
}

/* Chart container with center label */
.chart-wrap {
    position: relative;
    height: 220px;
}
.chart-center {
    position: absolute;
    top: 46%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
    pointer-events: none;
}
.chart-center__num {
    display: block;
    font-size: 1.5rem;
    font-weight: 800;
    color: #1e293b;
    line-height: 1.1;
    letter-spacing: -.02em;
}
.chart-center__lbl {
    display: block;
    font-size: .65rem;
    color: #94a3b8;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: .06em;
    margin-top: 2px;
}

/* Custom legend below chart */
.chart-card__legend {
    display: flex;
    justify-content: center;
    gap: 1.2rem;
    padding: .6rem 1rem 1rem;
    flex-wrap: wrap;
}
.legend-item {
    display: flex;
    align-items: center;
    gap: .35rem;
}
.legend-dot {
    width: 10px; height: 10px;
    border-radius: 3px;
    flex-shrink: 0;
}
.legend-label {
    font-size: .72rem;
    color: #64748b;
    font-weight: 500;
}
.legend-value {
    font-size: .72rem;
    color: #1e293b;
    font-weight: 700;
}

/* ═══════════ Skeleton Loading ═══════════ */
@keyframes shimmer {
    0%   { background-position: -200px 0; }
    100% { background-position: calc(200px + 100%) 0; }
}
.skeleton {
    background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
    background-size: 200px 100%;
    animation: shimmer 1.5s ease-in-out infinite;
    border-radius: 6px;
}

/* ═══════════ Responsive ═══════════ */
@media (max-width: 768px) {
    .stat-card { padding: .9rem 1rem; gap: .65rem; min-height: auto; }
    .stat-card__icon { width: 40px; height: 40px; min-width: 40px; font-size: 1.1rem; border-radius: 10px; }
    .stat-card__num  { font-size: 1.25rem; }
    .stat-card__lbl  { font-size: .65rem; }
    .chart-wrap      { height: 200px; }
    .chart-center__num { font-size: 1.2rem; }
}
@media (max-width: 576px) {
    .stat-card__wave { width: 80px; height: 80px; right: -12px; bottom: -12px; }
    .stat-card__wave::after { width: 50px; height: 50px; }
    .chart-wrap { height: 180px; }
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.font.size = 11;

    /* ── Animated counter ── */
    function animateCount(el, target, duration) {
        if (!el || target === 0) { if (el) el.textContent = '0'; return; }
        var start = 0, startTime = null;
        function step(ts) {
            if (!startTime) startTime = ts;
            var progress = Math.min((ts - startTime) / duration, 1);
            var ease = 1 - Math.pow(1 - progress, 3);
            el.textContent = Math.floor(ease * target).toLocaleString();
            if (progress < 1) requestAnimationFrame(step);
            else el.textContent = target.toLocaleString();
        }
        requestAnimationFrame(step);
    }

    /* ── Build custom legend ── */
    function buildLegend(containerId, items) {
        var container = document.getElementById(containerId);
        if (!container) return;
        container.innerHTML = items.map(function (item) {
            return '<div class="legend-item">' +
                '<span class="legend-dot" style="background:' + item.color + '"></span>' +
                '<span class="legend-label">' + item.label + '</span>' +
                '<span class="legend-value">' + item.value.toLocaleString() + '</span>' +
            '</div>';
        }).join('');
    }

    /* ── Create doughnut chart ── */
    function createDoughnut(canvasId, labels, values, colors) {
        return new Chart(document.getElementById(canvasId), {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderWidth: 0,
                    borderRadius: 6,
                    spacing: 3,
                    hoverOffset: 8,
                }]
            },
            options: {
                cutout: '72%',
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    animateRotate: true,
                    duration: 1200,
                    easing: 'easeOutQuart',
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15,23,42,.88)',
                        titleFont: { weight: '600', size: 12 },
                        bodyFont: { size: 12 },
                        padding: { x: 12, y: 8 },
                        cornerRadius: 8,
                        displayColors: true,
                        boxWidth: 10,
                        boxHeight: 10,
                        boxPadding: 4,
                        callbacks: {
                            label: function (ctx) {
                                var total = ctx.dataset.data.reduce(function (a, b) { return a + b; }, 0);
                                var pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                                return ' ' + ctx.label + ': ' + ctx.parsed.toLocaleString() + ' នាក់ (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    /* ── Fetch & render ── */
    fetch('{{ route("dashboard.stats") }}')
        .then(function (res) { return res.json(); })
        .then(function (d) {

            /* Stat cards */
            animateCount(document.getElementById('stat-total'), d.totalCivilServant, 900);
            animateCount(document.getElementById('stat-departments'), d.totalDepartments, 700);
            animateCount(document.getElementById('stat-child-departments'), d.totalChildDepartments, 800);
            animateCount(document.getElementById('stat-id-card'), d.hasIdCardCount, 800);

            /* ── Gender Chart ── */
            createDoughnut('genderChart',
                ['ប្រុស', 'ស្រី'],
                [d.maleCount, d.femaleCount],
                ['#6366f1', '#ec4899']
            );
            document.querySelector('#genderCenter .chart-center__num').textContent = d.totalCivilServant.toLocaleString();
            buildLegend('genderLegend', [
                { label: 'ប្រុស', value: d.maleCount, color: '#6366f1' },
                { label: 'ស្រី', value: d.femaleCount, color: '#ec4899' },
            ]);

            /* ── Photo Chart ── */
            createDoughnut('photoChart',
                ['មានរូបថត', 'គ្មានរូបថត'],
                [d.hasPhotoCount, d.noPhotoCount],
                ['#10b981', '#f59e0b']
            );
            document.querySelector('#photoCenter .chart-center__num').textContent = d.hasPhotoCount.toLocaleString();
            document.querySelector('#photoCenter .chart-center__lbl').textContent = 'មានរូបថត';
            buildLegend('photoLegend', [
                { label: 'មានរូបថត', value: d.hasPhotoCount, color: '#10b981' },
                { label: 'គ្មានរូបថត', value: d.noPhotoCount, color: '#f59e0b' },
            ]);

            /* ── ID Card Chart ── */
            createDoughnut('idCardChart',
                ['មានអត្តសញ្ញាណប័ណ្ណ', 'គ្មានអត្តសញ្ញាណប័ណ្ណ'],
                [d.hasIdCardCount, d.noIdCardCount],
                ['#0ea5e9', '#f97316']
            );
            document.querySelector('#idCardCenter .chart-center__num').textContent = d.hasIdCardCount.toLocaleString();
            document.querySelector('#idCardCenter .chart-center__lbl').textContent = 'មានប័ណ្ណ';
            buildLegend('idCardLegend', [
                { label: 'មានអត្តសញ្ញាណប័ណ្ណ', value: d.hasIdCardCount, color: '#0ea5e9' },
                { label: 'គ្មានអត្តសញ្ញាណប័ណ្ណ', value: d.noIdCardCount, color: '#f97316' },
            ]);
        });
});
</script>
@endpush
