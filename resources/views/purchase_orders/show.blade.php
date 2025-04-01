<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bon de Commande #{{ $purchaseOrder->id }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        function printPurchaseOrder() {
            window.print();
        }
    </script>

    <style>
        @media print {
            @page {
                size: A4;
                margin: 1cm;
            }
            
            body {
                font-size: 12pt;
                padding: 0;
                margin: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .no-print {
                display: none !important;
            }
            
            .print-full-width {
                width: 100% !important;
                max-width: 100% !important;
            }
            
            .print-p-0 {
                padding: 0 !important;
            }
            
            .print-m-0 {
                margin: 0 !important;
            }
            
            .break-before {
                page-break-before: always;
            }
            
            .break-after {
                page-break-after: always;
            }
            
            .break-inside-avoid {
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body class="bg-white text-gray-800 font-sans">


    <div class="container lg:max-w-2xl  mx-auto p-6">
        <div class="no-print text-center mb-6">
            <button onclick="printPurchaseOrder()" class="bg-teal-600 text-white px-6 py-3 rounded-lg hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 transition-colors duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
                </svg>
                Imprimer le bon 
            </button>
        </div>
        <!-- En-tête -->
        <div class="flex justify-between items-start border-b-2 border-gray-200 pb-4 mb-4">
            <div class="w-2/5">
                <h1 class="text-2xl font-bold text-gray-800">{{ config('app.name') }}</h1>
                <p class="text-gray-600">Adresse de l'entreprise</p>
                <p class="text-gray-600">Téléphone: {{ config('app.phone') }}</p>
                <p class="text-gray-600">Email: {{ config('app.email') }}</p>
            </div>

            <div class="w-2/5 text-right">
                <h2 class="text-3xl font-bold text-teal-600">BON DE COMMANDE</h2>
                <div class="mt-2 space-y-1">
                    <p class="text-lg font-semibold">N°: {{ $purchaseOrder->id }}</p>
                    <p class="text-gray-600">Date: {{ $purchaseOrder->date->format('d/m/Y') }}</p>
                    <p class="text-gray-600">Heure: {{ $purchaseOrder->time }}</p>
                </div>
            </div>
        </div>

        <!-- Fournisseur -->
        <div class="bg-gray-50 p-4 rounded-lg mb-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Fournisseur:</h3>
            <p class="font-bold">{{ $purchaseOrder->supplier->name }}</p>
            <p class="text-gray-600">{{ $purchaseOrder->supplier->address }}</p>
            <p class="text-gray-600">Téléphone: {{ $purchaseOrder->supplier->phone }}</p>
            <p class="text-gray-600">Email: {{ $purchaseOrder->supplier->email }}</p>
        </div>

        <!-- Articles -->
        <div class="mb-4">
            <h3 class="text-lg font-semibold mb-2">Articles commandés</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Produit</th>
                            <th class="px-3 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Prix unitaire</th>
                            <th class="px-3 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Quantité</th>
                            <th class="px-3 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Sous-total</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($purchaseOrder->items as $item)
                            <tr>
                                <td class="px-3 py-1 whitespace-nowrap">{{ $item->product->name }}</td>
                                <td class="px-3 py-1 whitespace-nowrap">
                                    {{ number_format($item->unit_price, 2, ',', ' ') }} $</td>
                                <td class="px-3 py-1 whitespace-nowrap">{{ $item->quantity }}</td>
                                <td class="px-3 py-1 whitespace-nowrap">
                                    {{ number_format($item->subtotal, 2, ',', ' ') }} $</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Totaux -->
        <div class="ml-auto w-3/4">
            <div class="bg-gray-50 p-4 rounded-lg">
                <table class="w-full">
                    <tr class="border-b border-gray-200">
                        <td class="py-2 text-gray-600">Total:</td>
                        <td class="py-2 text-right font-medium">{{ number_format($purchaseOrder->total, 2, ',', ' ') }}
                            $</td>
                    </tr>
                    <tr class="border-b border-gray-200">
                        <td class="py-2 text-gray-600">Remise:</td>
                        <td class="py-2 text-right font-medium">
                            {{ number_format($purchaseOrder->discount, 2, ',', ' ') }} $</td>
                    </tr>
                    <tr class="border-b border-gray-200">
                        <td class="py-2 text-gray-600">Montant à payer:</td>
                        <td class="py-2 text-right font-bold">
                            {{ number_format($purchaseOrder->amount_payable, 2, ',', ' ') }} $</td>
                    </tr>
                    <tr>
                        <td class="py-2 text-gray-600">Statut:</td>
                        <td class="py-2 text-right">
                            <span
                                class="px-2 py-1 rounded-full text-xs font-medium {{ $purchaseOrder->paid ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ $purchaseOrder->paid ? 'Payé' : 'En attente' }}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Notes -->
        @if ($purchaseOrder->notes)
            <div class="mt-4 bg-gray-50 p-4 rounded-lg">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Notes:</h3>
                <p class="text-gray-600">{{ $purchaseOrder->notes }}</p>
            </div>
        @endif

        <!-- Pied de page -->
        <div class="mt-8 pt-4 border-t border-gray-200 text-center text-gray-500 text-sm">
            <p>Merci pour votre confiance!</p>
            <p class="mt-1">{{ config('app.name') }} - {{ date('Y') }}</p>
        </div>
    </div>
</body>

</html>
