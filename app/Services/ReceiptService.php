<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\Services\ReceiptServiceInterface;
use App\Models\Transaction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileUnacceptableForCollection;
use Spatie\MediaLibrary\MediaCollections\Filesystem;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Transaction receipts on spatie/laravel-medialibrary (private disk).
 *
 * Rows created before the migration may still point to a file through
 * `receipt_path`; that file is served as a fallback and never deleted here.
 */
class ReceiptService implements ReceiptServiceInterface
{
    private const string DISK = 'private';

    public function attach(Transaction $transaction, UploadedFile $file): Media
    {
        // Remove the old receipt first (file deletion deferred to commit), so
        // the singleFile auto-clear never deletes it inside the transaction.
        $this->remove($transaction);

        // Random stored name: the client file name never reaches the disk.
        $fileName = Str::uuid()->toString().'.'.strtolower($file->getClientOriginalExtension());

        try {
            $media = $transaction->addMedia($file)
                ->usingFileName($fileName)
                ->toMediaCollection(Transaction::RECEIPT_COLLECTION, self::DISK);
        } catch (FileUnacceptableForCollection) {
            throw ValidationException::withMessages(['receipt' => 'O comprovante deve ser JPG, PNG ou PDF.']);
        } catch (FileIsTooBig) {
            throw ValidationException::withMessages(['receipt' => 'O comprovante não pode ter mais de 5MB.']);
        }

        // Keep the owner in memory: discard() must resolve the path even
        // after a rollback removed the transaction row.
        $media->setRelation('model', $transaction);

        return $media;
    }

    public function remove(Transaction $transaction): void
    {
        $items = $transaction->media()
            ->where('collection_name', Transaction::RECEIPT_COLLECTION)
            ->get();

        foreach ($items as $media) {
            // Delete the row without Spatie's observer (which removes the
            // file at once); the file goes only after the DB commit.
            Media::withoutEvents(fn () => $media->delete());

            DB::afterCommit(fn () => app(Filesystem::class)->removeAllFiles($media));
        }

        $transaction->unsetRelation('media');
    }

    public function discard(Media ...$media): void
    {
        foreach ($media as $item) {
            app(Filesystem::class)->removeAllFiles($item);
        }
    }

    public function response(Transaction $transaction): ?StreamedResponse
    {
        $media = $transaction->getFirstMedia(Transaction::RECEIPT_COLLECTION);

        [$diskName, $path] = $media instanceof Media
            ? [$media->disk, $media->getPathRelativeToRoot()]
            : [self::DISK, (string) $transaction->receipt_path];

        $disk = Storage::disk($diskName);

        if (trim($path) === '' || ! $disk->exists($path)) {
            return null;
        }

        // Same file name and headers as the pre-Media Library download.
        $filename = 'comprovante-'.$transaction->date->format('Y-m-d').'.'.pathinfo($path, PATHINFO_EXTENSION);

        return $disk->response($path, $filename, [
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
