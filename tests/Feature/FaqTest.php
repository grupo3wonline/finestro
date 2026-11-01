<?php

namespace Tests\Feature;

use App\Models\FaqCategory;
use App\Models\FaqItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqTest extends TestCase
{
    use RefreshDatabase;

    public function test_faq_page_is_public_and_lists_active_items(): void
    {
        $category = FaqCategory::factory()->create(['name' => 'Primeiros passos']);
        FaqItem::factory()->for($category, 'category')->create([
            'question' => 'O que é o Finestro?',
            'answer' => 'Um sistema de gestão financeira pessoal.',
        ]);

        $response = $this->get(route('faq'));

        $response->assertOk()
            ->assertSee('Central de Ajuda')
            ->assertSee('Primeiros passos')
            ->assertSee('O que é o Finestro?');
    }

    public function test_inactive_items_and_categories_are_hidden(): void
    {
        $active = FaqCategory::factory()->create(['name' => 'Visível']);
        FaqItem::factory()->for($active, 'category')->create(['question' => 'Pergunta visível']);

        $active->items()->save(FaqItem::factory()->inactive()->make(['question' => 'Pergunta oculta']));

        $hidden = FaqCategory::factory()->inactive()->create(['name' => 'Categoria oculta']);
        FaqItem::factory()->for($hidden, 'category')->create(['question' => 'Pergunta de categoria oculta']);

        $response = $this->get(route('faq'));

        $response->assertOk()
            ->assertSee('Pergunta visível')
            ->assertDontSee('Pergunta oculta')
            ->assertDontSee('Categoria oculta');
    }

    public function test_search_filters_by_term(): void
    {
        $category = FaqCategory::factory()->create();
        FaqItem::factory()->for($category, 'category')->create([
            'question' => 'Como parcelar uma compra?',
            'answer' => 'Informe o número de parcelas no cartão.',
        ]);
        FaqItem::factory()->for($category, 'category')->create([
            'question' => 'Como exportar relatórios?',
            'answer' => 'Use o botão PDF ou CSV.',
        ]);

        $response = $this->get(route('faq', ['q' => 'parcelar']));

        $response->assertOk()
            ->assertSee('Como parcelar uma compra?')
            ->assertDontSee('Como exportar relatórios?');
    }
}
