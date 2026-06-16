{{--
    Wrapper para contenido markdown renderizado.

    Aplica estilos consistentes con la paleta warm y tipografia del
    design system a un HTML que viene de `Str::markdown()`. Centraliza
    el aspecto del "markdown body" para que admin/portal preview y
    vista de lectura coincidan.

    Uso:
        <x-partials.markdown-body :html="$document->rendered_content" />
--}}
@props(['html'])

<div {{ $attributes->merge(['class' => 'markdown-body']) }}>
    {!! $html !!}
</div>

<style>
    /*
        Estilos minimos para el markdown renderizado. Mantenemos
        clases nativas de Tailwind via @apply seria posible, pero
        usamos CSS plano para no inflar el bundle con clases que
        solo se usan aqui.
    */
    .markdown-body {
        color: #111827;
        font-size: 0.95rem;
        line-height: 1.6;
    }
    .markdown-body h1,
    .markdown-body h2,
    .markdown-body h3,
    .markdown-body h4 {
        font-weight: 600;
        color: #111827;
        margin-top: 1.5rem;
        margin-bottom: 0.75rem;
        line-height: 1.25;
    }
    .markdown-body h1 { font-size: 1.5rem; }
    .markdown-body h2 { font-size: 1.25rem; }
    .markdown-body h3 { font-size: 1.125rem; }
    .markdown-body h4 { font-size: 1rem; }
    .markdown-body h1:first-child,
    .markdown-body h2:first-child,
    .markdown-body h3:first-child { margin-top: 0; }
    .markdown-body p { margin: 0 0 1rem 0; }
    .markdown-body a { color: #2563EB; text-decoration: underline; text-underline-offset: 2px; }
    .markdown-body a:hover { color: #1D4ED8; }
    .markdown-body ul,
    .markdown-body ol { margin: 0 0 1rem 1.5rem; }
    .markdown-body ul { list-style: disc; }
    .markdown-body ol { list-style: decimal; }
    .markdown-body li { margin: 0.25rem 0; }
    .markdown-body code {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 0.85em;
        background: #F4F1EA;
        color: #111827;
        padding: 0.1rem 0.35rem;
        border-radius: 0.25rem;
    }
    .markdown-body pre {
        background: #111827;
        color: #FAFAF7;
        padding: 1rem;
        border-radius: 0.5rem;
        overflow-x: auto;
        margin: 0 0 1rem 0;
    }
    .markdown-body pre code {
        background: transparent;
        color: inherit;
        padding: 0;
    }
    .markdown-body blockquote {
        border-left: 3px solid #E7E2D8;
        padding: 0.25rem 0 0.25rem 1rem;
        color: #6B7280;
        margin: 0 0 1rem 0;
    }
    .markdown-body hr {
        border: 0;
        border-top: 1px solid #E7E2D8;
        margin: 1.5rem 0;
    }
    .markdown-body table {
        width: 100%;
        border-collapse: collapse;
        margin: 0 0 1rem 0;
    }
    .markdown-body th,
    .markdown-body td {
        border: 1px solid #E7E2D8;
        padding: 0.5rem 0.75rem;
        text-align: left;
    }
    .markdown-body th {
        background: #FAFAF7;
        font-weight: 600;
    }
    .markdown-body img {
        max-width: 100%;
        height: auto;
        border-radius: 0.5rem;
    }
</style>
