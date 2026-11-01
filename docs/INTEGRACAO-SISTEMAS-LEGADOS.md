# Integração dos sistemas de `bkp-after_features/` ao Finestro

Data: 25/09/2026

## 1. O que havia em cada sistema

| Sistema | Stack | Recursos aproveitáveis |
|---|---|---|
| **Financas-master** (livro-caixa, Paulo Collares) | PHP procedural + `mysql_*` + FPDF | Parcelamento (`nparcela/parcelas`), orçamento mensal (`orcamento`, com "aplicar deste mês em diante"), histórico de edições com justificativa (`historico` + `just_ed`), log de exclusões (`exclusoes`), comprovante anexo (`comprovantes`), gastos de cartão por bandeira com dia de vencimento, exportação PDF, saudação por horário |
| **ebfinance** | PHP OO + PDO + Semantic UI | Status pago/pendente (`pago` s/n) com "marcar movimento", forma de pagamento, 22 categorias padrão, seleção por mês/ano, balanço mensal e geral |
| **controle-financas-phpestruturado** | PHP + Smarty + mPDF + PHPMailer | Receitas/despesas **fixas × variáveis**, busca por período, relatório em PDF, envio do relatório por e-mail |
| **DCFinancas** | Zend Framework 1 | Nada — é o esqueleto vazio do ZF1 (controller `indexAction` sem código) |

**Nenhuma linha de código legado foi copiada.** Apenas as ideias foram reescritas na arquitetura do Finestro (Controller → Service → Repository → Model, DTOs, UUID, SoftDeletes, escopo por usuário). O código legado tinha problemas graves de segurança:

- SQL injection: `"... WHERE mes='$_GET[mes]'"` (Financas-master) e datas concatenadas (controle-financas);
- senhas em MD5 sem salt (ebfinance: `21232f297a57a5a743894a0e4a801fc3` = "admin");
- extensão `mysql_*`, removida no PHP 7 — nem executa no PHP 8.

## 2. Mapa ideia → implementação

| Ideia (origem) | Onde está no Finestro |
|---|---|
| Pago/pendente + marcar (ebfinance) | `transactions.status`, `paid_at`, `due_date` · `TransactionService::markAsPaid/markAsPending` · tela **Finanças → Contas a pagar/receber** (`/contas`) |
| Forma de pagamento (ebfinance) | `transactions.payment_method` · `App\Enums\PaymentMethod` |
| Parcelamento (Financas-master) | `installment_group_id/number/total` · `TransactionService::createInstallments` |
| Orçamento mensal (Financas-master) | tabela `budgets` · `BudgetService` · **Finanças → Orçamentos** (`/orcamentos`) |
| Histórico + justificativa + exclusões (Financas-master) | tabela `transaction_histories` · linha do tempo em `transactions/show` · **Lixeira** com restauração |
| Cartão por bandeira (Financas-master) | `Wallet::invoiceCycleFor()` · `CreditCardInvoiceService` · `/wallets/{id}/fatura` |
| Categorias padrão (ebfinance) | `CategoryService::suggestedCategories/importSuggested` · botão "Importar sugeridas" |
| Mês/ano + balanço mensal (ebfinance) | navegação ← mês → em Transações + cards de resumo |
| Fixas × variáveis (controle-financas) | bloco novo em Relatórios (fixa = recorrente ou parcelada) |
| PDF (Financas-master/controle-financas) | `/relatorios/imprimir` — página com CSS de impressão (A4) |
| E-mail do relatório (controle-financas) | `FinancialReportMail` com CSV anexo |
| Saudação por horário (Financas-master) | `DashboardController::greeting()` |

## 3. Decisões de projeto (e o porquê)

