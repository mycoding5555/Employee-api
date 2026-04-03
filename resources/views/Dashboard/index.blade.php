@extends('layout.main')

@section('title', 'ផ្ទាំងគ្រប់គ្រង')
@section('page-title', 'ផ្ទាំងគ្រប់គ្រង')
@section('page-subtitle', 'ទិដ្ឋភាពទូទៅនៃប្រព័ន្ធគ្រប់គ្រងបុគ្គលិក')

@section('content')
<div class="dash">

    {{-- KPI Strip --}}
    <div class="kpi-strip">
        <div class="kpi">
            <span class="kpi__num">{{ number_format($totalStaff) }}</span>
            <span class="kpi__lbl">មន្រ្តីសរុប</span>
        </div>
        <div class="kpi-divider"></div>
        <div class="kpi">
            <span class="kpi__num">{{ number_format($totalDepartments) }}</span>
            <span class="kpi__lbl">នាយកដ្ឋាន</span>
        </div>
        <div class="kpi-divider"></div>
        <div class="kpi">
            <span class="kpi__num">{{ number_format($totalPositions) }}</span>
            <span class="kpi__lbl">មុខតំណែង</span>
        </div>
        <div class="kpi-divider"></div>
        <div class="kpi">
            <span class="kpi__num">{{ number_format($maleCount) }}<small>/</small>{{ number_format($femaleCount) }}</span>
            <span class="kpi__lbl">ប្រុស / ស្រី</span>
        </div>
    </div>

    {{-- Charts Row 1: Gender Donut + Photo Status Donut + Positions Polar --}}
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card-min">
                <span class="card-min__title">សមាមាត្រភេទ</span>
                <div class="chart-wrap chart-wrap--sm">
                    <canvas id="genderChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-min">
                <span class="card-min__title">រូបថត</span>
                <div class="chart-wrap chart-wrap--sm">
                    <canvas id="photoChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-min">
                <span class="card-min__title">មុខតំណែង</span>
                <div class="chart-wrap chart-wrap--sm">
                    <canvas id="positionChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Row 2: Department Bar --}}
    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="card-min">
                <span class="card-min__title">នាយកដ្ឋានតាមចំនួនមន្រ្តី</span>
                <div class="chart-wrap chart-wrap--bar">
                    <canvas id="deptChart"></canvas>
                </div>
            </div>
        </div>
    </div>



</div>
@endsection

@push('styles')
<style>
.dash { max-width: 960px; margin: 0 auto; }

