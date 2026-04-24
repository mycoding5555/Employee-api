@extends('layout.main')

@section('title', 'អត្តសញ្ញណប័ណ្ណ')
@section('page-title', 'អត្តសញ្ញណប័ណ្ណ')
@section('page-subtitle', 'បញ្ជីមន្រ្តីរាជការស៊ីវិលដែលមានអត្តសញ្ញណប័ណ្ណ')

@section('content')
        {{-- Error flash --}}
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mx-3 mt-3" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Search Section --}}
        <div class="search-section">
            <div class="app-card">
                <div class="card-body-custom">
                    <form id="search-form" action="{{ route('civilservant-id.index') }}" method="GET">
                        <input type="hidden" name="sort_by" id="sort_by" value="{{ $filters['sort_by'] ?? 'position_id' }}">
                        <input type="hidden" name="sort_dir" id="sort_dir" value="{{ $filters['sort_dir'] ?? 'asc' }}">
                        <div class="mb-2">
                            <span class="badge bg-primary" id="dept-badge" style="font-size:0.9rem;">
                                <i class="bi bi-building me-1"></i>
                                @if(isset($filters['department_id']) && $filters['department_id'])
                                    {{ $subDepartments->firstWhere('id', $filters['department_id'])?->name_kh ?? 'អគ្គនាយកដ្ឋានទាំងអស់' }}
                                @else
                                    អង្គភាព/អគ្គនាយកដ្ឋានទាំងអស់
                                @endif
                            </span>
                        </div>
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label for="name" class="form-label-custom">គោត្តនាម និងនាម</label>
                                <div class="input-group ">
                                    <span class="input-group-text" style="border-radius:8px 0 0 8px; background:var(--bg); border-color:var(--border); color:var(--text-muted);">
                                        <i class="bi bi-search"></i>
                                    </span>
                                    <input type="text" class="form-control" id="name" name="name_kh"
                                           value="{{ $filters['name_kh'] ?? '' }}"
                                           placeholder="រកតាមរយៈគោត្តនាម និងនាម" 
                                           style="border-left:none; border-radius:0 8px 8px 0;">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <label for="department_id" class="form-label-custom">អង្គភាព/អគ្គនាយកដ្ឋាន</label>
                                <select class="form-select" id="department_id" name="department_id">
                                    <option value="">អគ្គនាយកដ្ឋានទាំងអស់</option>
                                    @foreach($subDepartments as $sub)
                                        <option value="{{ $sub->id }}"
                                            {{ (isset($filters['department_id']) && $filters['department_id'] == $sub->id) ? 'selected' : '' }}>
                                            {{ $sub->name_kh }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="parent_id" class="form-label-custom">អង្គភាព/នាយកដ្ឋាន</label>
                                <select class="form-select" id="parent_id" name="parent_id">
                                    <option value="">នាយកដ្ឋានទាំងអស់</option>
                                    @if(isset($childDepartments))
                                        @foreach($childDepartments as $child)
                                            <option value="{{ $child->id }}"
                                                {{ (isset($filters['parent_id']) && $filters['parent_id'] == $child->id) ? 'selected' : '' }}>
                                                {{ $child->name_kh }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="position_id" class="form-label-custom">តួនាទីមុខតំណែង</label>
                                <select class="form-select" id="position_id" name="position_id">
                                    <option value="">មុខតំណែងទាំងអស់</option>
                                    @foreach($positions as $pos)
                                        <option value="{{ $pos['id'] }}"
                                            {{ (isset($filters['position_id']) && $filters['position_id'] == $pos['id']) ? 'selected' : '' }}>
                                            {{ $pos['name_kh'] ?? $pos['name_short'] ?? $pos['abb'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 d-flex gap-2">
                                <button type="submit" class="btn btn-primary-custom grow">
                                    <i class="bi bi-search me-1"></i> ស្វែងរក
                                </button>
                                <a href="{{ route('civilservant-id.index') }}" class="btn btn-outline-custom" title="Reset filters">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </a>
                            </div>
                            <div class="col-md-3 align-self-center">
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" value="1" id="has_document" name="has_document" {{ (isset($filters['has_document']) && $filters['has_document']) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="has_document">តែមានអត្តសញ្ញាណប័ណ្ណ</label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Results --}}
        <div id="results-container">
        @if(isset($civilServants))
            <div class="mt-4 mb-4">
                <div class="app-card">
                    <div class="card-header-custom d-flex align-items-center">
                        <span class="result-count">
                            <i class="bi bi-people"></i> {{ $civilServants->total() }} មន្រ្តីរាជការរកឃើញ
                        </span>
                        @php
                            $deptIdForLink = $deptIdForLink ?? ($filters['parent_id'] ?? $filters['department_id'] ?? null);
                            $deptNameForFname = $deptNameForFname ?? ($deptIdForLink ? ($subDepartments->firstWhere('id', $deptIdForLink)?->name_kh ?? 'department') : null);
                        @endphp
                        <div class="ms-auto d-flex gap-2">
                            @if(count(request()->query()) > 0)
                                <a href="{{ route('civilservant-id.download-pdf', request()->query()) }}" class="btn btn-success-custom">
                                    <i class="bi bi-file-earmark-pdf me-1"></i> ទាញយកបញ្ជី PDF
                                </a>
                                @if($deptIdForLink)
                                    <a href="{{ route('civilservant-id.download-department', $deptIdForLink) }}"
                                       data-fname="{{ $deptNameForFname }}.zip"
                                       data-dept-label="{{ $deptNameForFname }}"
                                       class="btn btn-success-custom">
                                        <i class="bi bi-file-earmark-zip me-1"></i> ទាញយកអត្តសញ្ញាណប័ណ្ណ
                                    </a>
                                @else
                                    <a href="{{ route('civilservant-id.download-department',['department_id']) }}"
                                       data-fname="ទាំងអស់.zip"
                                       data-dept-label="ទាំងអស់"
                                       class="btn btn-success-custom">
                                        <i class="bi bi-file-earmark-zip me-1"></i> ទាញយកអត្តសញ្ញាណប័ណ្ណ
                                    </a>
                                @endif
                            @endif
                        </div>
                    </div>

                    @if($civilServants->total() > 0)
                        @php
                            $currentSortBy = $filters['sort_by'] ?? 'position_id';
                            $currentSortDir = $filters['sort_dir'] ?? 'asc';
                        @endphp
                        <div class="table-responsive">
                            <table class="table-custom table">
                                <thead>
                                    <tr>
                                        <th style="width:60px">លេខរៀង</th>
                                        <th class="sortable-th" data-sort="last_name_kh">
                                            គោត្តនាម និងនាម
                                            @if($currentSortBy === 'last_name_kh')
                                                <i class="bi bi-chevron-{{ $currentSortDir === 'asc' ? 'up' : 'down' }}"></i>
                                            @else
                                                <i class="bi bi-chevron-expand text-muted"></i>
                                            @endif
                                        </th>
                                        <th class="sortable-th" data-sort="gender_id">
                                            ភេទ
                                            @if($currentSortBy === 'gender_id')
                                                <i class="bi bi-chevron-{{ $currentSortDir === 'asc' ? 'up' : 'down' }}"></i>
                                            @else
                                                <i class="bi bi-chevron-expand text-muted"></i>
                                            @endif
                                        </th>
                                        <th class="sortable-th" data-sort="position_id">
                                            តួនាទី/មុខតំណែង
                                            @if($currentSortBy === 'position_id')
                                                <i class="bi bi-chevron-{{ $currentSortDir === 'asc' ? 'up' : 'down' }}"></i>
                                            @else
                                                <i class="bi bi-chevron-expand text-muted"></i>
                                            @endif
                                        </th>
                                        <th class="sortable-th" data-sort="department_id" style="max-width:160px;white-space:normal;word-break:break-word;">
                                            អង្គភាព/អគ្គនាយកដ្ឋាន
                                            @if($currentSortBy === 'department_id')
                                                <i class="bi bi-chevron-{{ $currentSortDir === 'asc' ? 'up' : 'down' }}"></i>
                                            @else
                                                <i class="bi bi-chevron-expand text-muted"></i>
                                            @endif
                                        </th>
                                        <th>អង្គភាព/នាយកដ្ឋាន</th>
                                        <th style="width:160px; text-align:center;">ឯកសារ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $lastPositionName = null;
                                        $lastDepartmentName = null;
                                    @endphp
                                    @foreach($civilServants as $i => $emp)
                                        @php
                                            $fullName = trim(($emp['last_name_kh'] ?? '') . ' ' . ($emp['first_name_kh'] ?? ''));
                                            $positionName = $emp['position']['name_kh'] ?? $emp['position']['name_short'] ?? $emp['position']['abb'] ?? 'គ្មានមុខតំណែង';
                                            $nayokName = $deptGroupMap[$emp->department_id] ?? ($emp['department']['name_kh'] ?? 'គ្មាននាយកដ្ឋាន');
                                        @endphp
                                        @if($currentSortBy === 'position_id' && $positionName !== $lastPositionName)
                                            <tr class="position-group-row">
                                                <td colspan="7">
                                                    <strong><i class="bi bi-bookmark-fill me-1"></i>{{ $positionName }}</strong>
                                                </td>
                                            </tr>
                                            @php $lastPositionName = $positionName; @endphp
                                        @endif
                                        @if($currentSortBy === 'department_id' && $nayokName !== $lastDepartmentName)
                                            @php
                                                $parentDeptId = $emp->parent_department_id ?? $emp->department_id ?? null;
                                                $deptLabel = $nayokName;
                                            @endphp
                                            <tr class="position-group-row">
                                                <td colspan="7">
                                                    <strong><i class="bi bi-building me-1"></i>{{ $nayokName }}</strong>
                                                    @if($parentDeptId)
                                                        <a href="{{ url('/civilservant-id/download-department') }}/{!! $parentDeptId !!}"
                                                           data-fname="{{ $nayokName }}.zip"
                                                           data-dept-label="{{ $nayokName }}"
                                                           class="ms-2 text-success" title="ទាញយកឯកសារ នាយកដ្ឋាននេះ">
                                                            <i class="bi bi-file-earmark-zip" style="font-size:1rem"></i>
                                                        </a>
                                                    @endif
                                                </td>
                                            </tr>
                                            @php $lastDepartmentName = $nayokName; @endphp
                                        @endif
                                        <tr>
                                            <td class="text-muted fw-medium">{{ $civilServants->firstItem() + $i }}</td>
                                            <td><span class="emp-name">{{ $fullName }}</span></td>
                                            <td>{{ $emp['gender_id'] == 1 ? 'ប្រុស' : 'ស្រី' }}</td>
                                            <td>{{ $emp['position']['name_kh'] ?? $emp['position']['name_short'] ?? $emp['position']['abb'] ?? 'N/A' }}</td>
                                            <td><span class="dept-cell">{{ $emp->department_name ?? $emp['department']['name_kh'] ?? 'N/A' }}</span></td>
                                            <td><span class="dept-cell">{{ $emp->sub_department_name ?? 'N/A' }}</span></td>
                                            <td style="text-align:center;">
                                                <div class="d-flex align-items-center justify-content-center gap-3">
                                                    {{-- ID Card (type 16) --}}
                                                    @if($emp->has_id_card)
                                                        <a href="{{ route('civilservant-id.download-id-card-doc', $emp->id) }}" title="ទាញយកអត្តសញ្ញណប័ណ្ណ (16)" class="text-primary">
                                                            <i class="bi bi-card-heading" style="font-size:1.1rem;"></i>
                                                        </a>
                                                    @else
                                                        <span title="គ្មានអត្តសញ្ញណប័ណ្ណ" class="text-muted" style="cursor:default;">
                                                            <i class="bi bi-card-heading" style="font-size:1.1rem; opacity:0.3;"></i>
                                                        </span>
                                                    @endif

                                                    {{-- Delta doc (type 10) --}}
                                                    @if($emp->has_delta_doc)
                                                        <a href="{{ route('civilservant-id.download-delta-doc', $emp->id) }}" title="ទាញយកឯកសារ (10)" class="text-success">
                                                            <i class="bi bi-download" style="font-size:1.1rem;"></i>
                                                        </a>
                                                    @else
                                                        <span title="គ្មានឯកសារ" class="text-muted" style="cursor:default;">
                                                            <i class="bi bi-download" style="font-size:1.1rem; opacity:0.3;"></i>
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Pagination --}}
                        @if($civilServants->hasPages())
                        <div class="pagination-wrapper">
                            <div class="pagination-controls">
                                {{ $civilServants->links() }}
                            </div>
                        </div>
                        @endif
                    @else
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="bi bi-person-slash"></i></div>
                            <h5>រកមិនឃើញមន្រ្តីរាជការ</h5>
                        </div>
                    @endif
                </div>
            </div>
        @else
            {{-- Initial state before search --}}
            <div class="mt-4 mb-4">
                <div class="app-card">
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="bi bi-search"></i></div>
                        <h5>Search for civil servants</h5>
                        <p>Use the filters above to find civil servants by name or department.</p>
                    </div>
                </div>
            </div>
        @endif
        </div>
    @endsection

    @push('scripts')
    <script>
    (function() {
        const nameInput = document.getElementById('name');
        const generalDeptSelect = document.getElementById('department_id');
        const deptSelect = document.getElementById('parent_id');
        const posSelect = document.getElementById('position_id');
        const hasDocumentCheckbox = document.getElementById('has_document');
        const sortByInput = document.getElementById('sort_by');
        const sortDirInput = document.getElementById('sort_dir');
        const resultsContainer = document.getElementById('results-container');
        const searchForm = document.getElementById('search-form');
        const deptBadge = document.getElementById('dept-badge');
        const perPage = 20;
        const deptChildrenMap = @json($allChildDepts);
        let debounceTimer;
        let lastResponse = null;
        let currentPage = 1;
        let currentDeptId = '';
        let currentDeptName = '';
        let currentGeneralDeptName = '';
        let currentPosId = '';
        let currentSortBy = sortByInput.value || 'position_id';
        let currentSortDir = sortDirInput.value || 'asc';

        // Cascade: when អគ្គនាយកដ្ឋាន changes, fetch children for នាយកដ្ឋាន
        generalDeptSelect.addEventListener('change', function() {
            const parentId = this.value;
            const selectedText = this.options[this.selectedIndex]?.text?.trim() || 'អគ្គនាយកដ្ឋានទាំងអស់';
            deptBadge.innerHTML = '<i class="bi bi-building me-1"></i> ' + (parentId ? selectedText : 'អគ្គនាយកដ្ឋានទាំងអស់');
            deptSelect.innerHTML = '<option value="">នាយកដ្ឋានទាំងអស់</option>';
            deptSelect.disabled = true;
            // Reset position dropdown when general department changes
            posSelect.innerHTML = '<option value="">មុខតំណែងទាំងអស់</option>';

            if (!parentId) {
                deptSelect.disabled = false;
                currentPage = 1;
                fetchCivilServants();
                return;
            }

            const children = deptChildrenMap[parentId] || [];
            children.forEach(function(dept) {
                const opt = document.createElement('option');
                opt.value = dept.id;
                opt.textContent = dept.name_kh;
                deptSelect.appendChild(opt);
            });
            deptSelect.disabled = false;
            currentPage = 1;
            fetchCivilServants();
        });

        // Cascade: when នាយកដ្ឋាន changes, fetch civil servants then populate positions from results
        deptSelect.addEventListener('change', function() {
            posSelect.innerHTML = '<option value="">មុខតំណែងទាំងអស់</option>';
            currentPage = 1;
            fetchCivilServants(true);
        });

        // Sort column click handler (server-side via form submit)
        document.querySelectorAll('.sortable-th').forEach(function(th) {
            th.style.cursor = 'pointer';
            th.addEventListener('click', function() {
                const col = this.getAttribute('data-sort');
                if (currentSortBy === col) {
                    currentSortDir = currentSortDir === 'asc' ? 'desc' : 'asc';
                } else {
                    currentSortBy = col;
                    currentSortDir = 'asc';
                }
                sortByInput.value = currentSortBy;
                sortDirInput.value = currentSortDir;
                searchForm.submit();
            });
        });

        searchForm.addEventListener('submit', function(e) {
            // Allow normal form submit for server-side search+sort
        });

        nameInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() { currentPage = 1; fetchCivilServants(); }, 300);
        });

        posSelect.addEventListener('change', function() { currentPage = 1; fetchCivilServants(); });

        if (hasDocumentCheckbox) {
            hasDocumentCheckbox.addEventListener('change', function() {
                currentPage = 1;
                fetchCivilServants(true);
            });
        }

        function fetchCivilServants(rebuildPositions) {
            const params = new URLSearchParams();
            const name = nameInput.value.trim();
            currentDeptId = deptSelect.value;
            currentDeptName = deptSelect.value ? (deptSelect.options[deptSelect.selectedIndex]?.text?.trim() || '') : '';
            currentGeneralDeptName = generalDeptSelect.value ? (generalDeptSelect.options[generalDeptSelect.selectedIndex]?.text?.trim() || '') : '';
            currentPosId = posSelect.value;
            const generalDeptId = generalDeptSelect.value;

            if (name) params.append('name_kh', name);
            if (currentDeptId) {
                params.append('parent_id', currentDeptId);
            } else if (generalDeptId) {
                params.append('department_id', generalDeptId);
            }
            if (hasDocumentCheckbox && hasDocumentCheckbox.checked) params.append('has_document', '1');
            if (currentPosId) params.append('position_id', currentPosId);
            params.append('sort_by', currentSortBy);
            params.append('sort_dir', currentSortDir);
            params.append('page', currentPage);
            params.append('per_page', perPage);

            fetch('{{ route("civilservant-id.ajax-search") }}?' + params.toString())
                .then(function(r) {
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    return r.json();
                })
                .then(function(response) {
                    lastResponse = response;

                    // Rebuild position dropdown from fetched civil servants
                    if (rebuildPositions) {
                        const seen = {};
                        const positions = [];
                        (response.data || []).forEach(function(emp) {
                            if (emp.position && !seen[emp.position.id]) {
                                seen[emp.position.id] = true;
                                positions.push(emp.position);
                            }
                        });
                        positions.sort(function(a, b) { return (a.sort || 0) - (b.sort || 0); });
                        posSelect.innerHTML = '<option value="">មុខតំណែងទាំងអស់</option>';
                        positions.forEach(function(pos) {
                            const opt = document.createElement('option');
                            opt.value = pos.id;
                            opt.textContent = pos.name_kh || pos.name_short || pos.abb;
                            posSelect.appendChild(opt);
                        });
                    }

                    renderPage();
                })
                .catch(function(err) {
                    console.error('fetchCivilServants error:', err);
                    resultsContainer.innerHTML = `
                        <div class="mt-4 mb-4"><div class="app-card"><div class="empty-state">
                            <div class="empty-state-icon"><i class="bi bi-exclamation-triangle"></i></div>
                            <h5>កំហុសក្នុងការទាញយកទិន្នន័យ</h5>
                            <p>សូមពិនិត្យការតភ្ជាប់ហើយព្យាយាមម្តងទៀត។</p>
                        </div></div></div>`;
                });
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        /* -------------------- Download progress + confirmation (copied from photo view) -------------------- */
        function parseFilenameFromDisposition(disposition) {
            if (!disposition) return null;
            const filenameRegex = /filename\*=UTF-8''([^;\n]*)|filename=?"?([^";\n]*)"?/i;
            const match = disposition.match(filenameRegex);
            if (!match) return null;
            return decodeURIComponent(match[1] || match[2]);
        }

        async function downloadWithProgress(url, suggestedName) {
            const overlay = getOrCreateProgressOverlay();
            overlay.show();

            try {
                const resp = await fetch(url, { credentials: 'same-origin' });
                if (!resp.ok) throw new Error('Server returned ' + resp.status);

                const contentLength = resp.headers.get('content-length');
                const disposition = resp.headers.get('content-disposition');
                const contentType = resp.headers.get('content-type') || 'application/octet-stream';
                const filename = suggestedName || parseFilenameFromDisposition(disposition) || 'download.bin';

                if (!resp.body) {
                    overlay.hide();
                    window.location = url;
                    return;
                }

                const total = contentLength ? parseInt(contentLength, 10) : null;
                const reader = resp.body.getReader();
                const chunks = [];
                let received = 0;

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;
                    chunks.push(value);
                    received += value.length;
                    if (total) {
                        overlay.setProgress((received / total) * 100);
                    } else {
                        overlay.setIndeterminate();
                    }
                }

                const blob = new Blob(chunks, { type: contentType });
                const blobUrl = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = blobUrl;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                a.remove();
                URL.revokeObjectURL(blobUrl);
                overlay.complete();
            } catch (err) {
                console.error(err);
                alert('រកមិនឃើញឯកសារ ឬមានកំហុសក្នុងការទាញយក');
                getOrCreateProgressOverlay().hide();
            }
        }

        function getOrCreateProgressOverlay() {
            let overlay = document.getElementById('download-progress-overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.id = 'download-progress-overlay';
                overlay.innerHTML = `
                    <div class="dp-inner">
                        <div class="dp-title">កំពុងទាញយក...</div>
                        <div class="dp-bar"><div class="dp-fill" style="width:0%"></div></div>
                        <div class="dp-percent">0%</div>
                    </div>`;
                document.body.appendChild(overlay);
                const style = document.createElement('style');
                style.textContent = `#download-progress-overlay{position:fixed;left:0;right:0;top:0;bottom:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.35);z-index:99999;visibility:hidden;opacity:0;transition:opacity .18s}#download-progress-overlay .dp-inner{background:#fff;padding:18px 22px;border-radius:8px;min-width:320px;max-width:90%;box-shadow:0 6px 24px rgba(2,6,23,.2);text-align:center}#download-progress-overlay .dp-title{font-weight:600;margin-bottom:8px}#download-progress-overlay .dp-bar{height:10px;background:#f1f5f9;border-radius:999px;overflow:hidden;margin:8px 0}#download-progress-overlay .dp-fill{height:100%;background:#10b981;width:0;transition:width .12s linear}#download-progress-overlay .dp-percent{font-size:13px;color:#334155;margin-top:6px}`;
                document.head.appendChild(style);

                overlay.show = function() { overlay.style.visibility = 'visible'; overlay.style.opacity = '1'; overlay.querySelector('.dp-fill').style.width = '0%'; overlay.querySelector('.dp-percent').textContent = '0%'; overlay.querySelector('.dp-title').textContent = 'កំពុងទាញយក...'; };
                overlay.hide = function() { overlay.style.opacity = '0'; setTimeout(() => { overlay.style.visibility = 'hidden'; }, 200); };
                overlay.setProgress = function(p) { const pct = Math.min(100, Math.max(0, Math.round(p))); overlay.querySelector('.dp-fill').style.width = pct + '%'; overlay.querySelector('.dp-percent').textContent = pct + '%'; };
                overlay.setIndeterminate = function() { overlay.querySelector('.dp-fill').style.width = '60%'; overlay.querySelector('.dp-percent').textContent = '...'; };
                overlay.setTitle = function(t) { overlay.querySelector('.dp-title').textContent = t; };
                overlay.complete = function() { overlay.setProgress(100); overlay.setTitle('រួចរាល់!'); setTimeout(() => overlay.hide(), 600); };
            }
            return overlay;
        }

        function getOrCreateConfirmModal() {
            let modal = document.getElementById('download-confirm-modal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'download-confirm-modal';
                modal.innerHTML = `
                    <div class="cm-backdrop"></div>
                    <div class="cm-box">
                        <h4 class="cm-title">ទិន្នន័យធ្វើការទាញយក</h4>
                        <p class="cm-message">តើអ្នកពិតជាចង់ទាញយកឯកសារសម្រាប់ <span class="cm-entity"></span>?</p>
                        <p class="cm-size-row">ទំហំៈ <span class="cm-size">—</span></p>
                        <div class="cm-actions">
                            <button class="cm-cancel btn btn-outline-custom">បោះបង់</button>
                            <button class="cm-confirm btn btn-success-custom">ទាញយក</button>
                        </div>
                    </div>`;
                document.body.appendChild(modal);
                const style = document.createElement('style');
                style.textContent = `#download-confirm-modal{position:fixed;left:0;right:0;top:0;bottom:0;display:flex;align-items:center;justify-content:center;z-index:100000;visibility:hidden;opacity:0;transition:opacity .18s}#download-confirm-modal .cm-backdrop{position:absolute;left:0;right:0;top:0;bottom:0;background:rgba(0,0,0,0.35)}#download-confirm-modal .cm-box{position:relative;background:#fff;padding:18px 20px;border-radius:8px;min-width:320px;max-width:90%;box-shadow:0 6px 24px rgba(2,6,23,.2);z-index:2;text-align:left}#download-confirm-modal .cm-title{margin:0 0 6px 0}#download-confirm-modal .cm-message{margin:0 0 8px 0}#download-confirm-modal .cm-size-row{margin:0 0 12px 0;color:#475569}#download-confirm-modal .cm-actions{display:flex;gap:8px;justify-content:flex-end}`;
                document.head.appendChild(style);
                modal.show = function(entityLabel, sizeText, onConfirm) {
                    modal.querySelector('.cm-entity').textContent = entityLabel || '';
                    modal.querySelector('.cm-size').textContent = sizeText || '—';
                    modal.style.visibility = 'visible'; modal.style.opacity = '1';
                    modal.querySelector('.cm-confirm').onclick = function() { modal.hide(); onConfirm && onConfirm(); };
                    modal.querySelector('.cm-cancel').onclick = function() { modal.hide(); };
                };
                modal.updateSize = function(sizeText) { const el = modal.querySelector('.cm-size'); if (el) el.textContent = sizeText || '—'; };
                modal.hide = function() { modal.style.opacity = '0'; setTimeout(() => { modal.style.visibility = 'hidden'; }, 180); };
            }
            return modal;
        }

        function humanBytes(bytes) {
            if (!bytes || isNaN(bytes)) return 'មិនដឹងទំហំ';
            const units = ['B','KB','MB','GB','TB'];
            let i = 0;
            let val = Number(bytes);
            while (val >= 1024 && i < units.length - 1) { val /= 1024; i++; }
            return `${val.toFixed(val >= 100 ? 0 : val >= 10 ? 1 : 2)} ${units[i]}`;
        }

        // Intercept download links and show confirmation for department downloads
        document.addEventListener('click', function(e) {
            const el = e.target.closest('a');
            if (!el) return;
            const href = el.getAttribute('href') || '';

            // Individual id-card downloads: download with progress directly
            if (href.includes('/civilservant-id/') && href.match(/download-(id-card-doc|delta-doc)/)) {
                e.preventDefault();
                const suggested = el.getAttribute('data-fname') || null;
                downloadWithProgress(href, suggested);
                return;
            }

            // Department-level download: ask for confirmation then download with progress (show estimated size)
            if (href.includes('/civilservant-id/download-department/')) {
                e.preventDefault();
                const suggested = el.getAttribute('data-fname') || null;
                const deptLabel = el.getAttribute('data-dept-label') || el.textContent.trim() || 'នាយកដ្ឋាន';
                const modal = getOrCreateConfirmModal();
                // Show modal immediately with calculating text, then perform HEAD to get size
                modal.show(deptLabel, 'កំពុងគណនា...', function() {
                    downloadWithProgress(href, suggested);
                });

                // Attempt HEAD to fetch content-length
                fetch(href, { method: 'HEAD', credentials: 'same-origin' })
                    .then(function(res) {
                        if (!res.ok) throw new Error('HEAD failed ' + res.status);
                        const len = res.headers.get('content-length');
                        if (len) {
                            modal.updateSize(humanBytes(parseInt(len, 10)));
                        } else {
                            modal.updateSize('មិនដឹងទំហំ');
                        }
                    })
                    .catch(function() {
                        modal.updateSize('មិនដឹងទំហំ');
                    });
                return;
            }
        });


        function sortIcon(col) {
            if (currentSortBy === col) {
                return currentSortDir === 'asc'
                    ? '<i class="bi bi-chevron-up"></i>'
                    : '<i class="bi bi-chevron-down"></i>';
            }
            return '<i class="bi bi-chevron-expand text-muted"></i>';
        }

        function renderPage() {
            if (!lastResponse) return;
            const pageItems = lastResponse.data || [];
            const total = lastResponse.total || 0;
            const totalPages = lastResponse.last_page || 1;
            currentPage = lastResponse.current_page || 1;
            const startIndex = lastResponse.from || 1;

            if (total === 0) {
                resultsContainer.innerHTML = `
                    <div class="mt-4 mb-4"><div class="app-card">
                        <div class="card-header-custom d-flex align-items-center">
                            <span class="result-count"><i class="bi bi-people"></i> 0 មន្រ្តីរាជការរកឃើញ</span>
                        </div>
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="bi bi-person-slash"></i></div>
                            <h5>រកមិនឃើញមន្រ្តីរាជការ</h5>
                            <p>សូមកែប្រែលក្ខខណ្ឌស្វែងរក។</p>
                        </div>
                    </div></div>`;
                return;
            }

            let rows = '';
            let lastGroupName = null;

            // Build PDF download link with current filters
            const pdfParams = new URLSearchParams();
            if (nameInput.value.trim()) pdfParams.append('name_kh', nameInput.value.trim());
            if (deptSelect.value) pdfParams.append('parent_id', deptSelect.value);
            else if (generalDeptSelect.value) pdfParams.append('department_id', generalDeptSelect.value);
            if (posSelect.value) pdfParams.append('position_id', posSelect.value);
            pdfParams.append('sort_by', currentSortBy);
            pdfParams.append('sort_dir', currentSortDir);
                const pdfUrl = '{{ route("civilservant-id.download-pdf") }}?' + pdfParams.toString();
                const pdfBtn = `<a href="${pdfUrl}" class="btn btn-success-custom"><i class="bi bi-file-earmark-pdf me-1"></i> \u1791\u17b6\u1789\u1799\u1780\u1794\u1789\u17d2\u1787\u17b8 PDF</a>`;

                const downloadBase = '{{ url("/civilservant-id") }}';
                const downloadDeptBase = '{{ url("/civilservant-id/download-department") }}';
                // Department download button (uses selected parent_id or department_id). Disabled when none selected.
                let deptDownloadBtn = '';
                const selectedDept = deptSelect.value || generalDeptSelect.value;
                if (selectedDept) {
                    const safeName = currentDeptName || currentGeneralDeptName || selectedDept;
                    deptDownloadBtn = `<a href="${downloadDeptBase}/${selectedDept}" data-fname="${escapeHtml(safeName)}-list.zip" data-dept-label="${safeName}" class="btn btn-success-custom ms-2"><i class="bi bi-file-earmark-zip me-1"></i> ទាញយកអត្តសញ្ញាណប័ណ្ណ</a>`;
                } else {
                    deptDownloadBtn = `<button class="btn btn-outline-custom ms-2" disabled title="ជ្រើសអង្គភាពដើម្បីទាញយក"> <i class="bi bi-file-earmark-zip me-1"></i> ទាញយកអត្តសញ្ញាណប័ណ្ណ</button>`;
                }
                pageItems.forEach(function(emp, i) {
                const globalIndex = (startIndex || 1) + i;
                const name = escapeHtml((emp.last_name_kh || '') + ' ' + (emp.first_name_kh || '')).trim();
                const sex = emp.gender_id == 1 ? '\u1794\u17d2\u179a\u17bb\u179f' : '\u179f\u17d2\u179a\u17b8';
                const title = emp.position ? escapeHtml(emp.position.name_kh || emp.position.name_short || emp.position.abb || 'N/A') : 'N/A';
                const deptName = emp.department ? escapeHtml(emp.department.name_kh || 'N/A') : 'N/A';
                const deptId = emp.department ? emp.department.id : null;

                // Position group header row
                if (currentSortBy === 'position_id' && title !== lastGroupName) {
                    rows += `<tr class="position-group-row">
                        <td colspan="7"><strong><i class="bi bi-bookmark-fill me-1"></i>${title}</strong></td>
                    </tr>`;
                    lastGroupName = title;
                }

                // Department group header row
                if (currentSortBy === 'department_id' && deptName !== lastGroupName) {
                    // Include a download link for this department group
                    const groupDownload = deptId ? `<a href="${downloadDeptBase}/${deptId}" data-dept-label="${deptName}" class="ms-2 text-success" title="ទាញយកឯកសារ"><i class="bi bi-file-earmark-zip" style="font-size:1rem"></i></a>` : '';
                    rows += `<tr class="position-group-row">
                        <td colspan="7"><strong><i class="bi bi-building me-1"></i>${deptName}</strong>${groupDownload}</td>
                    </tr>`;
                    lastGroupName = deptName;
                }

                const subDeptName = emp.sub_department_name ? escapeHtml(emp.sub_department_name) : (emp.sub_department ? escapeHtml(emp.sub_department.name_kh || '') : 'N/A');

                // ID card (type 16) download
                const idCardLink = emp.has_id_card
                    ? `<a href="${downloadBase}/${emp.id}/download-id-card-doc" title="ទាញយកអត្តសញ្ញណប័ណ្ណ (16)" class="text-primary"><i class="bi bi-card-heading" style="font-size:1.1rem;"></i></a>`
                    : `<span title="គ្មានអត្តសញ្ញណប័ណ្ណ" class="text-muted" style="cursor:default;"><i class="bi bi-card-heading" style="font-size:1.1rem;opacity:0.3;"></i></span>`;

                // Delta doc (type 10) download
                const deltaLink = emp.has_delta_doc
                    ? `<a href="${downloadBase}/${emp.id}/download-delta-doc" title="ទាញយកឯកសារ (10)" class="text-success"><i class="bi bi-download" style="font-size:1.1rem;"></i></a>`
                    : `<span title="គ្មានឯកសារ" class="text-muted" style="cursor:default;"><i class="bi bi-download" style="font-size:1.1rem;opacity:0.3;"></i></span>`;

                rows += `<tr>
                    <td class="text-muted fw-medium">${globalIndex}</td>
                    <td><span class="emp-name">${name}</span></td>
                    <td>${sex}</td>
                    <td>${title}</td>
                    <td><span class="dept-cell">${deptName}</span></td>
                    <td><span class="dept-cell">${subDeptName}</span></td>
                    <td><div class="d-flex align-items-center justify-content-center gap-3">${idCardLink}${deltaLink}</div></td>
                </tr>`;
            });

            let paginationHtml = '';
            if (totalPages > 1) {
                paginationHtml = `<div class="pagination-wrapper">
                    <div class="pagination-controls"><nav><ul class="pagination">`;

                paginationHtml += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage - 1}">&laquo;</a></li>`;

                let startP = Math.max(1, currentPage - 2);
                let endP = Math.min(totalPages, currentPage + 2);
                if (startP > 1) {
                    paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
                    if (startP > 2) paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
                for (let p = startP; p <= endP; p++) {
                    paginationHtml += `<li class="page-item ${p === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${p}">${p}</a></li>`;
                }
                if (endP < totalPages) {
                    if (endP < totalPages - 1) paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                    paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a></li>`;
                }

                paginationHtml += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage + 1}">&raquo;</a></li>`;
                paginationHtml += `</ul></nav></div></div>`;
            }

            resultsContainer.innerHTML = `
                <div class="mt-4 mb-4"><div class="app-card">
                    <div class="card-header-custom d-flex align-items-center">
                        <span class="result-count"><i class="bi bi-people"></i> ${total} មន្រ្តីរាជការរកឃើញ</span>
                        <div class="ms-auto d-flex gap-2">${pdfBtn}${deptDownloadBtn}</div>
                    </div>
                    <div class="table-responsive">
                        <table class="table-custom table">
                            <thead><tr>
                                <th style="width:60px">លេខរៀង</th>
                                <th class="sortable-th-js" data-sort="last_name_kh">គោត្តនាម និងនាម ${sortIcon('last_name_kh')}</th>
                                <th class="sortable-th-js" data-sort="gender_id">ភេទ ${sortIcon('gender_id')}</th>
                                <th class="sortable-th-js" data-sort="position_id">តួនាទី/មុខតំណែង ${sortIcon('position_id')}</th>
                                <th class="sortable-th-js" data-sort="department_id" style="max-width:160px;white-space:normal;word-break:break-word;">អង្គភាព/អគ្គនាយកដ្ឋាន ${sortIcon('department_id')}</th>
                                <th>អង្គភាព/នាយកដ្ឋាន</th>
                                <th style="width:160px; text-align:center;">ឯកសារ</th>
                            </tr></thead>
                            <tbody>${rows}</tbody>
                        </table>
                    </div>
                    ${paginationHtml}
                </div></div>`;

            // Attach sort click handlers
            resultsContainer.querySelectorAll('.sortable-th-js').forEach(function(th) {
                th.style.cursor = 'pointer';
                th.addEventListener('click', function() {
                    const col = this.getAttribute('data-sort');
                    if (currentSortBy === col) {
                        currentSortDir = currentSortDir === 'asc' ? 'desc' : 'asc';
                    } else {
                        currentSortBy = col;
                        currentSortDir = 'asc';
                    }
                    sortByInput.value = currentSortBy;
                    sortDirInput.value = currentSortDir;
                    fetchCivilServants();
                });
            });

            // Attach pagination click handlers
            resultsContainer.querySelectorAll('.page-link[data-page]').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const page = parseInt(this.getAttribute('data-page'));
                    if (page >= 1 && page <= totalPages) {
                        currentPage = page;
                        fetchCivilServants();
                        resultsContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            });

        }
    })();
    </script>
    @endpush
