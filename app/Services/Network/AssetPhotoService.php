<?php

namespace App\Services\Network;

use App\Models\AssetPhoto;
use App\Models\NetworkNode;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AssetPhotoService
{
    private const DISK_PATH = 'asset-photos';

    /** @return Collection<int, AssetPhoto> */
    public function forNode(NetworkNode $node): Collection
    {
        return $node->assetPhotos()
            ->with('uploader')
            ->latest()
            ->get();
    }

    public function store(
        NetworkNode $node,
        UploadedFile $file,
        string $photoType,
        ?string $caption = null,
    ): AssetPhoto {
        $path = $file->store(self::DISK_PATH.'/'.$node->uuid, 'public');

        return AssetPhoto::query()->create([
            'network_node_id' => $node->id,
            'photo_type' => $photoType,
            'file_path' => $path,
            'caption' => $caption,
            'uploaded_by' => Auth::id(),
        ]);
    }

    public function delete(AssetPhoto $photo): void
    {
        if (Storage::disk('public')->exists($photo->file_path)) {
            Storage::disk('public')->delete($photo->file_path);
        }

        $photo->delete();
    }

    public function publicUrl(AssetPhoto $photo): string
    {
        return Storage::disk('public')->url($photo->file_path);
    }

    /** @return array{location: bool, device: bool} */
    public function complianceStatus(NetworkNode $node): array
    {
        $types = $node->assetPhotos()->pluck('photo_type');

        return [
            'location' => $types->contains('location'),
            'device' => $types->contains('device'),
            'is_compliant' => $types->contains('location') && $types->contains('device'),
        ];
    }
}