/* ── KPI Strip ── */
.kpi-strip {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0;
    background: #fff;
    border-radius: 16px;
    padding: .9rem 1.5rem;
    margin-bottom: 1rem;
    box-shadow: 0 1px 4px rgba(0,0,0,.05);
}
.kpi { text-align: center; flex: 1; }
.kpi__num { display: block; font-size: 1.6rem; font-weight: 800; color: #1e293b; line-height: 1.2; letter-spacing: -.02em; }
.kpi__num small { font-weight: 400; color: #94a3b8; font-size: .85em; }
.kpi__lbl { font-size: .7rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .06em; }
.kpi-divider { width: 1px; height: 36px; background: #e2e8f0; flex-shrink: 0; }

/* ── Card ── */
.card-min {
    background: #fff;
    border-radius: 16px;
    padding: 1.15rem 1.25rem;
    box-shadow: 0 1px 4px rgba(0,0,0,.05);
}
.card-min__title {
    display: block;
    font-size: .78rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: .75rem;
}
.card-min__link {
    font-size: .75rem;
    color: #4f46e5;
    text-decoration: none;
    font-weight: 500;
}
.card-min__link:hover { text-decoration: underline; }

/* ── Chart Sizes ── */
.chart-wrap { position: relative; }
.chart-wrap--sm { height: 200px; }
.chart-wrap--bar { height: 260px; }

/* ── Recent List ── */
.recent-list { display: flex; flex-direction: column; gap: .5rem; }
.recent-item {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: .55rem .65rem;
    border-radius: 10px;
    transition: background .15s;
}
.recent-item:hover { background: #f8fafc; }
.recent-avatar {
    width: 36px; height: 36px; min-width: 36px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: .8rem; font-weight: 700; color: #fff;
}
.recent-avatar--m { background: #4f46e5; }
.recent-avatar--f { background: #ec4899; }
.recent-info { flex: 1; display: flex; flex-direction: column; min-width: 0; }
.recent-name { font-size: .85rem; font-weight: 600; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.recent-meta { font-size: .72rem; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.recent-badge {
    font-size: .68rem;
    padding: .15rem .5rem;
    border-radius: 999px;
    font-weight: 500;
    flex-shrink: 0;
}
.recent-badge--m { background: #eef2ff; color: #4338ca; }
.recent-badge--f { background: #fdf2f8; color: #be185d; }

/* ── Responsive ── */
@media (max-width: 576px) {
    .kpi-strip { flex-wrap: wrap; gap: .5rem; padding: .75rem 1rem; }
    .kpi-divider { display: none; }
    .kpi { flex: 0 0 48%; }
    .kpi__num { font-size: 1.3rem; }
    .chart-wrap--sm { height: 180px; }
    .chart-wrap--bar { height: 220px; }
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    var CHART_DATA = @json($chartData);
    var maleCount = {{ (int) $maleCount }};
    var femaleCount = {{ (int) $femaleCount }};
    var withPhoto = {{ (int) $withPhoto }};
    var withoutPhoto = {{ (int) $withoutPhoto }};

    var COLORS = {
        indigo: '#4f46e5', pink: '#ec4899', emerald: '#10b981', amber: '#f59e0b',
        sky: '#0ea5e9', violet: '#8b5cf6', rose: '#f43f5e', teal: '#14b8a6'
    };
    var palette = [COLORS.indigo, COLORS.emerald, COLORS.amber, COLORS.sky, COLORS.violet, COLORS.rose, COLORS.teal, COLORS.pink];

    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.font.size = 11;
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.pointStyleWidth = 8;
    Chart.defaults.plugins.legend.labels.boxHeight = 7;

    // Gender Donut
    new Chart(document.getElementById('genderChart'), {
        type: 'doughnut',
        data: {
            labels: ['ប្រុស', 'ស្រី'],
            datasets: [{ data: [maleCount, femaleCount], backgroundColor: [COLORS.indigo, COLORS.pink], borderWidth: 0 }]
        },
        options: {
            cutout: '70%',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { padding: 14 } },
                tooltip: { callbacks: { label: function(ctx){ return ctx.label + ': ' + ctx.parsed.toLocaleString() + ' នាក់'; } } }
            }
        }
    });

    // Photo Donut
    new Chart(document.getElementById('photoChart'), {
        type: 'doughnut',
        data: {
            labels: ['មានរូបថត', 'គ្មានរូបថត'],
            datasets: [{ data: [withPhoto, withoutPhoto], backgroundColor: [COLORS.emerald, '#e2e8f0'], borderWidth: 0 }]
        },
        options: {
            cutout: '70%',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { padding: 14 } },
                tooltip: { callbacks: { label: function(ctx){ return ctx.label + ': ' + ctx.parsed.toLocaleString(); } } }
            }
        }
    });

    // Position Polar Area
    new Chart(document.getElementById('positionChart'), {
        type: 'polarArea',
        data: {
            labels: CHART_DATA.posLabels,
            datasets: [{ data: CHART_DATA.posValues, backgroundColor: palette.map(function(c){ return c + '99'; }), borderColor: palette, borderWidth: 1.5 }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { r: { ticks: { display: false }, grid: { color: '#f1f5f9' } } },
            plugins: {
                legend: { position: 'bottom', labels: { padding: 10, font: { size: 10 } } },
                tooltip: { callbacks: { label: function(ctx){ return ctx.label + ': ' + ctx.parsed.r.toLocaleString() + ' នាក់'; } } }
            }
        }
    });

    // Department Horizontal Bar
    new Chart(document.getElementById('deptChart'), {
        type: 'bar',
        data: {
            labels: CHART_DATA.deptLabels,
            datasets: [{
                label: 'មន្រ្តី',
                data: CHART_DATA.deptValues,
                backgroundColor: palette,
                borderRadius: 6,
                barPercentage: 0.65
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { grid: { color: '#f1f5f9' }, ticks: { font: { size: 10 } } },
                y: { grid: { display: false }, ticks: { font: { size: 10 } } }
            },
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: function(ctx){ return ctx.parsed.x.toLocaleString() + ' នាក់'; } } }
            }
        }
    });
});
</script>
@endpush
