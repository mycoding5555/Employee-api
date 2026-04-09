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
                    <span class="stat-card__lbl">មន្រ្តីរាជការសកម្មសរុប</span>
                </div>
                <div class="stat-card__wave"></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card stat-card--emerald">
                <div class="stat-card__icon"><i class="bi bi-building"></i></div>
                <div class="stat-card__body">
                    <span class="stat-card__num" id="stat-departments">—</span>
                    <span class="stat-card__lbl">អង្គភាព / អគ្គនាយកដ្ឋាន</span>
                </div>
                <div class="stat-card__wave"></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card stat-card--sky">
                <div class="stat-card__icon"><i class="bi bi-diagram-3-fill"></i></div>
                <div class="stat-card__body">
                    <span class="stat-card__num" id="stat-child-departments">—</span>
                    <span class="stat-card__lbl">អង្គភាព / នាយកដ្ឋានសរុប</span>
                </div>
                <div class="stat-card__wave"></div>
            </div>
        </div>
        <div  route{{"civil-servants-id.index"}} class="col-6 col-lg-3">
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
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
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
