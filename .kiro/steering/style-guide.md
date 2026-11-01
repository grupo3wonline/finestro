---
inclusion: fileMatch
fileMatchPattern: '**/*.blade.php'
---

# Finestro — Guia de Estilo (Style Guide)

> Design system e convenções de UI do Finestro. Mantenha a identidade de marca
> "Confiança serena" (verde evergreen + âmbar) consistente em toda a interface.
> UI em pt-BR; código, classes e comentários técnicos em inglês.

## 1. Identidade de marca

- **Logo**: use sempre o componente `<x-ui.logo />` (nunca recriar o mark inline).
  Props: `size` (sm|md|lg), `href`, `showText`, `textClass`.
- **Tom visual**: moderno, profissional, limpo, confiável. Cantos arredondados
  (`rounded-xl`/`rounded-2xl`), sombras suaves, bastante respiro (espaçamento).
- **Fontes**: `font-sans` (Inter) para corpo; `font-display` (Fraunces) para
  títulos de destaque e números. Valores monetários usam a classe `.num`
  (Fraunces + algarismos tabulares).

## 2. Paleta (tokens Tailwind)

Use SEMPRE os tokens de marca — nunca `gray-*` (frio). Para cinzas, use `surface-*`.

| Token       | Uso                                                        |
|-------------|------------------------------------------------------------|
| `primary`   | Verde evergreen `#039855` — ações primárias, marca, saldo  |
| `secondary` | Teal-green — ações secundárias, acentos sutis, receitas    |
| `accent`    | Âmbar `#f79009` — CTAs e destaques (uso reservado)         |
| `surface`   | Charcoal quente — fundos, textos, bordas (substitui gray)  |
| `red-*`     | Despesas, erros, ações destrutivas                         |

- Dark mode: sempre forneça a variante `dark:` (ex.: `bg-white dark:bg-surface-900`).
- Nunca introduza cores cruas fora da paleta; se precisar de neutro, use `surface-*`.

## 3. Componentes do kit (`resources/views/components/ui/`)

Prefira os componentes a remarcar markup. Principais:

- `<x-ui.button variant="primary|secondary|accent|danger|success|ghost|outline" size="sm|md|lg" icon="fa-..." :href="..." loading="..." block />`
- `<x-ui.card hover padding="p-6">` — card base.
- `<x-ui.stat-card label value icon color trend />` — card de estatística.
- `<x-ui.section-card title icon iconColor>` — seção com cabeçalho; slots `header`, `actions`.
- `<x-ui.page-header title subtitle icon>` com `<x-slot:actions>` — cabeçalho de página.
- `<x-ui.breadcrumb :items="[['label'=>'...','url'=>...], ['label'=>'Atual']]" />` — trilha (Home é automática).
- `<x-ui.empty-state icon title text>` com `<x-slot:action>` — estado vazio.
- `<x-ui.badge variant="success|danger|warning|info|neutral" icon />`
- `<x-ui.alert variant="info|success|warning|danger" title dismissible />`
- `<x-ui.tip icon>` — caixa de dica/ajuda.
- `<x-ui.icon-box icon color size />` — ícone em caixa arredondada tingida.
- Formulários: `<x-ui.field label for error hint required tooltip>` envolvendo
  `<x-ui.input>`, `<x-ui.select>`, `<x-ui.textarea>`.

### Classes utilitárias (`resources/css/app.css`)
`.btn-*`, `.input`, `.select`, `.badge-*`, `.alert-*`, `.icon-box`, `.chip`,
`.progress`, `.skeleton`, `.num`, `.glass`, `.bg-grid`, `.tip-box`, `.section-card`.

## 4. Layout e responsividade

- **Mobile-first**: estilize o mobile primeiro, depois `sm: md: lg: xl: 2xl:`.
- Container padrão: `max-w-7xl mx-auto px-4 sm:px-6 lg:px-8`.
- Grids responsivos: `grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4`.
- Toque confortável: alvos ≥ 40px; evite hover como única affordance.

## 5. Interação (Alpine.js)

- Reatividade via Alpine (`x-data`, `x-show`, `x-on`, `x-transition`, `x-cloak`).
- Dropdowns/modais com `@click.outside` e `@keydown.escape.window`.
- Dark mode via `x-data` no `<html>` + `localStorage` (não reimplementar).
- Alertas e confirmações: **SweetAlert2** (`Swal.fire`), nunca `alert()` nativo.
- Ícones: **Font Awesome** (`fas fa-...`, `fab fa-...`).
- Gráficos: `window.Finestro.chart(canvas, config)` (Chart.js com tema de marca).

## 6. Acessibilidade

- HTML semântico; `aria-label`/`aria-current`/`aria-expanded` onde aplicável.
- Contraste adequado em ambos os temas; foco visível (`focus-visible:ring-*`).
- Navegação por teclado funcional em menus, modais e dropdowns.
- `alt` significativo em imagens; `[x-cloak]` para evitar flash de conteúdo.

## 7. Formulários

- `autocomplete="off"` em inputs (os componentes `<x-ui.*>` já aplicam).
- Validação exibida via `<x-ui.field error="campo">`.
- Estados de loading/erro/vazio sempre tratados.
- Proteção contra duplo-submit é automática (`resources/js/forms.js`); use
  `data-allow-resubmit` apenas quando reenvio repetido for intencional.

## 8. Conteúdo e copy (pt-BR)

- Textos de interface em português do Brasil, tom claro e acolhedor (público 20-50).
- Mensagens de erro/sucesso amigáveis e com orientação de próxima ação.
- CTAs persuasivos com benefício claro.
- **Footer obrigatório** em todas as páginas: "Sistema desenvolvido pelo desenvolvedor HOTWYL | WILLFROMBRASIL".
- Páginas públicas incluem CTA para o Grupo 3W Online (www.3wonline.com.br).

## 9. Não faça

- Não use `gray-*` (use `surface-*`).
- Não recrie logo, header, footer, breadcrumb ou botões com markup solto —
  use os componentes do kit.
- Não use `alert()`/`confirm()` nativo para fluxos de UI (prefira SweetAlert2).
- Não introduza jQuery nem CSS custom quando uma utilitária Tailwind resolve.
- Não escreva lógica de negócio em views; mantenha a view declarativa.
