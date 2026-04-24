<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\PhotoHelper;
use App\Models\CivilServant;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\Http;
use ZipStream\ZipStream;

class CivilservantIdController extends Controller
{
    use PhotoHelper;

    private const ALLOWED_SORTS = [
        'last_name_kh',
        'first_name_kh',
        'gender_id',
        'position_id',
        'department_id',
        'sort',
    ];

    public function index(Request $request)
    {
        $request->validate([
            'name_kh'       => 'nullable|string|max:255',
            'department_id' => 'nullable|integer',
            'parent_id'     => 'nullable|integer',
            'position_id'   => 'nullable|integer',
            'has_document'  => 'nullable|boolean',
            'sort_by'       => 'nullable|string|in:' . implode(',', self::ALLOWED_SORTS),
            'sort_dir'      => 'nullable|string|in:asc,desc',
        ]);

        $filters = $request->only(['name_kh', 'department_id', 'parent_id', 'position_id', 'has_document', 'sort_by', 'sort_dir']);

        $query = CivilServant::with([
            'department.parent.parent',
            'position:id,name_kh,name_short,abb,sort',
        ])->select('civil_servants.*');

        $this->applyFilters($query, $request);
        $this->applySorting($query, $request);

        $departments = Cache::remember('dept_tree_top', 300, function () {
            return Department::select('id', 'name_kh', 'parent_id', 'sort', 'active')
                ->where(function ($q) {
                    $q->whereIn('parent_id', [1, 2])
                        ->orWhereIn('id', [1, 2]);
                })->where('active', 1)
                ->orderByRaw('CASE WHEN id = ? OR parent_id = ? THEN 0 WHEN id = ? OR parent_id = ? THEN 2 ELSE 1 END ASC', [1, 1, 2, 2])
                ->orderByRaw('CASE WHEN id IN (?, ?) THEN 0 ELSE 1 END ASC', [1, 2])
                ->orderByRaw('COALESCE(`sort`, id) ASC')
                ->get()->toArray();
        });
        $departments = collect($departments)->map(fn($d) => (object) $d);

        $childDepartments = collect();
        if ($request->filled('department_id')) {
            $deptId = $request->input('department_id');
            $childDepartments = Cache::remember('dept_children_list_' . $deptId, 300, function () use ($deptId) {
                return Department::select('id', 'name_kh', 'parent_id', 'sort')
                    ->where('parent_id', $deptId)
                    ->where('active', 1)
                    ->orderBy('sort')
                    ->get()->toArray();
            });
            $childDepartments = collect($childDepartments)->map(fn($d) => (object) $d);
        }

        $allChildDepts = Cache::remember('dept_tree_all_children', 300, function () use ($departments) {
            return Department::select('id', 'name_kh', 'parent_id', 'sort')
                ->whereIn('parent_id', $departments->pluck('id'))
                ->where('active', 1)
                ->orderBy('sort')
                ->get()->toArray();
        });
        $allChildDepts = collect($allChildDepts)->map(fn($d) => (object) $d)->groupBy('parent_id');

        $deptGroupMap = [];
        foreach ($departments as $d) {
            $deptGroupMap[$d->id] = $d->name_kh;
            if (isset($allChildDepts[$d->id])) {
                foreach ($allChildDepts[$d->id] as $child) {
                    $deptGroupMap[$child->id] = $d->name_kh;
                }
            }
        }

        $idCardCivilServantIds    = $this->getCivilServantIdsWithIdCard();
        $deltaDocCivilServantIds  = $this->getCivilServantIdsWithDeltaDoc();

        // Apply filtering for has_document (id card) if requested
        if ($request->boolean('has_document')) {
            $ids = $idCardCivilServantIds;
            if ($ids->isEmpty()) {
                $query->whereRaw('0 = 1');
            } else {
                $query->whereIn('civil_servants.id', $ids->all());
            }
        }
        $paginated = $query->paginate(20)->withQueryString();
        $paginated->getCollection()->transform(function ($cs) use ($idCardCivilServantIds, $deltaDocCivilServantIds) {
            $dept   = $cs->department;
            $sub    = $dept?->parent;
            $parent = $sub?->parent;

            $cs->department_name        = $dept->name_kh ?? null;
            $cs->sub_department_id      = $sub->id ?? null;
            $cs->sub_department_name    = $sub->name_kh ?? null;
            $cs->parent_department_id   = $parent->id ?? null;
            $cs->parent_department_name = $parent->name_kh ?? null;
            $cs->has_id_card            = $idCardCivilServantIds->contains($cs->id);
            $cs->has_delta_doc          = $deltaDocCivilServantIds->contains($cs->id);

            return $cs;
        });

        return view('civil-servants-id.index', [
            'civilServants'    => $paginated,
            'subDepartments'   => $departments,
            'childDepartments' => $childDepartments,
            'allChildDepts'    => $allChildDepts,
            'positions'        => $this->getFilteredPositions($request),
            'filters'          => $filters,
            'deptGroupMap'     => $deptGroupMap,
        ]);
    }

