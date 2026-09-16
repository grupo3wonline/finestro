<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the public contact form submission.
 *
 * Public endpoint (no authentication). Includes a honeypot field ("website")
 * that must remain empty; bots that fill every field are rejected by rules().
 */
class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:180'],
            'subject' => ['required', 'string', 'min:3', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
            // Honeypot: real users never see or fill this field.
            'website' => ['nullable', 'prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Informe seu nome.',
            'name.min' => 'O nome deve ter pelo menos 2 caracteres.',
            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'subject.required' => 'Informe o assunto.',
            'message.required' => 'Escreva sua mensagem.',
            'message.min' => 'A mensagem deve ter pelo menos 10 caracteres.',
            'website.prohibited' => 'Falha na validação anti-spam.',
        ];
    }
}
