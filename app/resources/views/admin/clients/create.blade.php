<x-layouts.admin title="Nuevo cliente">
    <div class="mb-8"><h1 class="text-3xl font-semibold tracking-tight">Nuevo cliente</h1><p class="mt-3 text-[#6B7280]">Crea un cliente manualmente y asocia sus proyectos después.</p></div>
    <form method="POST" action="{{ route('admin.clients.store') }}">@include('admin.clients._form', ['submit' => 'Guardar cliente'])</form>
</x-layouts.admin>
