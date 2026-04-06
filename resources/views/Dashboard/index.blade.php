@extends('layout.main')

@section('title', 'ផ្ទាំងគ្រប់គ្រង')
@section('page-title', 'ផ្ទាំងគ្រប់គ្រង')
@section('page-subtitle', 'ទិដ្ឋភាពទូទៅនៃប្រព័ន្ធគ្រប់គ្រងបុគ្គលិក')

@section('content')
<div class="dash">

    {{-- ═══ Stat Cards ═══ --}}
    <div class="row g-4 mb-4 mt-1">
        <div class="col-6 col-md-4">
            <div class="stat-card stat-card--indigo">
                <div class="stat-card__icon"><i class="bi bi-people-fill"></i></div>
                <div class="stat-card__body">
                    <span class="stat-card__num">{{ number_format($totalCivilServant) }}</span>
                    <span class="stat-card__lbl">មន្រ្តីរាជការសរុប</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="stat-card stat-card--emerald">
                <div class="stat-card__icon"><i class="bi bi-building"></i></div>
                <div class="stat-card__body">
                    <span class="stat-card__num">{{ number_format($totalDepartments) }}</span>
                    <span class="stat-card__lbl">អង្គភាព / អគ្គនាយកដ្ឋាន</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="stat-card stat-card--sky">
                <div class="stat-card__icon"><i class="bi bi-gender-male"></i></div>
                <div class="stat-card__body">
                    <span class="stat-card__num">{{ number_format($totalChildDepartments)}}</span>
                    <span class="stat-card__lbl">អង្គភាព / នាយកដ្ឋានសរុប</span>
                </div>
            </div>
        </div>
     
    </div>

    {{-- ═══ Charts + List ═══ --}}
    <div class="row g-4">
        {{-- Charts row: two charts side-by-side --}}
        <div class="col-12">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <div class="card-min">
                        <span class="card-min__title">សមាមាត្រភេទ</span>
                        <div class="chart-wrap chart-wrap--sm">
                            <canvas id="genderChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="card-min">
                        <span class="card-min__title">សមាមាត្រមាន/គ្មានរូបថត</span>
                        <div class="chart-wrap chart-wrap--sm">
                            <canvas id="photoChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.dash { max-width: 1200px; margin: 0 auto; }

/* ── Stat Cards ── */
.stat-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: #fff;
    border-radius: 14px;
    padding: 1.5rem 1.6rem;
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
    border-left: 4px solid transparent;
    transition: transform .15s, box-shadow .15s;
}
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(0,0,0,.08); }
.stat-card__icon {
    width: 56px; height: 56px; min-width: 56px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem; color: #fff;
}
.stat-card__body { display: flex; flex-direction: column; min-width: 0; }
.stat-card__num { font-size: 1.9rem; font-weight: 800; color: #1e293b; line-height: 1.05; letter-spacing: -.02em; }
.stat-card__lbl { font-size: .85rem; color: #64748b; text-transform: uppercase; letter-spacing: .04em; margin-top: 4px; }

/* Card color variants */
.stat-card--indigo  { border-left-color: #4f46e5; }
.stat-card--indigo  .stat-card__icon { background: #4f46e5; }
.stat-card--emerald { border-left-color: #10b981; }
.stat-card--emerald .stat-card__icon { background: #10b981; }
.stat-card--sky     { border-left-color: #0ea5e9; }
.stat-card--sky     .stat-card__icon { background: #0ea5e9; }
.stat-card--pink    { border-left-color: #ec4899; }
.stat-card--pink    .stat-card__icon { background: #ec4899; }

/* ── Card ── */
.card-min {
    background: #fff;
    border-radius: 14px;
    padding: 1.15rem 1.25rem;
    box-shadow: 0 1px 4px rgba(0,0,0,.06);
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

/* ── Chart Sizes ── */
.chart-wrap { position: relative; }
    .chart-wrap--sm  { height: 320px; }

/* ── Dept List ── */
.dept-list { display: flex; flex-direction: column; gap: .35rem; max-height: 260px; overflow-y: auto; }
.dept-item {
    display: flex;
    align-items: center;
    gap: .65rem;
    padding: .45rem .6rem;
    border-radius: 8px;
    transition: background .15s;
}
.dept-item:hover { background: #f8fafc; }
.dept-item__num {
    width: 26px; height: 26px; min-width: 26px;
    border-radius: 8px;
    background: #eef2ff;
    color: #4f46e5;
    font-size: .72rem;
    font-weight: 700;
    display: flex; align-items: center; justify-content: center;
}
.dept-item__name {
    font-size: .82rem;
    font-weight: 500;
    color: #334155;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ── Responsive ── */
@media (max-width: 576px) {
    .stat-card { padding: .85rem .9rem; gap: .65rem; }
    .stat-card__icon { width: 38px; height: 38px; min-width: 38px; font-size: 1.05rem; }
    .stat-card__num  { font-size: 1.2rem; }
    .chart-wrap--sm  { height: 200px; }
    .dept-list { max-height: 220px; }
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var maleCount   = {{ (int) $maleCount }};
    var femaleCount = {{ (int) $femaleCount }};
    var hasPhoto    = {{ (int) $hasPhotoCount }};
    var noPhoto     = {{ (int) $noPhotoCount }};

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
            datasets: [{ data: [maleCount, femaleCount], backgroundColor: ['#4f46e5', '#ec4899'], borderWidth: 0 }]
        },
        options: {
            cutout: '68%',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { padding: 14 } },
                tooltip: { callbacks: { label: function (ctx) { return ctx.label + ': ' + ctx.parsed.toLocaleString() + ' នាក់'; } } }
            }
        }
    });

    // Photo Donut (has / no photo)
    new Chart(document.getElementById('photoChart'), {
        type: 'doughnut',
        data: {
            labels: ['មានរូបថត', 'គ្មានរូបថត'],
            datasets: [{ data: [hasPhoto, noPhoto], backgroundColor: ['#10b981', '#f59e0b'], borderWidth: 0 }]
        },
        options: {
            cutout: '68%',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { padding: 10 } },
                tooltip: { callbacks: { label: function (ctx) { return ctx.label + ': ' + ctx.parsed.toLocaleString() + ' នាក់'; } } }
            }
        }
    });
});
</script>
@endpush
