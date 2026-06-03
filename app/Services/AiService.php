<?php

namespace App\Services;

use App\Models\AiCredential;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    /**
     * Generate AI response based on user's preferred provider.
     */
    public function generate(User $user, string $prompt, string $context = ''): array
    {
        $credentials = $user->aiCredential;

        if (!$credentials || !$credentials->hasConfiguredProvider()) {
            return [
                'success' => false,
                'error' => 'Credenciais de IA não configuradas. Acesse Configurações > Inteligência Artificial.',
            ];
        }

        $fullPrompt = $this->buildPrompt($prompt, $context);

        return match ($credentials->preferred_provider) {
            'gemini' => $this->callGemini($credentials->gemini_api_key, $fullPrompt),
            'groq' => $this->callGroq($credentials->groq_api_key, $fullPrompt),
            'openrouter' => $this->callOpenRouter($credentials->openrouter_api_key, $fullPrompt),
            default => ['success' => false, 'error' => 'Provedor de IA não suportado.'],
        };
    }

    /**
     * Call Google Gemini API.
     */
    private function callGemini(string $apiKey, string $prompt): array
    {
        try {
            $response = Http::timeout(30)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}",
                [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]],
                    ],
                ]
            );

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

                return ['success' => true, 'content' => $text, 'provider' => 'Gemini'];
            }

            return [
                'success' => false,
                'error' => 'Erro na API Gemini: ' . ($response->json('error.message') ?? 'Resposta inválida'),
            ];
        } catch (\Exception $e) {
            Log::error('Gemini API error', ['error' => $e->getMessage()]);

            return ['success' => false, 'error' => 'Falha na conexão com Gemini. Tente novamente.'];
        }
    }

    /**
     * Call Groq API (OpenAI-compatible).
     */
    private function callGroq(string $apiKey, string $prompt): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders(['Authorization' => "Bearer {$apiKey}"])
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [
                        ['role' => 'system', 'content' => 'Você é um consultor financeiro pessoal especializado. Responda sempre em português brasileiro de forma clara e objetiva.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 2048,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['choices'][0]['message']['content'] ?? '';

                return ['success' => true, 'content' => $text, 'provider' => 'Groq'];
            }

            return [
                'success' => false,
                'error' => 'Erro na API Groq: ' . ($response->json('error.message') ?? 'Resposta inválida'),
            ];
        } catch (\Exception $e) {
            Log::error('Groq API error', ['error' => $e->getMessage()]);

            return ['success' => false, 'error' => 'Falha na conexão com Groq. Tente novamente.'];
        }
    }

    /**
     * Call OpenRouter API.
     */
    private function callOpenRouter(string $apiKey, string $prompt): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'HTTP-Referer' => config('app.url'),
                    'X-Title' => 'Finestro',
                ])
                ->post('https://openrouter.ai/api/v1/chat/completions', [
                    'model' => 'google/gemma-3n-e2b-it:free',
                    'messages' => [
                        ['role' => 'system', 'content' => 'Você é um consultor financeiro pessoal especializado. Responda sempre em português brasileiro de forma clara e objetiva.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['choices'][0]['message']['content'] ?? '';

                return ['success' => true, 'content' => $text, 'provider' => 'OpenRouter'];
            }

            return [
                'success' => false,
                'error' => 'Erro na API OpenRouter: ' . ($response->json('error.message') ?? 'Resposta inválida'),
            ];
        } catch (\Exception $e) {
            Log::error('OpenRouter API error', ['error' => $e->getMessage()]);

            return ['success' => false, 'error' => 'Falha na conexão com OpenRouter. Tente novamente.'];
        }
    }

    /**
     * Build the full prompt with context.
     */
    private function buildPrompt(string $prompt, string $context): string
    {
        if (empty($context)) {
            return $prompt;
        }

        return "Contexto financeiro do usuário:\n{$context}\n\nSolicitação:\n{$prompt}";
    }

    /**
     * Build financial context from user data.
     */
    public function buildFinancialContext(User $user): string
    {
        $wallets = $user->wallets()->get();
        $totalBalance = $wallets->sum('balance');

        $transactions = $user->transactions()
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->get();

        $income = $transactions->where('type', 'income')->sum('amount');
        $expenses = $transactions->where('type', 'expense')->sum('amount');

        $goals = $user->goals()->where('status', 'active')->get();

        $context = "Saldo total: R$ " . number_format($totalBalance, 2, ',', '.') . "\n";
        $context .= "Receitas do mês: R$ " . number_format($income, 2, ',', '.') . "\n";
        $context .= "Despesas do mês: R$ " . number_format($expenses, 2, ',', '.') . "\n";
        $context .= "Balanço mensal: R$ " . number_format($income - $expenses, 2, ',', '.') . "\n";
        $context .= "Carteiras: " . $wallets->count() . "\n";

        if ($goals->isNotEmpty()) {
            $context .= "\nMetas ativas:\n";
            foreach ($goals as $goal) {
                $context .= "- {$goal->name}: R$ " . number_format($goal->current_amount, 2, ',', '.')
                    . " / R$ " . number_format($goal->target_amount, 2, ',', '.')
                    . " ({$goal->progress}%)\n";
            }
        }

        // Top expense categories this month
        $topCategories = $user->transactions()
            ->where('type', 'expense')
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->with('category')
            ->get()
            ->groupBy('category_id')
            ->map(fn($group) => [
                'name' => $group->first()->category?->name ?? 'Sem categoria',
                'total' => $group->sum('amount'),
            ])
            ->sortByDesc('total')
            ->take(5);

        if ($topCategories->isNotEmpty()) {
            $context .= "\nTop 5 categorias de despesa:\n";
            foreach ($topCategories as $cat) {
                $context .= "- {$cat['name']}: R$ " . number_format($cat['total'], 2, ',', '.') . "\n";
            }
        }

        return $context;
    }
}
