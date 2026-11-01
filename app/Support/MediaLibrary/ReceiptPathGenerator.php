<?php

declare(strict_types=1);

namespace App\Support\MediaLibrary;

use App\Models\Transaction;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

/**
 * Stores transaction receipts under receipts/{owner user_id}/media/{media uuid}/.
 *
 * The owner is read without global scopes, so the path is the same in a web
 * request, in a console command and for soft-deleted transactions (the
 * package resolves this path again when it deletes the files).
 */
class ReceiptPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        return 'receipts/'.$this->ownerId($media).'/media/'.$media->uuid.'/';
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getPath($media).'conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getPath($media).'responsive/';
    }

    protected function ownerId(Media $media): string
    {
        // An in-memory owner (set by ReceiptService::attach) still resolves
        // the path after a rollback removed the transaction row.
        if ($media->relationLoaded('model') && $media->getRelation('model') instanceof Transaction) {
            return (string) $media->getRelation('model')->user_id;
        }

        $userId = Transaction::withoutGlobalScopes()
            ->whereKey($media->model_id)
            ->value('user_id');

        return filled($userId) ? (string) $userId : 'unknown';
    }
}
