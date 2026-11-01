<?php

declare(strict_types=1);

namespace App\Interfaces\Services;

use App\Models\Transaction;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

interface ReceiptServiceInterface
{
    /**
     * Store $file as the transaction's receipt, replacing any previous one
     * (see remove()). Returns the new media so a caller whose DB transaction
     * fails can discard() the written file.
     *
     * @throws ValidationException when the content type or size is not accepted
     */
    public function attach(Transaction $transaction, UploadedFile $file): Media;

    /**
     * Remove the receipt media. The rows are deleted now; the files are
     * deleted after the surrounding DB transaction commits (immediately when
     * there is none), so a rollback keeps both. Legacy `receipt_path` files
     * are never deleted.
     */
    public function remove(Transaction $transaction): void;

    /**
     * Delete the files of media written by attach() whose DB transaction
     * was rolled back.
     */
    public function discard(Media ...$media): void;

    /**
     * Inline download of the receipt (media first, then the legacy
     * `receipt_path` file), or null when there is no readable file.
     */
    public function response(Transaction $transaction): ?StreamedResponse;
}