    public function ajaxSearch(Request $request): JsonResponse
    {
        $request->validate([
            'name_kh'       => 'nullable|string|max:255',
            'department_id' => 'nullable|integer',
            'parent_id'     => 'nullable|integer',
            'position_id'   => 'nullable|integer',
            'has_document'  => 'nullable|boolean',
            'sort_by'       => 'nullable|string|in:' . implode(',', self::ALLOWED_SORTS),
            'sort_dir'      => 'nullable|string|in:asc,desc',
            'per_page'      => 'nullable|integer|min:1|max:100',
        ]);

        $query = CivilServant::with([
            'department.parent.parent',
            'position:id,name_kh,name_short,abb,sort',
        ])->select('civil_servants.*');

        $this->applyFilters($query, $request);
        $this->applySorting($query, $request);

        $idCardCivilServantIds   = $this->getCivilServantIdsWithIdCard();
        $deltaDocCivilServantIds = $this->getCivilServantIdsWithDeltaDoc();

        if ($request->boolean('has_document')) {
            if ($idCardCivilServantIds->isEmpty()) {
                $query->whereRaw('0 = 1');
            } else {
                $query->whereIn('civil_servants.id', $idCardCivilServantIds->all());
            }
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $paginated = $query->paginate($perPage)->withQueryString();

        $paginated->getCollection()->transform(function ($cs) use ($idCardCivilServantIds, $deltaDocCivilServantIds) {
            $dept   = $cs->department;
            $sub    = $dept?->parent;
            $parent = $sub?->parent;

            $cs->department_name        = $dept->name_kh ?? null;
            $cs->sub_department_id      = $sub->id ?? null;
            $cs->sub_department_name    = $sub->name_kh ?? null;
            $cs->parent_department_id   = $parent->id ?? null;
            $cs->parent_department_name = $parent->name_kh ?? null;
            $cs->has_id_card            = $idCardCivilServantIds->contains($cs->id);
            $cs->has_delta_doc          = $deltaDocCivilServantIds->contains($cs->id);

            return $cs;
        });

        return response()->json($paginated);
    }

    public function downloadPdf(Request $request)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(600);

        $request->validate([
            'name_kh'       => 'nullable|string|max:255',
            'department_id' => 'nullable|integer',
            'parent_id'     => 'nullable|integer',
            'position_id'   => 'nullable|integer',
            'has_document'  => 'nullable|boolean',
            'sort_by'       => 'nullable|string|in:' . implode(',', self::ALLOWED_SORTS),
            'sort_dir'      => 'nullable|string|in:asc,desc',
        ]);

        $query = CivilServant::with([
            'department.parent.parent',
            'position:id,name_kh,name_short,abb,sort',
        ])->select('civil_servants.*');

        $this->applyFilters($query, $request);
        $this->applySorting($query, $request);

        $idCardCivilServantIds = $this->getCivilServantIdsWithIdCard();
        if ($request->boolean('has_document')) {
            if ($idCardCivilServantIds->isEmpty()) {
                $query->whereRaw('0 = 1');
            } else {
                $query->whereIn('civil_servants.id', $idCardCivilServantIds->all());
            }
        }
        $total = (clone $query)->count();
        // Derive a friendly filename when department filter is present
        $deptFilter = $request->input('parent_id') ?? $request->input('department_id');
        $deptLabel = null;
        if ($deptFilter) {
            $deptModel = Department::find($deptFilter);
            $deptLabel = $deptModel && ! empty($deptModel->name_kh) ? $deptModel->name_kh : null;
        }
        $baseName = $deptLabel ? trim(preg_replace('/[\/\\:*?"<>|\x00-\x1F]/', '', $deptLabel)) : 'civil-servants-id-card-list';
        $filename = $baseName . '-' . now()->format('Y-m-d') . '.pdf';
        // ASCII fallback for Content-Disposition (some clients prefer an ASCII filename)
        $asciiBase = trim(preg_replace('/[^\x20-\x7E]/', '', $baseName), '-_ ');
        if (empty($asciiBase)) {
            $asciiBase = 'civil-servants-id-card-list';
        }
        $asciiFilename = $asciiBase . '-' . now()->format('Y-m-d') . '.pdf';

        $civilServants = $query->get()->map(fn ($cs) => $this->transformForPdf($cs, $idCardCivilServantIds));

        $html = view('civil-servants-id.pdf', [
            'civilServants' => $civilServants,
            'total'         => $total,
            'offset'        => 0,
        ])->render();

        $mpdf = $this->createMpdf('A4-L');
        $mpdf->WriteHTML($html);
        $content = $mpdf->Output('', 'S');

        return response($content, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$asciiFilename}\"; filename*=UTF-8''" . rawurlencode($filename),
        ]);
    }

    public function downloadSinglePdf($id)
    {
        ini_set('memory_limit', '256M');
        set_time_limit(120);

        $cs = CivilServant::with([
            'department.parent.parent',
            'position:id,name_kh,name_short,abb,sort',
        ])->select('civil_servants.*')->findOrFail($id);

        $idCardCivilServantIds = $this->getCivilServantIdsWithIdCard();
        $cs = $this->transformForPdf($cs, $idCardCivilServantIds);

        $deptLabel = ! empty($cs->department_name) ? $cs->department_name : null;
        $baseName  = $deptLabel
            ? trim(preg_replace('/[\/\\:*?"<>|\x00-\x1F]/', '', $deptLabel))
            : 'civil-servant-' . $cs->id;
        $filename  = $baseName . '-' . now()->format('Y-m-d') . '.pdf';
        $asciiBase = trim(preg_replace('/[^\x20-\x7E]/', '', $baseName), '-_ ');
        if (empty($asciiBase)) {
            $asciiBase = 'civil-servant-' . $cs->id;
        }
        $asciiFilename = $asciiBase . '-' . now()->format('Y-m-d') . '.pdf';

        $html = view('civil-servants-id.pdf', [
            'civilServants' => collect([$cs]),
            'total'         => 1,
            'offset'        => 0,
        ])->render();

        $mpdf = $this->createMpdf('A4-L');
        $mpdf->WriteHTML($html);
        $content = $mpdf->Output('', 'S');

        return response($content, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$asciiFilename}\"; filename*=UTF-8''" . rawurlencode($filename),
        ]);
    }

    public function downloadIdCardDocPdf($id)
    {
        set_time_limit(120);

        $baseUrl = rtrim(config('services.hrmis.document_base_url', 'https://mef-pd.net/hrmis/civilservant/viewDocument'), '/');

        $csName = $this->csFilename($id);

        // Find all documents linked to this civil servant with document_type_id = 16 (id cards)
        $documents = DB::table('document_deltas')
            ->join('documents', 'documents.id', '=', 'document_deltas.document_id')
            ->where('document_deltas.civil_servant_id', $id)
            ->where('documents.document_type_id', 16)
            ->select('documents.id as doc_id', 'documents.name as doc_name', 'documents.code')
            ->orderBy('documents.id')
            ->get();

        if ($documents->isEmpty()) {
            return redirect()->route('civilservant-id.index')
                ->with('error', 'មន្រ្តីរូបនេះមិនមានឯកសារអត្តសញ្ញណប័ណ្ណទេ។');
        }

        $jar = $this->hrmisAuthenticatedClient();

        if ($documents->count() === 1) {
            $doc      = $documents->first();
            $response = Http::withOptions(['cookies' => $jar])->timeout(30)->get($baseUrl . '/' . $doc->doc_id);

            $contentType = $response->header('Content-Type', 'application/octet-stream');
            $body        = $response->body();
            $statusCode  = $response->status();

            $isHtml = str_starts_with(ltrim($body), '<') ||
                      str_contains(strtolower($contentType), 'text/html');

            if ($isHtml || ! $response->successful()) {
                \Illuminate\Support\Facades\Log::error('HRMIS id-card document fetch failed', [
                    'url'          => $baseUrl . '/' . $doc->doc_id,
                    'status'       => $statusCode,
                    'content_type' => $contentType,
                    'body_preview' => substr($body, 0, 500),
                ]);

                return redirect()->route('civilservant-id.index')
                    ->with('error', 'មិនអាចទាញយកឯកសារបានទេ (HTTP ' . $statusCode . '). សូមមើល laravel.log សម្រាប់ព័ត៌មានលម្អិត។');
            }

            $ext      = $this->documentExtension($contentType, $doc->doc_name, $body);
            $filename = $csName . '-id-card' . $ext;

            return response($body, 200, [
                'Content-Type'        => $contentType,
                'Content-Disposition' => "attachment; filename*=UTF-8''" . rawurlencode($filename),
            ]);
        }

        // Multiple documents → bundle into a ZIP
        $tempDir = storage_path('app/temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zipPath = $tempDir . '/id-card-docs-' . $id . '-' . now()->format('YmdHis') . '.zip';
        $zip     = new \ZipArchive();

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Could not create ZIP archive.');
        }

        foreach ($documents as $doc) {
            $response = Http::withOptions(['cookies' => $jar])->timeout(30)->get($baseUrl . '/' . $doc->doc_id);
            if (! $response->successful()) {
                continue;
            }
            $contentType = $response->header('Content-Type', 'application/octet-stream');
            $body        = $response->body();
            $ext         = $this->documentExtension($contentType, $doc->doc_name, $body);
            $entryName   = $csName . '-id-card' . $ext;
            $zip->addFromString($entryName, $body);
        }

        $zip->close();

        $downloadName = $csName . '-id-card-documents.zip';
        $headers = [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => "attachment; filename*=UTF-8''" . rawurlencode($downloadName),
        ];
        return response()->download($zipPath, $downloadName, $headers)->deleteFileAfterSend(true);
    }

    public function downloadDeltaDocPdf($id)
    {
        set_time_limit(120);

        $baseUrl = rtrim(config('services.hrmis.document_base_url', 'https://mef-pd.net/hrmis/civilservant/viewDocument'), '/');

        $csName = $this->csFilename($id);

        // Find all documents linked to this civil servant with document_type_id = 10 (delta docs)
        $documents = DB::table('document_deltas')
            ->join('documents', 'documents.id', '=', 'document_deltas.document_id')
            ->where('document_deltas.civil_servant_id', $id)
            ->where('documents.document_type_id', 10)
            ->select('documents.id as doc_id', 'documents.name as doc_name', 'documents.code')
            ->orderBy('documents.id')
            ->get();

        if ($documents->isEmpty()) {
            return redirect()->route('civilservant-id.index')
                ->with('error', 'មន្រ្តីរូបនេះមិនមានឯកសារប្រភេទទី ១៦ទេ។');
        }

        $jar = $this->hrmisAuthenticatedClient();

        if ($documents->count() === 1) {
            // Stream single document directly
            $doc      = $documents->first();
            $response = Http::withOptions(['cookies' => $jar])->timeout(30)->get($baseUrl . '/' . $doc->doc_id);

            $contentType  = $response->header('Content-Type', 'application/octet-stream');
            $body         = $response->body();
            $statusCode   = $response->status();
            $allHeaders   = $response->headers();
            $bodyPreview  = substr($body, 0, 500);

            // Detect if HRMIS returned HTML instead of a file (login redirect / error page)
            $isHtml = str_starts_with(ltrim($body), '<') ||
                      str_contains(strtolower($contentType), 'text/html');

            if ($isHtml || ! $response->successful()) {
                \Illuminate\Support\Facades\Log::error('HRMIS document fetch failed', [
                    'url'         => $baseUrl . '/' . $doc->doc_id,
                    'status'      => $statusCode,
                    'content_type'=> $contentType,
                    'body_preview'=> $bodyPreview,
                    'headers'     => $allHeaders,
                ]);

                return redirect()->route('civilservant-id.index')
                    ->with('error', 'មិនអាចទាញយកឯកសារបានទេ (HTTP ' . $statusCode . '). សូមមើល laravel.log សម្រាប់ព័ត៌មានលម្អិត។');
            }

            $ext      = $this->documentExtension($contentType, $doc->doc_name, $body);
            $filename = $csName . '-document' . $ext;

            return response($body, 200, [
                'Content-Type'        => $contentType,
                'Content-Disposition' => "attachment; filename*=UTF-8''" . rawurlencode($filename),
            ]);
        }

        // Multiple documents → bundle into a ZIP
        $tempDir = storage_path('app/temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zipPath = $tempDir . '/docs-' . $id . '-' . now()->format('YmdHis') . '.zip';
        $zip     = new \ZipArchive();

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Could not create ZIP archive.');
        }

        foreach ($documents as $doc) {
            $response = Http::withOptions(['cookies' => $jar])->timeout(30)->get($baseUrl . '/' . $doc->doc_id);
            if (! $response->successful()) {
                continue;
            }
            $contentType = $response->header('Content-Type', 'application/octet-stream');
            $body        = $response->body();
            $ext         = $this->documentExtension($contentType, $doc->doc_name, $body);
            $entryName   = $csName . '-document' . $ext;
            $zip->addFromString($entryName, $body);
        }

        $zip->close();

        $downloadName = $csName . '-documents.zip';
        $headers = [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => "attachment; filename*=UTF-8''" . rawurlencode($downloadName),
        ];
        return response()->download($zipPath, $downloadName, $headers)->deleteFileAfterSend(true);
    }

    private function createMpdf(string $format = 'A4-L'): \Mpdf\Mpdf
    {
        // Merge our Khmer fonts into mPDF defaults so built-in font references keep working
        $defaultFontdata = (new \Mpdf\Config\FontVariables())->getDefaults()['fontdata'];
        $fontdata = array_merge($defaultFontdata, config('mpdf.fontdata'));

        $tempDir = storage_path('app/mpdf-tmp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        return new \Mpdf\Mpdf([
            'mode'              => 'utf-8',
            'format'            => $format,
            'margin_top'        => 10,
            'margin_right'      => 10,
            'margin_bottom'     => 10,
            'margin_left'       => 10,
            'tempDir'           => storage_path('app/mpdf-tmp'),
            'fontDir'           => [config('mpdf.font_dir')],
            'fontdata'          => $fontdata,
            'default_font'      => config('mpdf.default_font'),
            'autoScriptToLang'  => true,
            'autoLangToFont'    => true,
            'useKerning'        => true,
        ]);
    }

    /**
     * Return a safe filename prefix using the civil servant's full Khmer name.
     */
    private function csFilename(int|string $id): string
    {
        $cs = DB::table('civil_servants')
            ->where('id', $id)
            ->select('last_name_kh', 'first_name_kh')
            ->first();

        $name = trim(($cs->last_name_kh ?? '') . ' ' . ($cs->first_name_kh ?? ''));

        if ($name === '') {
            $name = 'civil-servant-' . $id;
        }

        // Strip characters that are invalid in filenames on any OS
        $name = preg_replace('/[\/\\\\:*?"<>|]/', '', $name);
        $name = trim($name);

        return $name !== '' ? $name : 'civil-servant-' . $id;
    }

    /**
     * Login to HRMIS and return an authenticated cookie jar.
     */
    private function hrmisAuthenticatedClient(): CookieJar
    {
        $jar      = new CookieJar();
        $loginUrl = config('services.hrmis.login_url', 'https://mef-pd.net/hrmis/postLogin');
        $baseHost = parse_url($loginUrl, PHP_URL_SCHEME) . '://' . parse_url($loginUrl, PHP_URL_HOST);

        // Step 1: Fetch login page to get CSRF token
        $loginPage = Http::withOptions(['cookies' => $jar])
            ->timeout(15)
            ->get($baseHost . '/hrmis/login');

        preg_match('/name="_token" value="([^"]+)"/', $loginPage->body(), $m);
        $csrf = $m[1] ?? '';

        // Step 2: POST credentials
        Http::withOptions(['cookies' => $jar, 'allow_redirects' => false])
            ->timeout(15)
            ->asForm()
            ->post($loginUrl, [
                '_token'   => $csrf,
                'username' => config('services.hrmis.username'),
                'password' => config('services.hrmis.password'),
            ]);

        return $jar;
    }

    private function documentExtension(string $contentType, ?string $filename = null, ?string $body = null): string
    {
        // 1. Try DB filename extension first
        if ($filename && str_contains($filename, '.')) {
            $ext = '.' . strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (strlen($ext) > 1 && strlen($ext) <= 5 && $ext !== '.bin') {
                return $ext;
            }
        }

        // 2. Try Content-Type header
        $fromHeader = match (true) {
            str_contains($contentType, 'pdf')              => '.pdf',
            str_contains($contentType, 'msword')           => '.doc',
            str_contains($contentType, 'wordprocessingml') => '.docx',
            str_contains($contentType, 'ms-excel')         => '.xls',
            str_contains($contentType, 'spreadsheetml')    => '.xlsx',
            str_contains($contentType, 'png')              => '.png',
            str_contains($contentType, 'jpeg'),
            str_contains($contentType, 'jpg')              => '.jpg',
            default                                        => null,
        };

        if ($fromHeader) {
            return $fromHeader;
        }

        // 3. Magic byte detection from actual file content
        if ($body && strlen($body) >= 4) {
            $magic = substr($body, 0, 8);

            if (str_starts_with($magic, '%PDF'))         return '.pdf';
            if (str_starts_with($magic, "\xFF\xD8\xFF")) return '.jpg';
            if (str_starts_with($magic, "\x89PNG"))      return '.png';
            // ZIP-based: DOCX, XLSX, ODT, etc.
            if (str_starts_with($magic, "PK\x03\x04") || str_starts_with($magic, "PK\x05\x06")) {
                // Peek inside ZIP to distinguish DOCX vs XLSX
                if (str_contains($body, 'word/'))        return '.docx';
                if (str_contains($body, 'xl/'))          return '.xlsx';
                return '.zip';
            }
            // OLE2 compound (legacy .doc, .xls)
            if (str_starts_with($magic, "\xD0\xCF\x11\xE0")) {
                if (str_contains($body, 'W\x00o\x00r\x00d')) return '.doc';
                return '.xls';
            }
        }

        return '.pdf'; // safe default — most HRMIS documents are PDFs
    }

    private function transformForPdf($cs, $idCardCivilServantIds)
    {
        $dept = $cs->department;
        $sub  = $dept?->parent;

        $cs->department_name     = $dept->name_kh ?? null;
        $cs->sub_department_name = $sub->name_kh ?? null;
        $cs->has_id_card         = $idCardCivilServantIds->contains($cs->id);

        return $cs;
    }

    // ──────────────────────────────────────────────
    //  Private helpers
    // ──────────────────────────────────────────────

    private function getCivilServantIdsWithIdCard(): \Illuminate\Support\Collection
    {
        $ids = Cache::remember('civil_servant_ids_with_id_card', 300, function () {
            return DB::table('document_deltas')
                ->join('documents', 'documents.id', '=', 'document_deltas.document_id')
                ->where('documents.document_type_id', 16)
                ->distinct()
                ->pluck('document_deltas.civil_servant_id')
                ->all();
        });

        return collect($ids);
    }

    private function getCivilServantIdsWithDeltaDoc(): \Illuminate\Support\Collection
    {
        $ids = Cache::remember('civil_servant_ids_with_delta_doc', 300, function () {
            return DB::table('document_deltas')
                ->join('documents', 'documents.id', '=', 'document_deltas.document_id')
                ->where('documents.document_type_id', 10)
                ->distinct()
                ->pluck('document_deltas.civil_servant_id')
                ->all();
        });

        return collect($ids);
    }

    /**
     * Download a ZIP of all ID card documents (document_type_id = 16) for a department (and its children).
     */
    public function downloadDepartment(int $departmentId)
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        $deptIds = $this->departmentWithChildIds($departmentId);

        // Stream/stream-to-file approach similar to DepartmentController
        $csQuery = \App\Models\CivilServant::with(['department:id,parent_id,name_kh'])
            ->whereIn('department_id', $deptIds)
            ->where('status_type_id', 1);

        $csIterator = $csQuery->cursor();

        $dept = \App\Models\Department::find($departmentId);
        $deptName = ($dept && ! empty($dept->name_kh)) ? $dept->name_kh : 'department_' . $departmentId;

        // Load all departments in the tree keyed by id
        $allDepts = \App\Models\Department::whereIn('id', $deptIds)->get()->keyBy('id');

        // Build folder path for each department by walking up to the root
        $deptFolderPaths = [];
        foreach ($allDepts as $d) {
            $segments = [];
            $current = $d;
            while ($current) {
                $segments[] = $current->name_kh ?: ('dept_' . $current->id);
                if ($current->id == $departmentId) {
                    break;
                }
                $current = $allDepts->get($current->parent_id);
            }
            $deptFolderPaths[$d->id] = implode('/', array_reverse($segments));
        }

        $deptCounters = [];

        $safeZipName = (trim(preg_replace('/[\/\\:*?"<>|\x00-\x1F]/', '', $deptName)) ?: 'department_' . $departmentId) . '.zip';
        $zipPath = storage_path('app/temp/' . $safeZipName);

        if (! is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $outputStream = fopen($zipPath, 'wb');
        $zip = new ZipStream(
            outputStream: $outputStream,
            sendHttpHeaders: false,
            defaultCompressionMethod: \ZipStream\CompressionMethod::STORE,
            enableZip64: false,
            defaultEnableZeroHeader: false,
        );

        $baseUrl = rtrim(config('services.hrmis.document_base_url', 'https://mef-pd.net/hrmis/civilservant/viewDocument'), '/');
        $jar = $this->hrmisAuthenticatedClient();
        $totalAdded = 0;

        foreach ($csIterator as $civilServant) {
            $deptId = $civilServant->department_id;
            $folderPrefix = ($deptFolderPaths[$deptId] ?? $deptName) . '/';

            $documents = \Illuminate\Support\Facades\DB::table('document_deltas')
                ->join('documents', 'documents.id', '=', 'document_deltas.document_id')
                ->where('document_deltas.civil_servant_id', $civilServant->id)
                ->where('documents.document_type_id', 16)
                ->select('documents.id as doc_id', 'documents.name as doc_name')
                ->orderBy('documents.id')
                ->get();

            if ($documents->isEmpty()) {
                continue;
            }

            $number = $deptCounters[$deptId] ?? 1;
            $baseName = trim(($civilServant->last_name_kh ?? '') . '_' . ($civilServant->first_name_kh ?? '')) ?: ('cs_' . $civilServant->id);

            foreach ($documents as $doc) {
                try {
                    $response = Http::withOptions(['cookies' => $jar])->timeout(30)->get($baseUrl . '/' . $doc->doc_id);
                } catch (\Exception $e) {
                    continue;
                }

                if (! $response->successful()) {
                    continue;
                }

                $contentType = $response->header('Content-Type', 'application/octet-stream');
                $body = $response->body();
                $ext = $this->documentExtension($contentType, $doc->doc_name, $body);

                $numberedPrefix = $number . '_';
                $entryName = $numberedPrefix . $baseName . '-id-card' . $ext;
                $zip->addFile(fileName: $folderPrefix . $entryName, data: $body);
                $number++;
                $deptCounters[$deptId] = $number;
                $totalAdded++;
            }
        }

        $zip->finish();
        fclose($outputStream);

        if ($totalAdded === 0) {
            @unlink($zipPath);

            return back()->with('error', 'No ID card documents found for this department');
        }

        $downloadName = $deptName . '-id-cards.zip';
        $headers = [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => "attachment; filename*=UTF-8''" . rawurlencode($downloadName),
        ];

        return response()->download($zipPath, $downloadName, $headers)->deleteFileAfterSend(true);
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        $query->where('civil_servants.status_type_id', 1);

        if ($request->filled('name_kh')) {
            $search = $request->input('name_kh');
            $query->where(function (Builder $q) use ($search) {
                $q->where('last_name_kh', 'like', "%{$search}%")
                  ->orWhere('first_name_kh', 'like', "%{$search}%");
            });
        }

        if ($request->filled('parent_id')) {
            $query->whereIn('civil_servants.department_id', $this->departmentWithChildIds($request->input('parent_id')));
        } elseif ($request->filled('department_id')) {
            $query->whereIn('civil_servants.department_id', $this->departmentWithChildIds($request->input('department_id')));
        }

        if ($request->filled('position_id')) {
            $query->where('position_id', $request->input('position_id'));
        }
    }

    private function applySorting(Builder $query, Request $request): void
    {
        $sortBy  = $request->input('sort_by', 'position_id');
        $sortDir = $request->input('sort_dir', 'asc') === 'desc' ? 'desc' : 'asc';

        if (! in_array($sortBy, self::ALLOWED_SORTS, true)) {
            $sortBy = 'position_id';
        }

        if ($sortBy === 'position_id') {
            $query->orderBy(
                Position::select('sort')->whereColumn('positions.id', 'civil_servants.position_id')->limit(1),
                $sortDir
            );
        } elseif ($sortBy === 'department_id') {
            $query->orderByRaw(
                'CASE WHEN (SELECT parent_id FROM departments WHERE id = civil_servants.department_id) IN (0,1) OR civil_servants.department_id = 1 THEN 0
                      WHEN (SELECT parent_id FROM departments WHERE id = civil_servants.department_id) = 2 OR civil_servants.department_id = 2 THEN 2
                      ELSE 1 END ASC'
            )->orderBy(
                Department::select('sort')->whereColumn('departments.id', 'civil_servants.department_id')->limit(1),
                $sortDir
            );
        } else {
            $query->orderBy($sortBy, $sortDir);
        }
    }

    private function getFilteredPositions(Request $request): \Illuminate\Database\Eloquent\Collection
    {
        $posQuery = Position::select('id', 'name_kh', 'name_short', 'abb', 'sort')
            ->where('active', 1);

        if ($request->filled('parent_id')) {
            $deptIds = $this->departmentWithChildIds($request->input('parent_id'));
            $posQuery->whereHas('civilServants', function ($q) use ($deptIds) {
                $q->where('status_type_id', 1)->whereIn('department_id', $deptIds);
            });
        } elseif ($request->filled('department_id')) {
            $deptIds = $this->departmentWithChildIds($request->input('department_id'));
            $posQuery->whereHas('civilServants', function ($q) use ($deptIds) {
                $q->where('status_type_id', 1)->whereIn('department_id', $deptIds);
            });
        } else {
            $posQuery->whereHas('civilServants', function ($q) {
                $q->where('status_type_id', 1);
            });
        }

        if ($request->boolean('has_document')) {
            $ids = $this->getCivilServantIdsWithIdCard();
            if ($ids->isEmpty()) {
                // No positions if no matching civil servants
                $posQuery->whereRaw('0 = 1');
            } else {
                $posQuery->whereHas('civilServants', function ($q) use ($ids) {
                    $q->where('status_type_id', 1)->whereIn('civil_servants.id', $ids->all());
                });
            }
        }

        return $posQuery->orderBy('sort')->get();
    }
}
