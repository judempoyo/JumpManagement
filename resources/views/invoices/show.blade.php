<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture #{{ $invoice->id }}</title>
    
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance 
</head>
<body class="bg-white text-gray-800 font-sans">
    <div class="container lg:max-w-1/3  mx-auto p-6">
        <!-- En-tête -->
        <div class="flex justify-between items-start border-b-2 border-gray-200 pb-6 mb-8">
            <div class="w-2/5">
                <h1 class="text-2xl font-bold text-gray-800">{{ config('app.name') }}</h1>
                <p class="text-gray-600">Adresse de l'entreprise</p>
                <p class="text-gray-600">Téléphone: {{ config('app.phone') }}</p>
                <p class="text-gray-600">Email: {{ config('app.email') }}</p>
            </div>
            
            <div class="w-2/5 text-right">
                <h2 class="text-3xl font-bold text-blue-600">FACTURE</h2>
                <div class="mt-2 space-y-1">
                    <p class="text-lg font-semibold">N°: {{ $invoice->id }}</p>
                    <p class="text-gray-600">Date: {{ $invoice->date->format('d/m/Y') }}</p>
                    <p class="text-gray-600">Heure: {{ $invoice->time }}</p>
                </div>
            </div>
        </div>

        <!-- Client -->
<div class="bg-gray-50 p-4 rounded-lg mb-8">
    <h3 class="text-lg font-semibold text-gray-800 mb-2">Client:</h3>
    <p class="font-bold">{{ $invoice->customer->name }}</p>
    @if($invoice->customer_id)
        <p class="text-gray-600">{{ $invoice->customer->address }}</p>
        <p class="text-gray-600">Téléphone: {{ $invoice->customer->phone }}</p>
        <p class="text-gray-600">Email: {{ $invoice->customer->email }}</p>
    @endif
</div>
        <!-- Articles -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold mb-4">Articles</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produit</th>
                            <th class="px-3 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prix unitaire</th>
                            <th class="px-3 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantité</th>
                            <th class="px-3 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sous-total</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($invoice->items as $item)
                        <tr>
                            <td class="px-4 py-2 whitespace-nowrap">{{ $item->product->name }}</td>
                            <td class="px-4 py-2 whitespace-nowrap">{{ number_format($item->unit_price, 2, ',', ' ') }} $</td>
                            <td class="px-4 py-2 whitespace-nowrap">{{ $item->quantity }}</td>
                            <td class="px-4 py-2 whitespace-nowrap">{{ number_format($item->subtotal, 2, ',', ' ') }} $</td>
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
                        <td class="py-2 text-right font-medium">{{ number_format($invoice->total, 2, ',', ' ') }} $</td>
                    </tr>
                    <tr class="border-b border-gray-200">
                        <td class="py-2 text-gray-600">Remise:</td>
                        <td class="py-2 text-right font-medium">{{ number_format($invoice->discount, 2, ',', ' ') }} $</td>
                    </tr>
                    <tr class="border-b border-gray-200">
                        <td class="py-2 text-gray-600">Montant à payer:</td>
                        <td class="py-2 text-right font-bold">{{ number_format($invoice->amount_payable, 2, ',', ' ') }} $</td>
                    </tr>
                    <tr>
                        <td class="py-2 text-gray-600">Statut:</td>
                        <td class="py-2 text-right">
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $invoice->paid ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ $invoice->paid ? 'Payée' : 'En attente' }}
                            </span>
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $invoice->delivered ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ $invoice->delivered ? 'Livrée' : 'Non livrée' }}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Notes -->
        @if($invoice->notes)
        <div class="mt-8 bg-gray-50 p-4 rounded-lg">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Notes:</h3>
            <p class="text-gray-600">{{ $invoice->notes }}</p>
        </div>
        @endif

        <!-- Pied de page -->
        <div class="mt-12 pt-6 border-t border-gray-200 text-center text-gray-500 text-sm">
            <p>Merci pour votre confiance!</p>
            <p class="mt-1">{{ config('app.name') }} - {{ date('Y') }}</p>
        </div>
    </div>
</body>
</html>