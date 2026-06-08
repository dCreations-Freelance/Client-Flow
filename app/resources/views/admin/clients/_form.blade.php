@csrf
<div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(260px,1fr)]">
    <section class="rounded-[20px] border border-[#E7E2D8] bg-white p-6 shadow-[0_10px_30px_rgba(17,24,39,0.05)]">
        <h2 class="text-xl font-semibold">Datos del cliente</h2>
        <div class="mt-6 space-y-5">
            <div><label class="mb-2 block text-sm font-medium">Nombre</label><input name="name" value="{{ old('name', $client->name) }}" required class="w-full rounded-xl border border-[#E7E2D8] px-4 py-3 text-sm outline-none focus:border-[#B88746]">@error('name') <p class="mt-2 text-sm text-[#B91C1C]">{{ $message }}</p> @enderror</div>
            <div><label class="mb-2 block text-sm font-medium">Empresa</label><input name="company" value="{{ old('company', $client->company) }}" class="w-full rounded-xl border border-[#E7E2D8] px-4 py-3 text-sm outline-none focus:border-[#B88746]"></div>
            <div><label class="mb-2 block text-sm font-medium">Email</label><input name="email" type="email" value="{{ old('email', $client->email) }}" required class="w-full rounded-xl border border-[#E7E2D8] px-4 py-3 text-sm outline-none focus:border-[#B88746]">@error('email') <p class="mt-2 text-sm text-[#B91C1C]">{{ $message }}</p> @enderror</div>
            <div><label class="mb-2 block text-sm font-medium">Teléfono</label><input name="phone" value="{{ old('phone', $client->phone) }}" class="w-full rounded-xl border border-[#E7E2D8] px-4 py-3 text-sm outline-none focus:border-[#B88746]"></div>
            <div><label class="mb-2 block text-sm font-medium">Notas internas</label><textarea name="notes" rows="5" class="w-full rounded-xl border border-[#E7E2D8] px-4 py-3 text-sm outline-none focus:border-[#B88746]">{{ old('notes', $client->notes) }}</textarea></div>
        </div>
    </section>
    <aside class="rounded-[20px] border border-[#E7E2D8] bg-white p-6 shadow-[0_10px_30px_rgba(17,24,39,0.05)]">
        <h2 class="text-xl font-semibold">Acceso al portal</h2>
        <label class="mt-6 block text-sm font-medium">Estado</label>
        <select name="status" class="mt-2 w-full rounded-xl border border-[#E7E2D8] px-4 py-3 text-sm outline-none focus:border-[#B88746]">
            @foreach (['active' => 'Activo', 'inactive' => 'Inactivo'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $client->status ?: 'active') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="mt-6 w-full rounded-xl bg-[#111827] px-5 py-3 text-sm font-semibold text-white hover:bg-black">{{ $submit }}</button>
    </aside>
</div>
