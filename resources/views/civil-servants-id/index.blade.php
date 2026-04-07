@extends('layout.main')

@section('title', 'អត្តសញ្ញណប័ណ្ណ')
@section('page-title', 'អត្តសញ្ញណប័ណ្ណ')
@section('page-subtitle', 'បញ្ជីមន្រ្តីរាជការស៊ីវិលដែលមានអត្តសញ្ញណប័ណ្ណ')

@section('content')
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
                    <div class="card-header-custom">
                        <span class="result-count">
                            <i class="bi bi-people"></i> {{ $civilServants->total() }} មន្រ្តីរាជការរកឃើញ
                        </span>
                        <a href="{{ route('civilservant-id.download-pdf', request()->query()) }}" class="btn btn-success-custom">
                            <i class="bi bi-file-earmark-pdf me-1"></i> ទាញយកបញ្ជី PDF
                        </a>
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
                                        <th style="width:140px">អត្តសញ្ញាណប័ណ្ណ</th>
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
                                            <tr class="position-group-row">
                                                <td colspan="7">
                                                    <strong><i class="bi bi-building me-1"></i>{{ $nayokName }}</strong>
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
                                            <td>
                                                @if($emp->has_id_card)
                                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>មាន</span>
                                                @else
                                                    <span class="badge bg-secondary"><i class="bi bi-x-circle me-1"></i>គ្មាន</span>
                                                @endif
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

        function fetchCivilServants(rebuildPositions) {
            const params = new URLSearchParams();
            const name = nameInput.value.trim();
            currentDeptId = deptSelect.value;
            currentDeptName = deptSelect.options[deptSelect.selectedIndex]?.text?.trim() || '';
            currentPosId = posSelect.value;
            const generalDeptId = generalDeptSelect.value;

            if (name) params.append('name_kh', name);
            if (currentDeptId) {
                params.append('parent_id', currentDeptId);
            } else if (generalDeptId) {
                params.append('department_id', generalDeptId);
            }
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
                        <div class="card-header-custom">
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

                pageItems.forEach(function(emp, i) {
                const globalIndex = (startIndex || 1) + i;
                const name = escapeHtml((emp.last_name_kh || '') + ' ' + (emp.first_name_kh || '')).trim();
                const sex = emp.gender_id == 1 ? '\u1794\u17d2\u179a\u17bb\u179f' : '\u179f\u17d2\u179a\u17b8';
                const title = emp.position ? escapeHtml(emp.position.name_kh || emp.position.name_short || emp.position.abb || 'N/A') : 'N/A';
                const deptName = emp.department ? escapeHtml(emp.department.name_kh || 'N/A') : 'N/A';

                // Position group header row
                if (currentSortBy === 'position_id' && title !== lastGroupName) {
                    rows += `<tr class="position-group-row">
                        <td colspan="7"><strong><i class="bi bi-bookmark-fill me-1"></i>${title}</strong></td>
                    </tr>`;
                    lastGroupName = title;
                }

                // Department group header row
                if (currentSortBy === 'department_id' && deptName !== lastGroupName) {
                    rows += `<tr class="position-group-row">
                        <td colspan="7"><strong><i class="bi bi-building me-1"></i>${deptName}</strong></td>
                    </tr>`;
                    lastGroupName = deptName;
                }

                const subDeptName = emp.sub_department_name ? escapeHtml(emp.sub_department_name) : (emp.sub_department ? escapeHtml(emp.sub_department.name_kh || '') : 'N/A');

                const idCardBadge = emp.has_id_card
                    ? '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>\u1798\u17b6\u1793</span>'
                    : '<span class="badge bg-secondary"><i class="bi bi-x-circle me-1"></i>\u1782\u17d2\u1798\u17b6\u1793</span>';

                rows += `<tr>
                    <td class="text-muted fw-medium">${globalIndex}</td>
                    <td><span class="emp-name">${name}</span></td>
                    <td>${sex}</td>
                    <td>${title}</td>
                    <td><span class="dept-cell">${deptName}</span></td>
                    <td><span class="dept-cell">${subDeptName}</span></td>
                    <td>${idCardBadge}</td>
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
                    <div class="card-header-custom">
                        <span class="result-count"><i class="bi bi-people"></i> ${total} មន្រ្តីរាជការរកឃើញ</span>
                        ${pdfBtn}
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
                                <th style="width:140px">អត្តសញ្ញាណប័ណ្ណ</th>
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
