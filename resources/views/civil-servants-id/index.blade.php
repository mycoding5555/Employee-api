@extends('layout.main')

@section('title', 'អត្តលេខមន្រ្តីរាជការ')
@section('page-title', 'អត្តលេខមន្រ្តីរាជការ')
@section('page-subtitle', 'ឯកសារផ្តល់អត្តលេខមន្រ្តីរាជការស៊ីវិល')

@section('content')
    <div class="search-section">
        <div class="app-card">
            <div class="card-body-custom">
                <form action="{{ route('civilservant-id.index') }}" method="GET">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="search" class="form-label-custom">ស្វែងរក</label>
                            <div class="input-group">
                                <span class="input-group-text" style="border-radius:8px 0 0 8px; background:var(--bg); border-color:var(--border); color:var(--text-muted);">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" class="form-control" id="search" name="search"
                                       value="{{ $search ?? '' }}"
                                       placeholder="លេខកូដ, លេខយោង, ពិពណ៌នា..."
                                       style="border-left:none; border-radius:0 8px 8px 0;">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search me-1"></i> ស្វែងរក
                            </button>
                        </div>
                        @if($search)
                            <div class="col-md-2">
                                <a href="{{ route('civilservant-id.index') }}" class="btn btn-outline-secondary w-100">
                                    <i class="bi bi-x-circle me-1"></i> សម្អាត
                                </a>
                            </div>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="app-card mt-3">
        <div class="card-body-custom">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="badge bg-primary" style="font-size:0.9rem;">
                    <i class="bi bi-file-earmark-text me-1"></i> សរុប៖ {{ number_format($documents->total()) }} ឯកសារ
                </span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th style="width:50px;">ល.រ</th>
                            <th>លេខកូដ</th>
                            <th>លេខយោង</th>
                            <th>ចំណាំយោង</th>
                            <th>កាលបរិច្ឆេទ</th>
                            <th>ពិពណ៌នា</th>
                            <th>ស្ថានភាព</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($documents as $doc)
                            <tr>
                                <td>{{ $loop->iteration + ($documents->currentPage() - 1) * $documents->perPage() }}</td>
                                <td><code>{{ $doc->code }}</code></td>
                                <td>{{ $doc->ref_number }}</td>
                                <td>{{ $doc->ref_note }}</td>
                                <td>{{ $doc->ref_date?->format('d/m/Y') ?? '-' }}</td>
                                <td>{{ $doc->description ?? '-' }}</td>
                                <td>
                                    @if($doc->status === 1)
                                        <span class="badge bg-success">សកម្ម</span>
                                    @else
                                        <span class="badge bg-secondary">អសកម្ម</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox" style="font-size:2rem;"></i>
                                    <p class="mt-2">រកមិនឃើញឯកសារ</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($documents->hasPages())
                <div class="d-flex justify-content-center mt-3">
                    {{ $documents->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
