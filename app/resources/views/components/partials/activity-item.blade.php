{{--
    Partial que renderiza una entrada individual del feed de
    actividad.

    Props:
        - entry: una instancia de `App\Models\ActivityLog`.
        - portalMode: `true` si se renderiza dentro del portal
          cliente. Cambia el link del sujeto (algunos links
          no existen en el portal) y deshabilita enlaces a
          sujetos privados.

    Comportamiento:
        - Borde izquierdo coloreado segun `entry->tone()`.
        - Icono segun `entry->icon()` (sprite SVG inline en el
          switch del partial). Se mantiene inline para no
          depender de una libreria de iconos.
        - Texto: `entry->description`. Si la descripcion tiene
          el patron "Nombre Y resto", el nombre se pinta en
          negrita como actor. Se hace en JS en el futuro si
          se quiere; en MVP un simple `e($entry->description)`
          basta porque el formato es consistente.
        - Link al sujeto: si `entry->subjectUrl()` (admin) o
          `entry->portalSubjectUrl()` (portal) devuelve una URL,
          se renderiza como ancla; si no, se omite.
        - Fecha relativa via `Carbon::diffForHumans()` con
          tooltip de fecha absoluta (`title`).
--}}
@props([
    'entry',
    'portalMode' => false,
])

@php
    use Illuminate\Support\Carbon;

    $tone = $entry->tone();
    $iconName = $entry->icon();
    $subjectUrl = $portalMode ? $entry->portalSubjectUrl() : $entry->subjectUrl();

    // Mapeo de tono a clase Tailwind. Limitado a los tonos
    // que `ActivityType::tone()` puede devolver.
    $toneBorder = match ($tone) {
        'blue' => 'border-l-[#2563EB]',
        'green' => 'border-l-[#16A34A]',
        'amber' => 'border-l-[#D97706]',
        'red' => 'border-l-[#DC2626]',
        'purple' => 'border-l-[#7C3AED]',
        'gray' => 'border-l-[#9CA3AF]',
        default => 'border-l-[#9CA3AF]',
    };

    $toneIconBg = match ($tone) {
        'blue' => 'bg-[#EFF6FF] text-[#2563EB]',
        'green' => 'bg-[#ECFDF5] text-[#16A34A]',
        'amber' => 'bg-[#FFFBEB] text-[#D97706]',
        'red' => 'bg-[#FEF2F2] text-[#DC2626]',
        'purple' => 'bg-[#F5F3FF] text-[#7C3AED]',
        'gray' => 'bg-[#F4F1EA] text-[#6B7280]',
        default => 'bg-[#F4F1EA] text-[#6B7280]',
    };

    // Pinta la primera palabra (el nombre del actor) en negrita.
    // Es una convencion del formato: "Daniel creo la tarea X"
    // -> "Daniel" va en negrita. Si no hay espacio, simplemente
    // pintamos todo el texto en negrita (caso degenerado).
    $rawDescription = $entry->description;
    $actorBold = '';
    $rest = $rawDescription;
    if (preg_match('/^(\S+)\s+(.*)$/u', $rawDescription, $m)) {
        $actorBold = $m[1];
        $rest = $m[2];
    } else {
        $actorBold = $rawDescription;
        $rest = '';
    }

    $createdAt = $entry->created_at instanceof Carbon
        ? $entry->created_at
        : Carbon::parse($entry->created_at);
@endphp

<article
    wire:key="activity-{{ $entry->id }}"
    {{ $attributes->merge(['class' => "flex items-start gap-3 rounded-lg border border-[#E7E2D8] border-l-4 $toneBorder bg-white p-3"]) }}
>
    {{-- Icono --}}
    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $toneIconBg }}">
        @switch($iconName)
            @case('task')
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                @break
            @case('document')
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
                @break
            @case('event')
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                </svg>
                @break
            @case('message')
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                </svg>
                @break
            @case('project')
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 20V6a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                </svg>
                @break
            @case('member')
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                </svg>
                @break
            @case('attachment')
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" />
                </svg>
                @break
            @default
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
        @endswitch
    </span>

    {{-- Contenido --}}
    <div class="min-w-0 flex-1">
        <p class="text-sm text-[#111827]">
            <span class="font-semibold">{{ $actorBold }}</span>
            @if ($rest !== '')
                <span class="text-[#374151]">{{ ' '.$rest }}</span>
            @endif
        </p>

        {{-- Meta inferior: link al sujeto + fecha relativa --}}
        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-[#6B7280]">
            <span
                title="{{ $createdAt->format('d/m/Y H:i') }}"
                class="inline-flex items-center gap-1"
            >
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ $createdAt->diffForHumans() }}
            </span>

            @if ($subjectUrl !== null)
                <span aria-hidden="true">·</span>
                <a href="{{ $subjectUrl }}" class="text-[#2563EB] hover:underline">Ver</a>
            @endif
        </div>
    </div>
</article>
