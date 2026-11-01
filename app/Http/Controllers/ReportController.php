<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportRequest;
use App\Interfaces\Services\ReportServiceInterface;
use App\Interfaces\Services\WalletServiceInterface;
use App\Mail\FinancialReportMail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function __construct(
        protected ReportServiceInterface $reports,
        protected WalletServiceInterface $walletService
    ) {}

    public function index(ReportRequest $request): View
    {
        $data = $this->reports->build($request->user(), $request->filters());
        $data['wallets'] = $this->walletService->getAllWallets();

        return view('reports.index', $data);
    }

    /**
     * Versão para impressão / "Salvar como PDF" do navegador.
     *
     * Origem: exportação em PDF do Financas-master (FPDF) e do
     * controle-financas (mPDF). Em vez de adicionar uma biblioteca de PDF
     * (dezenas de MB, fontes, CSS limitado), usamos uma página com CSS de
     * impressão: o navegador gera um PDF fiel ao layout, sem dependência nova.
     */
    public function print(ReportRequest $request): View|RedirectResponse
    {
        if (! $request->user()->hasFeature('reports_export')) {
            return redirect()->route('reports.index')
                ->with('error', 'Impressão/PDF de relatórios disponível apenas no plano Profissional.');
        }

        return view('reports.print', $this->reports->build($request->user(), $request->filters()));
    }

    /**
     * Envia o resumo do período para o e-mail do próprio usuário, com CSV anexo.
     * Origem: email.php do controle-financas. Por segurança, só envia para o
     * e-mail da conta (um campo livre permitiria usar o Finestro para spam).
     */
    public function email(ReportRequest $request): RedirectResponse
    {
        if (! $request->user()->hasFeature('reports_export')) {
            return redirect()->route('reports.index')
                ->with('error', 'Envio de relatórios por e-mail disponível apenas no plano Profissional.');
        }

        $user = $request->user();
        $filters = $request->filters();
        $report = $this->reports->build($user, $filters);

        // Queued: the payload carries only the scalar summary and the CSV string.
        Mail::to($user)->queue(new FinancialReportMail(
            user: $user,
            report: $this->reports->summaryFor($user, $filters),
            csv: $this->reports->exportCsv($report['transactions']),
        ));

        return back()->with('success', 'Relatório enviado para '.$request->user()->email.'.');
    }

    /**
     * Gera o relatório em PDF de verdade (server-side, via dompdf) para download.
     *
     * Complementa o `print()` (que depende do navegador): útil para anexar em
     * e-mails, arquivar ou quando o usuário quer um PDF sem passar pelo diálogo
     * de impressão. Gated pelo mesmo recurso de plano da exportação.
     */
    public function pdf(ReportRequest $request): RedirectResponse|Response
    {
        if (! $request->user()->hasFeature('reports_export')) {
            return redirect()->route('reports.index')
                ->with('error', 'Exportação de relatórios em PDF disponível apenas no plano Profissional.');
        }

        $report = $this->reports->build($request->user(), $request->filters());
        $report['userName'] = $request->user()->name;

        $pdf = Pdf::loadView('reports.pdf', $report)
            ->setPaper('a4', 'portrait');

        $filename = 'finestro_relatorio_'.$report['startDate'].'_'.$report['endDate'].'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Extrato do período em CSV (padrão) ou XLSX (`?format=xlsx`).
     */
    public function export(ReportRequest $request): RedirectResponse|Response
    {
        if (! $request->user()->hasFeature('reports_export')) {
            return redirect()->route('reports.index')
                ->with('error', 'Exportação de relatórios disponível apenas no plano Profissional.');
        }

        $report = $this->reports->build($request->user(), $request->filters());
        $isXlsx = $request->input('format', 'csv') === 'xlsx';

        $content = $isXlsx
            ? $this->reports->exportXlsx($report['transactions'])
            : $this->reports->exportCsv($report['transactions']);

        $filename = 'finestro_relatorio_'.$report['startDate'].'_'.$report['endDate'].($isXlsx ? '.xlsx' : '.csv');
        $contentType = $isXlsx
            ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            : 'text/csv; charset=UTF-8';

        return response()->streamDownload(function () use ($content): void {
            echo $content;
        }, $filename, ['Content-Type' => $contentType]);
    }
}
