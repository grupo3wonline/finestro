<?php

namespace App\Mail;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Resumo financeiro do período, com o extrato em CSV anexo.
 */
class FinancialReportMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

    /**
     * @param  array<string, mixed>  $report  scalar summary from ReportService::summaryFor()
     *                                        (plain arrays only, no Eloquent models, so the
     *                                        queued payload stays small and serializable)
     */
    public function __construct(
        public User $user,
        public array $report,
        public string $csv,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Seu relatório financeiro — '
                .Carbon::parse($this->report['startDate'])->format('d/m/Y')
                .' a '.Carbon::parse($this->report['endDate'])->format('d/m/Y'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.financial-report');
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn (): string => $this->csv, 'extrato_'.$this->report['startDate'].'_'.$this->report['endDate'].'.csv')
                ->withMime('text/csv'),
        ];
    }
}
