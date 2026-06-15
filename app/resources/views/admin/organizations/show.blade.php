<x-layouts.admin :title="$organization->name">
    <div class="space-y-6">
        <a href="{{ route('admin.organizations.index') }}" class="inline-flex items-center text-sm text-[#6B7280] hover:text-[#111827]">
            &larr; Volver al listado
        </a>

        @if (session('status'))
            <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
        @endif
        @if ($errors->any())
            <x-ui.alert variant="error">
                <ul class="list-disc pl-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        <div class="grid gap-6 lg:grid-cols-3">
            <x-ui.card class="lg:col-span-2">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-xl font-semibold">{{ $organization->name }}</h2>
                        <p class="mt-1 text-sm text-[#6B7280]">Slug: {{ $organization->slug }}</p>
                    </div>
                    <span @class([
                        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                        'bg-[#F0FDF4] text-[#16A34A]' => $organization->status->value === 'active',
                        'bg-[#F4F1EA] text-[#6B7280]' => $organization->status->value === 'inactive',
                    ])>
                        {{ $organization->status->label() }}
                    </span>
                </div>
                @if ($organization->description)
                    <p class="mt-4 text-sm text-[#111827]">{{ $organization->description }}</p>
                @endif
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('admin.organizations.edit', $organization) }}" class="inline-flex items-center justify-center rounded-lg border border-[#E7E2D8] bg-white px-4 py-2 text-sm font-medium hover:bg-[#F4F1EA]">
                        Editar
                    </a>
                    <a href="{{ route('admin.organizations.members', $organization) }}" class="inline-flex items-center justify-center rounded-lg border border-[#E7E2D8] bg-white px-4 py-2 text-sm font-medium hover:bg-[#F4F1EA]">
                        Gestionar miembros
                    </a>
                </div>
            </x-ui.card>

            <x-ui.card>
                <h3 class="text-sm font-semibold text-[#111827]">Resumen</h3>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-[#6B7280]">Miembros</dt>
                        <dd class="font-medium">{{ $organization->members->count() }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-[#6B7280]">Invitaciones pendientes</dt>
                        <dd class="font-medium">{{ $organization->pendingInvitations->count() }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-[#6B7280]">Proyectos</dt>
                        <dd class="font-medium">0</dd>
                    </div>
                </dl>
            </x-ui.card>
        </div>

        <x-ui.card>
            <h3 class="text-sm font-semibold text-[#111827]">Miembros</h3>
            <ul class="mt-4 divide-y divide-[#E7E2D8]">
                @forelse ($organization->members as $member)
                    <li class="flex items-center justify-between py-3 text-sm">
                        <div>
                            <p class="font-medium text-[#111827]">{{ $member->name }}</p>
                            <p class="text-xs text-[#6B7280]">{{ $member->email }}</p>
                        </div>
                        <span class="inline-flex items-center rounded-full bg-[#EFF6FF] px-2.5 py-0.5 text-xs font-medium text-[#2563EB]">
                            {{ ucfirst($member->pivot->role) }}
                        </span>
                    </li>
                @empty
                    <li class="py-4 text-center text-sm text-[#6B7280]">Aun no hay miembros.</li>
                @endforelse
            </ul>
        </x-ui.card>

        @if ($organization->pendingInvitations->isNotEmpty())
            <x-ui.card>
                <h3 class="text-sm font-semibold text-[#111827]">Invitaciones pendientes</h3>
                <ul class="mt-4 divide-y divide-[#E7E2D8]">
                    @foreach ($organization->pendingInvitations as $invitation)
                        <li class="flex items-center justify-between py-3 text-sm">
                            <div>
                                <p class="font-medium text-[#111827]">{{ $invitation->email }}</p>
                                <p class="text-xs text-[#6B7280]">Expira el {{ $invitation->expires_at->format('d/m/Y H:i') }}</p>
                            </div>
                            <span class="inline-flex items-center rounded-full bg-[#FFFBEB] px-2.5 py-0.5 text-xs font-medium text-[#D97706]">
                                Pendiente
                            </span>
                        </li>
                    @endforeach
                </ul>
            </x-ui.card>
        @endif
    </div>
</x-layouts.admin>
