<?php

namespace App\Http\Controllers\Traits;

use App\Models\CivilServant;
use App\Models\Department;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

trait PhotoHelper
{
    protected function photoBaseUrl(): string
    {
        return rtrim(config('services.hrmis.photo_base_url', ''), '/');
    }

    protected function hrmisApiBase(): string
    {
        return rtrim(config('services.hrmis.photo_api_base', ''), '/');
    }

    protected function localPhotoPaths(CivilServant $civilServant, $image): array
    {
        $paths = [];
        $positionName = $civilServant->position->name_kh ?? null;

        if ($positionName) {
            $cleanName = preg_replace('/[\x{200B}\x{200C}\x{200D}\x{FEFF}\x{00AD}]+/u', '', $positionName);
            $paths[] = 'photos/' . $cleanName . '/' . $image->name;
        }
        $paths[] = 'photos/' . $image->name;
        $paths[] = $image->name;

        return $paths;
    }

    protected function resolveLocalPath(CivilServant $civilServant, $image): ?string
    {
        foreach ($this->localPhotoPaths($civilServant, $image) as $path) {
            try {
                if (Storage::disk('public')->exists($path)) {
                    return Storage::disk('public')->path($path);
                }
            } catch (\Exception) {
                continue;
            }
        }

        return null;
    }

    /**
     * Fetch a photo from the HRMIS API by civil servant ID.
     *
     * @return array{content: string, content_type: string}|null
     */
    protected function fetchRemotePhoto(string|int $civilServantId): ?array
    {
        $base = $this->hrmisApiBase();
        if (! $base) {
            return null;
        }

        try {
            $response = Http::timeout(10)->get($base . '/' . rawurlencode($civilServantId));
        } catch (\Exception) {
            return null;
        }

        if (! $response->successful() || $response->body() === '') {
            return null;
        }

        return [
            'content' => $response->body(),
            'content_type' => $response->header('Content-Type', 'image/jpeg'),
        ];
    }

    /**
     * Generic URL fetch helper.
     *
     * @return array{content: string, content_type: string}|null
     */
    protected function fetchUrl(string $url): ?array
    {
        try {
            $response = Http::timeout(10)->get($url);
        } catch (\Exception) {
            return null;
        }

        if (! $response->successful() || $response->body() === '') {
            return null;
        }

        return [
            'content' => $response->body(),
            'content_type' => $response->header('Content-Type', 'image/jpeg'),
        ];
    }

    protected function downloadFromString(string $content, string $filename, string $contentType = 'image/jpeg'): BinaryFileResponse
    {
        $tempPath = $this->writeTempFile($content);
        $response = response()->download($tempPath, $filename);
        $response->deleteFileAfterSend(true);
        $response->headers->set('Content-Type', $contentType);

        return $response;
    }

    protected function writeTempFile(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'photo_');
        file_put_contents($path, $content);

        return $path;
    }

    protected function extensionFromContentType(string $contentType): string
    {
        return match (true) {
            str_contains($contentType, 'png') => '.png',
            str_contains($contentType, 'gif') => '.gif',
            str_contains($contentType, 'webp') => '.webp',
            default => '.jpg',
        };
    }

    protected function departmentWithChildIds(int|string $departmentId): array
    {
        $ids = collect([(int) $departmentId]);
        $parentIds = $ids;

        for ($i = 0; $i < 5; $i++) {
            $childIds = Department::whereIn('parent_id', $parentIds)->pluck('id');
            if ($childIds->isEmpty()) {
                break;
            }
            $ids = $ids->merge($childIds);
            $parentIds = $childIds;
        }

        return $ids->unique()->toArray();
    }
}
