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

    /**
     * Generate technical financial report with AI.
     */
    public function technicalReport(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasFeature('ai_insights')) {
            return response()->json(['success' => false, 'error' => 'Recurso do plano Profissional.'], 403);
        }

        $context = $this->aiService->buildFinancialContext($user);

        $prompt = <<<PROMPT
Elabore um PARECER TÉCNICO FINANCEIRO completo e formal sobre a minha situação financeira. O parecer deve seguir a estrutura:

## PARECER TÉCNICO FINANCEIRO

### 1. IDENTIFICAÇÃO E PERÍODO DE ANÁLISE
- Período analisado
- Base de dados utilizada

### 2. INDICADORES FINANCEIROS
Calcule e apresente:
- Taxa de Poupança (% da renda que sobra)
- Comprometimento de Renda (% gasta com despesas fixas)
- Índice de Liquidez (reserva em meses de despesa)
- Diversificação de Receitas
- Evolução Patrimonial (tendência)
- Para cada indicador: valor, classificação (Ótimo/Bom/Regular/Crítico) e benchmark

### 3. DIAGNÓSTICO SITUACIONAL
- Pontos fortes da gestão financeira
- Vulnerabilidades identificadas
- Riscos potenciais (curto, médio e longo prazo)

### 4. ANÁLISE COMPARATIVA
- Compare com a média brasileira (dados IBGE/Bacen)
- Posicionamento relativo do perfil financeiro

### 5. RECOMENDAÇÕES TÉCNICAS
- Ações imediatas (próximos 30 dias)
- Ações de curto prazo (1-3 meses)
- Ações de médio prazo (3-12 meses)
- Cada recomendação com impacto esperado

### 6. NOTA FINAL E CLASSIFICAÇÃO
- Nota de 0 a 10 com justificativa
- Classificação: A (Excelente), B (Bom), C (Regular), D (Atenção), E (Crítico)
- Perspectiva de evolução

### 7. DISCLAIMER
- Este parecer é informativo e não substitui aconselhamento profissional

Seja técnico, objetivo e use dados quantitativos sempre que possível.
PROMPT;

        $result = $this->aiService->generate($user, $prompt, $context);

        return response()->json($result);
    }

    /**
     * Generate comprehensive financial planning with AI.
     */
    public function financialPlanning(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasFeature('ai_insights')) {
            return response()->json(['success' => false, 'error' => 'Recurso do plano Profissional.'], 403);
        }

        $validated = $request->validate([
            'horizon' => 'required|in:short,medium,long',
            'priority' => 'required|in:security,growth,freedom,retirement',
            'monthly_income' => 'nullable|numeric|min:0',
            'monthly_expenses' => 'nullable|numeric|min:0',
        ]);

        $context = $this->aiService->buildFinancialContext($user);

        $horizons = ['short' => 'Curto prazo (até 1 ano)', 'medium' => 'Médio prazo (1-5 anos)', 'long' => 'Longo prazo (5+ anos)'];
        $priorities = [
            'security' => 'Segurança financeira e reserva de emergência',
            'growth' => 'Crescimento patrimonial e investimentos',
            'freedom' => 'Liberdade financeira e independência',
            'retirement' => 'Aposentadoria e renda passiva',
        ];

        $prompt = "Crie um PLANEJAMENTO FINANCEIRO COMPLETO E PERSONALIZADO com base nos meus dados reais.\n\n";
        $prompt .= "Horizonte: {$horizons[$validated['horizon']]}\n";
        $prompt .= "Prioridade principal: {$priorities[$validated['priority']]}\n";

        if (!empty($validated['monthly_income'])) {
            $prompt .= "Renda mensal informada: R$ " . number_format($validated['monthly_income'], 2, ',', '.') . "\n";
        }
        if (!empty($validated['monthly_expenses'])) {
            $prompt .= "Despesas mensais informadas: R$ " . number_format($validated['monthly_expenses'], 2, ',', '.') . "\n";
        }

        $prompt .= "\n\nEstruture o planejamento em:\n\n## PLANEJAMENTO FINANCEIRO PERSONALIZADO\n\n### 1. DIAGNÓSTICO ATUAL\n- Resumo da situação (renda, gastos, patrimônio, dívidas)\n- Capacidade de investimento mensal estimada\n\n### 2. OBJETIVOS DEFINIDOS\n- Meta principal com valor e prazo\n- Metas intermediárias (marcos)\n- Indicadores de sucesso\n\n### 3. ORÇAMENTO SUGERIDO\n- Distribuição ideal da renda (% por categoria)\n- Áreas onde pode economizar\n\n### 4. ESTRATÉGIA DE INVESTIMENTOS\n- Perfil sugerido com base nos dados\n- Alocação recomendada por classe de ativo\n- Produtos específicos sugeridos\n- Aportes mensais recomendados\n\n### 5. PLANO DE AÇÃO (Timeline)\n- Mês 1-3: Ações imediatas\n- Mês 4-6: Consolidação\n- Mês 7-12: Crescimento\n- Para cada fase: o que fazer, quanto investir, resultado esperado\n\n### 6. CENÁRIOS\n- Cenário Otimista: se seguir 100% do plano\n- Cenário Realista: seguindo 70-80%\n- Cenário Pessimista: imprevistos e como se proteger\n\n### 7. MONITORAMENTO\n- KPIs para acompanhar mensalmente\n- Quando reavaliar o plano\n\n### 8. DISCLAIMER\n- Este planejamento é educacional e não constitui aconselhamento financeiro profissional\n\nUse valores reais dos meus dados, seja específico com números e prazos.";

        $result = $this->aiService->generate($user, $prompt, $context);

        return response()->json($result);
    }
}
