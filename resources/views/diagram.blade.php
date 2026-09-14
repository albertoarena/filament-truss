{{--
    The Truss diagram, rendered inside a Filament panel.

    This markup is a reproduction of the container in Truss's own dashboard
    view, and that is a deliberate, bounded cost rather than an oversight.
    Truss's frontend resolves its elements by id and reaches most of them
    without a null guard, so the container is a de facto contract that is not
    yet a published one. Two consequences worth knowing before editing this
    file:

      - Removing an element here to simplify the page will break the diagram,
        not degrade it, and the failure will surface at whichever interaction
        touches that element first.
      - An element added upstream must be added here too. The drift guard in
        tests/Pages/SchemaPageRenderTest.php fails when that happens, which is
        the only cheap moment to find out.

    The one deliberate difference from upstream: there is no
    `data-schema-endpoint`, because the payload is embedded below and no
    request is ever made.

    Structure only. The payload carries tables, columns, indexes and foreign
    keys, and never row data.
--}}

<link rel="preload" as="font" type="font/woff2"
      href="{{ route('truss.asset', 'ibm-plex-mono-400.woff2') }}" crossorigin>

<link rel="stylesheet" href="{{ route('truss.asset', 'truss.css') }}">

{{-- After truss.css, which is the whole mechanism: Truss styles `body` for a
     page it owns, and this takes that back and contains the diagram in the
     panel instead. --}}
<link rel="stylesheet" href="{{ route('filament-truss.asset', 'filament-truss.css') }}">

<script src="{{ config('truss.diagram.mermaid_url') ?: route('truss.asset', 'mermaid.min.js') }}"></script>

<div
    id="truss-app"
    class="truss-embed"
    data-connections='@json($connections)'
    data-type-labels="{{ $typeLabels }}"
    data-warn-above="{{ $warnAbove }}"
    data-focus-depth="{{ $focusDepth }}"
    data-min-zoom="{{ $minZoom }}"
    data-doctor-flag-tables="{{ $flagTables ? '1' : '0' }}"
>
    {{-- JSON_HEX_TAG because a column default or a table comment containing a
         closing script tag would otherwise end this block early. Truss reads
         comments as structure, so this is not hypothetical. --}}
    <script type="application/json" data-truss-payload>@json($payload, JSON_HEX_TAG)</script>

    <div class="truss-toolbar">
        <label class="truss-field truss-field--search">
            <span class="truss-field-label">Filter</span>
            <input id="truss-search" type="search" placeholder="table name…" autocomplete="off">
        </label>

        <div class="truss-secondary" id="truss-more">
            <div class="truss-field truss-combo">
                <label class="truss-field-label" for="truss-focus">Focus</label>
                <input id="truss-focus" type="text" role="combobox" autocomplete="off"
                       aria-expanded="false" aria-controls="truss-focus-list"
                       aria-autocomplete="list" placeholder="none">
                <ul class="truss-combo-list" id="truss-focus-list" role="listbox"
                    aria-label="Tables" hidden></ul>
                <span class="truss-sr-only" id="truss-focus-status" role="status" aria-live="polite"></span>
            </div>
            <label class="truss-field">
                <span class="truss-field-label">Depth</span>
                <input id="truss-depth" type="number" min="0" step="1">
            </label>
            <label class="truss-field truss-field--check">
                <input id="truss-labels" type="checkbox"> <span class="truss-field-label">Laravel types</span>
            </label>
        </div>

        <div class="truss-utils">
            <label class="truss-field truss-field--conn" hidden>
                <span class="truss-field-label">Connections</span>
                <select id="truss-connection"></select>
            </label>
            <button type="button" class="truss-util truss-util--more" id="truss-more-btn" title="More controls" aria-expanded="false">⋯</button>
            <button type="button" class="truss-util" id="truss-export-btn" title="Export the diagram (PNG or SVG)" aria-expanded="false">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 3 v11"/><path d="M7 9 l5 5 5-5"/><path d="M4 20 h16"/>
                </svg>
            </button>
            <button type="button" class="truss-util" id="truss-diff-btn" title="What changed since the last migration" aria-expanded="false" hidden>
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 8 h8 M9 4 v8"/><path d="M11 17 h8"/>
                </svg>
            </button>
            <button type="button" class="truss-util" id="truss-health-btn" title="Structure health (truss:doctor findings)" aria-expanded="false" hidden>
                <svg class="truss-health-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                    <path d="M3.22 12H9.5l.5-1 2 4.5 2-7 1.5 3.5h5.27"/>
                </svg>
                <span class="truss-health-count" id="truss-health-count" aria-hidden="true" hidden></span>
            </button>
            <button type="button" class="truss-util" id="truss-legend-btn" title="Legend" aria-expanded="false">▤</button>
            <button type="button" class="truss-util" id="truss-theme-btn" title="Theme">◐</button>
        </div>
    </div>

    <div id="truss-banners"></div>

    <main id="truss-viewport">
        <div id="truss-canvas"></div>

        <div class="truss-zoom" id="truss-zoom">
            <button type="button" data-fit title="Fit the whole diagram in view">Fit</button>
            <input id="truss-zoom-range" type="range" min="0.1" max="3" step="0.02" value="1"
                   aria-label="Zoom" title="Zoom (scroll or pinch over the diagram)">
            <span id="truss-zoom-pct">100%</span>
        </div>
    </main>

    <div class="truss-legend" id="truss-legend" hidden>
        <div class="truss-legend-head">Legend</div>
        <dl class="truss-legend-list">
            <dt>PK</dt><dd>Primary key</dd>
            <dt>FK</dt><dd>Foreign key</dd>
            <dt aria-hidden="true">
                <svg width="42" height="12" viewBox="0 0 42 12" fill="none" stroke="currentColor" stroke-width="1.3">
                    <path d="M2 2 V10 M5 2 V10"/><path d="M5 6 H30"/>
                    <path d="M30 6 L38 2 M30 6 L38 10 M30 6 H38"/><circle cx="35" cy="6" r="2.4"/>
                </svg>
            </dt><dd>One to many</dd>
        </dl>
    </div>

    <div class="truss-diff-panel" id="truss-diff-panel" hidden>
        <div class="truss-diff-head">Changes since last migration</div>
        <div class="truss-diff-body"></div>
    </div>

    <div class="truss-health-panel" id="truss-health-panel" hidden>
        <div class="truss-health-head">
            <span>Structure health</span>
            <button type="button" class="truss-health-max" id="truss-health-max-btn" title="Maximize" aria-label="Maximize" aria-pressed="false">⤢</button>
        </div>
        <div class="truss-health-body"></div>
    </div>

    <div class="truss-popover" id="truss-popover" hidden></div>

    <footer class="truss-footer">
        <span id="truss-stat-tables">&nbsp;</span>
        <span id="truss-stat-conn"></span>
        <span class="truss-flag" id="truss-stat-fallback" hidden>SQLite&nbsp;fallback</span>
        <span class="truss-footer-spacer"></span>
        <span id="truss-stat-updated"></span>
    </footer>
</div>

<script type="module" src="{{ route('truss.asset', 'truss.js') }}"></script>

{{-- Joins Filament's dark class to the data attribute Truss reads. --}}
<script src="{{ route('filament-truss.asset', 'filament-truss.js') }}"></script>
