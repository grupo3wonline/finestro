@extends('layouts.app')

@section('title', 'Cotação de Moedas e Câmbio')

@section('breadcrumb')
<nav class="text-sm" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li><a href="{{ route('calculators.index') }}" class="text-gray-500 hover:text-primary-600">Calculadoras</a></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li class="text-gray-700 dark:text-gray-300 font-medium">Cotação de Moedas</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="max-w-5xl mx-auto space-y-6" x-data="currencyExchange()" x-init="loadRates()">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            <i class="fas fa-exchange-alt text-amber-500 mr-2"></i> Cotação de Moedas e Câmbio
        </h1>
        <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">Cotações comerciais em tempo real e conversor de moedas.</p>
    </div>

    <!-- Exchange Rates Cards -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-chart-bar text-amber-500 mr-2"></i> Cotações Comerciais
            </h2>
            <div class="flex items-center gap-3">
                <span class="text-xs text-gray-500 dark:text-gray-400" x-show="lastUpdate">
                    <i class="fas fa-clock mr-1"></i> Atualizado: <span x-text="lastUpdate"></span>
                </span>
                <button @click="loadRates()" :disabled="loading" class="px-3 py-1.5 text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded-lg hover:bg-amber-200 dark:hover:bg-amber-900/50 transition disabled:opacity-50">
                    <i class="fas fa-sync-alt mr-1" :class="loading && 'animate-spin'"></i> Atualizar
                </button>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading && !rates.length" class="p-12 text-center">
            <i class="fas fa-spinner fa-spin text-3xl text-amber-500 mb-3"></i>
            <p class="text-gray-500 dark:text-gray-400">Carregando cotações...</p>
        </div>

        <!-- Error State -->
        <div x-show="error" class="p-6">
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 text-center">
                <i class="fas fa-exclamation-triangle text-red-500 text-xl mb-2"></i>
                <p class="text-sm text-red-700 dark:text-red-400" x-text="error"></p>
                <button @click="loadRates()" class="mt-2 px-4 py-1.5 text-xs font-medium bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition">
                    Tentar novamente
                </button>
            </div>
        </div>

        <!-- Rates Grid -->
        <div x-show="rates.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 p-6">
            <template x-for="rate in rates" :key="rate.code">
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 border border-gray-200 dark:border-gray-600">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-2xl" x-text="rate.flag"></span>
                            <div>
                                <p class="text-sm font-bold text-gray-900 dark:text-white" x-text="rate.code"></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400" x-text="rate.name"></p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                            :class="rate.variation >= 0 ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400'">
                            <i class="fas mr-1" :class="rate.variation >= 0 ? 'fa-arrow-up' : 'fa-arrow-down'"></i>
                            <span x-text="rate.variation.toFixed(2) + '%'"></span>
                        </span>
                    </div>
                    <div class="space-y-1">
                        <div class="flex justify-between">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Compra</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white" x-text="'R$ ' + parseFloat(rate.bid).toLocaleString('pt-BR', {minimumFractionDigits: 4})"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Venda</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white" x-text="'R$ ' + parseFloat(rate.ask).toLocaleString('pt-BR', {minimumFractionDigits: 4})"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Máxima</span>
                            <span class="text-xs text-gray-600 dark:text-gray-300" x-text="'R$ ' + parseFloat(rate.high).toLocaleString('pt-BR', {minimumFractionDigits: 4})"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Mínima</span>
                            <span class="text-xs text-gray-600 dark:text-gray-300" x-text="'R$ ' + parseFloat(rate.low).toLocaleString('pt-BR', {minimumFractionDigits: 4})"></span>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Currency Converter -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-coins text-amber-500 mr-2"></i> Conversor de Moedas
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Converta valores entre moedas usando as cotações comerciais atuais.</p>
        </div>

        <div class="p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                <!-- Amount -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Valor</label>
                    <input type="number" x-model.number="convertAmount" step="0.01" min="0" autocomplete="off"
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition text-lg font-semibold"
                        placeholder="1000.00">
                </div>

                <!-- From Currency -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">De</label>
                    <select x-model="convertFrom" autocomplete="off"
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition">
                        <option value="BRL">🇧🇷 BRL - Real Brasileiro</option>
                        <option value="USD">🇺🇸 USD - Dólar Americano</option>
                        <option value="EUR">🇪🇺 EUR - Euro</option>
                        <option value="GBP">🇬🇧 GBP - Libra Esterlina</option>
                        <option value="ARS">🇦🇷 ARS - Peso Argentino</option>
                        <option value="JPY">🇯🇵 JPY - Iene Japonês</option>
                        <option value="CNY">🇨🇳 CNY - Yuan Chinês</option>
                        <option value="CAD">🇨🇦 CAD - Dólar Canadense</option>
                        <option value="BTC">₿ BTC - Bitcoin</option>
                    </select>
                </div>

                <!-- To Currency -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Para</label>
                    <select x-model="convertTo" autocomplete="off"
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition">
                        <option value="BRL">🇧🇷 BRL - Real Brasileiro</option>
                        <option value="USD">🇺🇸 USD - Dólar Americano</option>
                        <option value="EUR">🇪🇺 EUR - Euro</option>
                        <option value="GBP">🇬🇧 GBP - Libra Esterlina</option>
                        <option value="ARS">🇦🇷 ARS - Peso Argentino</option>
                        <option value="JPY">🇯🇵 JPY - Iene Japonês</option>
                        <option value="CNY">🇨🇳 CNY - Yuan Chinês</option>
                        <option value="CAD">🇨🇦 CAD - Dólar Canadense</option>
                        <option value="BTC">₿ BTC - Bitcoin</option>
                    </select>
                </div>
            </div>

            <!-- Swap Button -->
            <div class="flex justify-center">
                <button @click="swapCurrencies()" class="p-2 rounded-full bg-gray-100 dark:bg-gray-700 hover:bg-amber-100 dark:hover:bg-amber-900/30 text-gray-600 dark:text-gray-400 hover:text-amber-600 transition" title="Inverter moedas">
                    <i class="fas fa-exchange-alt fa-rotate-90"></i>
                </button>
            </div>

            <!-- Convert Button -->
            <div class="flex gap-3">
                <button @click="convert()" :disabled="!convertAmount || convertFrom === convertTo || loadingConvert"
                    class="flex-1 px-6 py-3 bg-amber-600 hover:bg-amber-700 text-white font-semibold rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-calculator mr-2" :class="loadingConvert && 'animate-spin'"></i>
                    <span x-text="loadingConvert ? 'Convertendo...' : 'Converter'"></span>
                </button>
                <button @click="resetConvert()" class="px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <i class="fas fa-eraser mr-1"></i> Limpar
                </button>
            </div>

            <!-- Conversion Result -->
            <div x-show="convertResult !== null" x-transition class="bg-gradient-to-r from-amber-50 to-yellow-50 dark:from-amber-900/20 dark:to-yellow-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-6">
                <div class="text-center space-y-3">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        <span x-text="convertAmount?.toLocaleString('pt-BR', {minimumFractionDigits: 2})"></span>
                        <span class="font-semibold" x-text="convertFrom"></span> =
                    </p>
                    <p class="text-3xl font-black text-amber-700 dark:text-amber-400" x-text="convertResult"></p>
                    <div class="flex items-center justify-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                        <span>Taxa: <span class="font-medium" x-text="convertRate"></span></span>
                        <span>|</span>
                        <span x-text="convertTimestamp"></span>
                    </div>
                </div>
            </div>

            <!-- Same currency warning -->
            <div x-show="convertFrom === convertTo && convertAmount" class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3 text-center">
                <p class="text-sm text-yellow-700 dark:text-yellow-400"><i class="fas fa-info-circle mr-1"></i> Selecione moedas diferentes para converter.</p>
            </div>
        </div>
    </div>

    <!-- Info -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <p class="text-sm text-blue-800 dark:text-blue-300">
            <i class="fas fa-info-circle mr-1"></i>
            <strong>Fonte:</strong> Cotações comerciais obtidas via API pública (AwesomeAPI/BCB). Valores para referência — consulte sua instituição financeira para operações de câmbio.
        </p>
    </div>
