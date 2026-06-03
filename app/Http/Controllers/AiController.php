<?php

namespace App\Http\Controllers;

use App\Models\AiCredential;
use App\Services\AiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AiController extends Controller
{
    public function __construct(
        protected AiService $aiService
    ) {}

    /**
     * Show AI insights page.
     */
    public function index(): View|RedirectResponse
    {
        $user = Auth::user();

        if (!$user->hasFeature('ai_insights')) {
            return redirect()->route('subscription.plans')
                ->with('error', 'Análise com IA está disponível apenas no plano Profissional.');
        }

        $credentials = $user->aiCredential;
        $hasCredentials = $credentials && $credentials->hasConfiguredProvider();

        return view('ai.index', compact('credentials', 'hasCredentials'));
    }

    /**
     * Show AI credentials settings page.
     */
    public function settings(): View|RedirectResponse
    {
        $user = Auth::user();

        if (!$user->hasFeature('ai_insights')) {
            return redirect()->route('subscription.plans')
                ->with('error', 'Configuração de IA está disponível apenas no plano Profissional.');
        }

        $credentials = $user->aiCredential;

        return view('ai.settings', compact('credentials'));
    }

    /**
     * Save AI credentials.
     */
    public function saveCredentials(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if (!$user->hasFeature('ai_insights')) {
            return redirect()->route('subscription.plans')
                ->with('error', 'Recurso disponível apenas no plano Profissional.');
        }

        $validated = $request->validate([
            'gemini_api_key' => 'nullable|string|max:255',
            'groq_api_key' => 'nullable|string|max:255',
            'openrouter_api_key' => 'nullable|string|max:255',
            'preferred_provider' => 'required|in:gemini,groq,openrouter',
        ]);

        AiCredential::updateOrCreate(
            ['user_id' => $user->id],
            $validated
        );

        return redirect()->route('ai.settings')
            ->with('success', 'Credenciais de IA salvas com sucesso!');
    }

    /**
     * Analyze finances with AI.
     */
    public function analyzeFinances(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasFeature('ai_insights')) {
            return response()->json(['success' => false, 'error' => 'Recurso do plano Profissional.'], 403);
        }

        $context = $this->aiService->buildFinancialContext($user);
        $prompt = "Analise minhas finanças pessoais e forneça:\n1. Um resumo da situação financeira atual\n2. Pontos positivos identificados\n3. Pontos de atenção ou riscos\n4. 3 a 5 recomendações práticas para melhorar\n5. Uma nota de 0 a 10 para a saúde financeira\n\nSeja objetivo e prático nas recomendações.";

        $result = $this->aiService->generate($user, $prompt, $context);

        return response()->json($result);
    }

    /**
     * Analyze goals with AI.
     */
    public function analyzeGoals(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasFeature('ai_insights')) {
            return response()->json(['success' => false, 'error' => 'Recurso do plano Profissional.'], 403);
        }

        $context = $this->aiService->buildFinancialContext($user);
        $prompt = "Analise minhas metas financeiras e forneça:\n1. Avaliação do progresso de cada meta\n2. Se o ritmo atual é suficiente para atingir os prazos\n3. Sugestões de quanto economizar por mês para cada meta\n4. Priorização recomendada das metas\n5. Dicas práticas para acelerar o progresso\n\nSeja específico com valores e prazos.";

        $result = $this->aiService->generate($user, $prompt, $context);

        return response()->json($result);
    }

    /**
     * Generate financial plan with AI (simulator).
     */
    public function generatePlan(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasFeature('ai_insights')) {
            return response()->json(['success' => false, 'error' => 'Recurso do plano Profissional.'], 403);
        }

        $validated = $request->validate([
            'objective' => 'required|string|max:500',
            'timeline' => 'nullable|string|max:100',
            'monthly_available' => 'nullable|numeric|min:0',
        ]);

        $context = $this->aiService->buildFinancialContext($user);

        $prompt = "Com base na minha situação financeira, crie um plano financeiro personalizado para:\n";
        $prompt .= "Objetivo: {$validated['objective']}\n";

        if (!empty($validated['timeline'])) {
            $prompt .= "Prazo desejado: {$validated['timeline']}\n";
        }
        if (!empty($validated['monthly_available'])) {
            $prompt .= "Valor disponível por mês: R$ " . number_format($validated['monthly_available'], 2, ',', '.') . "\n";
        }

        $prompt .= "\nForneça:\n1. Análise de viabilidade\n2. Plano mensal detalhado (mês a mês nos primeiros 6 meses)\n3. Estratégias de economia recomendadas\n4. Riscos e como mitigá-los\n5. Marcos intermediários para acompanhamento\n6. Alternativas caso o plano principal não funcione";

        $result = $this->aiService->generate($user, $prompt, $context);

        return response()->json($result);
    }
}
