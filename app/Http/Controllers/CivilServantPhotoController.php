<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\PhotoHelper;
use App\Models\CivilServant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CivilServantPhotoController extends Controller
{
    use PhotoHelper;

    /**
     * Show a civil servant's photo (inline).
     */
    public function showPhoto(string $civilServantId): Response|RedirectResponse|StreamedResponse|BinaryFileResponse
    {
        $civilServant = CivilServant::with(['images', 'position'])->findOrFail($civilServantId);
        $image = $this->firstValidImageFromCollection($civilServant->images);

        abort_unless($image, 404);

        $photoBaseUrl = $this->photoBaseUrl();
        if ($photoBaseUrl) {
            return redirect($photoBaseUrl . '/' . rawurlencode($image->name));
        }

        $localPath = $this->resolveLocalPath($civilServant, $image);
        if ($localPath) {
            return response()->file($localPath);
        }

        $body = $this->fetchRemotePhoto($civilServantId);
        if ($body) {
            return response($body['content'], 200)
                ->header('Content-Type', $body['content_type'])
                ->header('Cache-Control', 'public, max-age=86400');
        }

        abort(404);
    }

    /**
     * Proxy an external HRMIS photo endpoint by civil servant ID.
     */
    public function proxyHrmisPhotoById(string $civilServantId): Response
    {
        $body = $this->fetchRemotePhoto($civilServantId);

        abort_unless($body, 404);

        return response($body['content'], 200)
            ->header('Content-Type', $body['content_type']);
    }

    /**
     * Download a single civil servant's photo.
     */
    public function downloadPhoto(string $civilServantId): BinaryFileResponse
    {
        $civilServant = CivilServant::with(['images', 'position'])->findOrFail($civilServantId);
        $image = $this->firstValidImageFromCollection($civilServant->images);

        abort_unless($image, 404, 'Photo not found');

        $downloadName = $civilServant->last_name_kh . '_' . $civilServant->first_name_kh . '_' . $image->name;

        $localPath = $this->resolveLocalPath($civilServant, $image);
        if ($localPath) {
            return response()->download($localPath, $downloadName);
        }

        $body = $this->fetchRemotePhoto($civilServantId);
        if ($body) {
            return $this->downloadFromString($body['content'], $downloadName, $body['content_type']);
        }

        $photoBaseUrl = $this->photoBaseUrl();
        if ($photoBaseUrl) {
            $body = $this->fetchUrl($photoBaseUrl . '/' . rawurlencode($image->name));
            if ($body) {
                return $this->downloadFromString($body['content'], $downloadName, $body['content_type']);
            }
        }

        abort(404, 'Photo not found');
    }
}