</div>

@push('scripts')
<script>
function currencyExchange() {
    return {
        rates: [],
        loading: false,
        loadingConvert: false,
        error: null,
        lastUpdate: null,

        convertAmount: 1000,
        convertFrom: 'USD',
        convertTo: 'BRL',
        convertResult: null,
        convertRate: null,
        convertTimestamp: null,

        async loadRates() {
            this.loading = true;
            this.error = null;

            try {
                const pairs = 'USD-BRL,EUR-BRL,GBP-BRL,ARS-BRL,JPY-BRL,CNY-BRL,BTC-BRL,CAD-BRL,ETH-BRL';
                const response = await fetch(`https://economia.awesomeapi.com.br/last/${pairs}`);

                if (!response.ok) throw new Error('Erro ao buscar cotações');

                const data = await response.json();
                const flags = { USD: '🇺🇸', EUR: '🇪🇺', GBP: '🇬🇧', ARS: '🇦🇷', JPY: '🇯🇵', CNY: '🇨🇳', BTC: '₿', CAD: '🇨🇦', ETH: 'Ξ' };

                this.rates = Object.values(data).map(item => ({
                    code: item.code + '/' + item.codein,
                    name: item.name,
                    bid: item.bid,
                    ask: item.ask,
                    high: item.high,
                    low: item.low,
                    variation: parseFloat(item.pctChange),
                    flag: flags[item.code] || '💱',
                    timestamp: item.create_date
                }));

                this.lastUpdate = new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
            } catch (e) {
                this.error = 'Não foi possível carregar as cotações. Verifique sua conexão e tente novamente.';
            } finally {
                this.loading = false;
            }
        },

        async convert() {
            if (!this.convertAmount || this.convertFrom === this.convertTo) return;

            this.loadingConvert = true;
            this.convertResult = null;

            try {
                let rate, result;

                if (this.convertFrom === 'BRL') {
                    const response = await fetch(`https://economia.awesomeapi.com.br/last/${this.convertTo}-BRL`);
                    const data = await response.json();
                    const key = this.convertTo + 'BRL';
                    rate = parseFloat(data[key].bid);
                    result = this.convertAmount / rate;
                    this.convertRate = `1 ${this.convertTo} = R$ ${rate.toLocaleString('pt-BR', {minimumFractionDigits: 4})}`;
                } else if (this.convertTo === 'BRL') {
                    const response = await fetch(`https://economia.awesomeapi.com.br/last/${this.convertFrom}-BRL`);
                    const data = await response.json();
                    const key = this.convertFrom + 'BRL';
                    rate = parseFloat(data[key].bid);
                    result = this.convertAmount * rate;
                    this.convertRate = `1 ${this.convertFrom} = R$ ${rate.toLocaleString('pt-BR', {minimumFractionDigits: 4})}`;
                } else {
                    const response = await fetch(`https://economia.awesomeapi.com.br/last/${this.convertFrom}-BRL,${this.convertTo}-BRL`);
                    const data = await response.json();
                    const fromRate = parseFloat(data[this.convertFrom + 'BRL'].bid);
                    const toRate = parseFloat(data[this.convertTo + 'BRL'].bid);
                    rate = fromRate / toRate;
                    result = this.convertAmount * rate;
                    this.convertRate = `1 ${this.convertFrom} = ${rate.toLocaleString('pt-BR', {minimumFractionDigits: 6})} ${this.convertTo}`;
                }

                const decimals = (this.convertTo === 'BTC' || this.convertFrom === 'BTC') ? 8 : 2;
                this.convertResult = result.toLocaleString('pt-BR', {minimumFractionDigits: decimals, maximumFractionDigits: decimals}) + ' ' + this.convertTo;
                this.convertTimestamp = new Date().toLocaleString('pt-BR');
            } catch (e) {
                this.error = 'Erro na conversão. Tente novamente.';
            } finally {
                this.loadingConvert = false;
            }
        },

        swapCurrencies() {
            const temp = this.convertFrom;
            this.convertFrom = this.convertTo;
            this.convertTo = temp;
            this.convertResult = null;
        },

        resetConvert() {
            this.convertAmount = 1000;
            this.convertFrom = 'USD';
            this.convertTo = 'BRL';
            this.convertResult = null;
            this.convertRate = null;
            this.convertTimestamp = null;
        }
    }
}
</script>
@endpush
@endsection
