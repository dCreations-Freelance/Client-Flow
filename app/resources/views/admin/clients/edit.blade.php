<x-layouts.admin title="Editar cliente">
    <div class="mb-8"><h1 class="text-3xl font-semibold tracking-tight">Editar cliente</h1><p class="mt-3 text-[#6B7280]">Actualiza la información y estado del cliente.</p></div>
    <form method="POST" action="{{ route('admin.clients.update', $client) }}">@method('PUT') @include('admin.clients._form', ['submit' => 'Guardar cambios'])</form>
</x-layouts.admin>