1. **Só transação paga mexe no saldo.** Antes, uma conta de dezembro lançada em setembro já descontava o saldo de setembro. Agora o saldo da carteira reflete o dinheiro que de fato existe; o "previsto" aparece separado.
2. **Migração não altera saldos.** Todas as transações existentes viram `paid` (com `paid_at = created_at`), exatamente o comportamento anterior.
3. **Parcelamento em centavos.** R$ 100 em 3x = 33,33 + 33,33 + **33,34**. O legado fazia `100/3` e perdia 1 centavo.
4. **Cartão parcelado nasce "pago".** Uma compra de R$ 1.200 em 12x consome R$ 1.200 do limite no ato (é assim que o banco faz); cada parcela cai na fatura do seu mês. Em conta corrente (carnê/boleto), só a 1ª parcela segue o status informado, e as demais nascem como contas a pagar.
5. **Cartão: saldo negativo = dívida.** Despesas já subtraíam do saldo; o cálculo de "% usado" é que estava invertido (dava negativo). Pagar a fatura gera uma saída na conta corrente e uma entrada no cartão, marcadas com `origin = invoice_payment`.
6. **PDF sem biblioteca nova.** A página de impressão usa CSS `@media print`; o "Salvar como PDF" do navegador gera o arquivo. Isso evita adicionar dompdf/mPDF (dezenas de MB, fontes, CSS limitado).
7. **E-mail só para o dono da conta.** Um campo de destinatário livre permitiria usar o Finestro para spam.
8. **Recorrência por série.** Só a última ocorrência de cada série gera a próxima; cada data é calculada a partir da **âncora** (31/01 → 28/02 → 31/03, sem ficar "presa" no dia 28); datas perdidas são recuperadas; contas são criadas como pendentes até 7 dias antes (débito automático e cartão entram pagos na data). Para encerrar a série, basta desmarcar "recorrente" na última ocorrência.
9. **Limites do plano grátis.** 3 orçamentos por mês; a importação de categorias sugeridas para quando chega ao limite de 5.

## 4. Bugs pré-existentes corrigidos

| Bug | Sintoma | Correção |
|---|---|---|
| Disco `private` inexistente | Anexar comprovante → erro 500 | Disco criado em `config/filesystems.php` + rota segura de download |
| `closing_day`/`due_day` como `DATE` | Cadastrar cartão com dia de fechamento falhava no MySQL estrito | Migração converte para inteiro (1–31), preservando o dia |
| `% usado` do cartão | Negativo; crédito disponível maior que o limite | `Wallet::credit_used` |
| Filtro de carteira no relatório | Formulário enviava `wallet`, controller lia `wallet_id` | Aceita os dois; CSV respeita todos os filtros |
| IDOR em transações | Era possível lançar na carteira de outro usuário informando o UUID | `Rule::exists(...)->where('user_id', ...)` |
| Recorrência não desligava | Checkbox desmarcado não é enviado → ficava `true` para sempre | Campo oculto `_has_recurring_field` |
| Campos opcionais não apagavam | `notes`/`due_date` vazios eram ignorados na edição | DTO distingue "não enviado" de "enviado vazio" |
| Lista sem paginação | Carregava todas as transações | `paginate(20)` |
| Carteiras compartilhadas | Consulta sempre vazia (escopo global + `user_id !=`) | `withoutGlobalScope('user')` |
| Testes do Breeze quebrados | 6 falhas (termos de uso, SoftDeletes) | Factory/testes atualizados |

## 5. Deploy (checklist)

```bash
# 1. BACKUP do banco de produção antes de tudo
mysqldump -u USUARIO -p hg3won41_Finestro > backup_antes_merge.sql

# 2. Dependências e build do front (as telas novas usam classes Tailwind novas)
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 3. Migrações (4 novas, todas com down())
php artisan migrate

# 4. Caches
php artisan optimize:clear && php artisan optimize

# 5. Agendador (necessário para recorrências) — cron do servidor:
# * * * * * cd /caminho/finestro && php artisan schedule:run >> /dev/null 2>&1

# Simular a recorrência sem gravar nada:
php artisan transactions:process-recurring --dry-run
```

Rollback: `php artisan migrate:rollback --step=4` (desfaz as 4 migrações novas) e restaurar o backup.

## 6. Testes

`php artisan test` → **74 testes / 228 asserções**, incluindo:

- `TransactionLifecycleTest` — saldo × status, parcelas, auditoria, lixeira, IDOR, comprovante, formato "1.234,56";
- `BudgetInvoiceRecurringTest` — orçamentos e limites do plano, ciclo e pagamento de fatura, recorrência (atrasos, âncora, parada), categorias sugeridas, relatórios (filtro, PDF, CSV, e-mail);
- `PagesSmokeTest` — renderiza as 22 telas autenticadas com os dados do seeder de demonstração.
