// The listing cover, as markup.
//
// Kept apart from `cover.mjs` for one reason: the composition is the part that
// can be wrong without a browser, and a pure function is a part that can be
// tested. `tests/js/cover-template.test.js` drives it. The driver next door
// does the half that genuinely needs Chromium: sign in, photograph the page,
// photograph this.
//
// Every colour is a custom property declared once per theme. That is not tidiness:
// `art/README.md` records a first pass that hard-coded `--gray-950` for the
// heading, and the dark image came back with an invisible title. A literal
// colour in a rule is a value one of the two themes will get wrong.

/** HTML-escape, because a tagline is prose and prose carries `&` and `<`. */
function escape(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

/**
 * @param {object} cover
 * @param {string} cover.eyebrow     Small line above the name.
 * @param {string} cover.title       First half of the wordmark, in the ink colour.
 * @param {string} cover.accent      Second half, in the amber gradient.
 * @param {string} cover.tagline     One or two lines of what it does.
 * @param {string[]} cover.pills     Feature names, three at most before they wrap.
 * @param {string} cover.shot        The page photograph, as a data URI.
 * @param {string} cover.caption     The window title bar's text.
 * @param {string} [cover.install]   The one command that gets it, set as one.
 * @param {boolean} [cover.dark]     Paint the dark variant.
 * @param {number} [cover.width]     Logical canvas width. The device scale factor doubles it.
 * @param {number} [cover.height]    Logical canvas height.
 * @returns {string} a complete, self-contained HTML document
 */
export function coverHtml({
  eyebrow,
  title,
  accent,
  tagline,
  pills = [],
  shot,
  caption,
  install = '',
  dark = false,
  width = 1600,
  height = 900,
}) {
  const theme = dark ? ' data-theme="dark"' : '';

  return `<!doctype html>
<html lang="en"${theme}>
<head>
<meta charset="utf-8">
<title>${escape(title)} ${escape(accent)}</title>
<style>
  /* Light, and the recommended variant: the listing form asks for a light theme. */
  :root {
    --bg: #ffffff;
    --bg-wash: #fdf6ec;
    --grid: rgba(17, 24, 39, 0.045);
    --glow: rgba(245, 158, 11, 0.16);
    --ink: #111827;
    --ink-soft: #4b5563;
    --eyebrow: #78716c;
    --accent: #f59e0b;
    --accent-from: #f59e0b;
    --accent-to: #b45309;
    --pill-bg: rgba(255, 255, 255, 0.85);
    --pill-ink: #374151;
    --pill-line: rgba(17, 24, 39, 0.1);
    --frame-bg: #ffffff;
    --frame-line: rgba(17, 24, 39, 0.1);
    --frame-rim: rgba(245, 158, 11, 0.55);
    --frame-bar: #fbfaf9;
    --dot-red: #f87171;
    --dot-amber: #fbbf24;
    --dot-green: #34d399;
    --shadow: 0 60px 120px -30px rgba(17, 24, 39, 0.28), 0 18px 40px -18px rgba(17, 24, 39, 0.22);
  }

  /* Same token set, different values. The test next door fails if one drifts. */
  :root[data-theme="dark"] {
    --bg: #0b0f16;
    --bg-wash: #17110a;
    --grid: rgba(255, 255, 255, 0.05);
    --glow: rgba(245, 158, 11, 0.2);
    --ink: #f4f4f5;
    --ink-soft: #a1a1aa;
    --eyebrow: #a8a29e;
    --accent: #fbbf24;
    --accent-from: #fcd34d;
    --accent-to: #f59e0b;
    --pill-bg: rgba(255, 255, 255, 0.05);
    --pill-ink: #d4d4d8;
    --pill-line: rgba(255, 255, 255, 0.12);
    --frame-bg: #0f141c;
    --frame-line: rgba(255, 255, 255, 0.1);
    --frame-rim: rgba(251, 191, 36, 0.5);
    --frame-bar: #141a24;
    --dot-red: #ef4444;
    --dot-amber: #f59e0b;
    --dot-green: #10b981;
    --shadow: 0 60px 120px -30px rgba(0, 0, 0, 0.75), 0 18px 40px -18px rgba(0, 0, 0, 0.6);
  }

  * { box-sizing: border-box; }

  html, body {
    margin: 0;
    padding: 0;
    background: var(--bg);
  }

  body {
    /* No webfont: the photographer may have no route out, and a font that
       fails to arrive is a cover set in Times. These are all local. */
    font-family: 'Inter', 'Avenir Next', ui-sans-serif, system-ui, -apple-system,
                 'Helvetica Neue', Arial, sans-serif;
    -webkit-font-smoothing: antialiased;
  }

  .stage {
    position: relative;
    overflow: hidden;
    background:
      radial-gradient(120% 90% at 100% 0%, var(--glow), transparent 55%),
      linear-gradient(135deg, var(--bg-wash), var(--bg) 45%);
  }

  /* The faint graph paper behind everything, which is also what the diagram
     itself sits on inside the page. The cover and the product agree. */
  .grid {
    position: absolute;
    inset: 0;
    background-image:
      linear-gradient(var(--grid) 1px, transparent 1px),
      linear-gradient(90deg, var(--grid) 1px, transparent 1px);
    background-size: 48px 48px;
    mask-image: radial-gradient(120% 100% at 20% 40%, black, transparent 75%);
  }

  .rail {
    position: absolute;
    left: 6%;
    top: 50%;
    width: 36%;
    transform: translateY(-50%);
  }

  .eyebrow {
    display: flex;
    align-items: center;
    gap: 0.7em;
    margin: 0 0 1.6rem;
    color: var(--eyebrow);
    font-size: 1.05rem;
    font-weight: 700;
    letter-spacing: 0.22em;
    text-transform: uppercase;
  }

  .eyebrow::before {
    content: '';
    width: 0.62em;
    height: 0.62em;
    border-radius: 50%;
    background: var(--accent);
    box-shadow: 0 0 0 0.35em var(--glow);
  }

  h1 {
    margin: 0;
    font-weight: 800;
    letter-spacing: -0.035em;
    line-height: 0.98;
  }

  .name-quiet {
    display: block;
    color: var(--ink);
    font-size: 3.6rem;
    font-weight: 700;
  }

  /* The gradient is the wordmark. Clipped to the glyphs rather than painted
     behind them, so it works on either background. */
  .name-loud {
    display: block;
    background-image: linear-gradient(170deg, var(--accent-from), var(--accent-to));
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    font-size: 6.1rem;
    letter-spacing: -0.045em;
  }

  .tagline {
    max-width: 30ch;
    margin: 2rem 0 0;
    color: var(--ink-soft);
    font-size: 1.5rem;
    font-weight: 400;
    line-height: 1.45;
  }

  .pills {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin: 2.6rem 0 0;
    padding: 0;
    list-style: none;
  }

  .pills li {
    display: flex;
    align-items: baseline;
    gap: 0.45em;
    padding: 0.62em 1.1em;
    border: 1px solid var(--pill-line);
    border-radius: 999px;
    background: var(--pill-bg);
    color: var(--pill-ink);
    font-size: 1.02rem;
    font-weight: 600;
  }

  .pills li::before {
    content: '\\203A';
    color: var(--accent);
    font-weight: 700;
  }

  /* The one command that gets it, which is a thing to type and is set as one. */
  .install {
    display: flex;
    align-items: center;
    gap: 0.6em;
    margin: 2.2rem 0 0;
    color: var(--ink-soft);
    font-family: 'SFMono-Regular', ui-monospace, 'Menlo', 'Consolas', monospace;
    font-size: 1rem;
  }

  .install::before {
    content: '$';
    color: var(--accent);
    font-weight: 700;
  }

  /* The window bleeds off the right edge on purpose: a frame with air on all
     four sides reads as a slide, one that runs off reads as a screen. */
  .window {
    position: absolute;
    left: 47%;
    top: 9%;
    width: 68%;
    height: 82%;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid var(--frame-line);
    border-left: 3px solid var(--frame-rim);
    border-radius: 20px;
    background: var(--frame-bg);
    box-shadow: var(--shadow);
    transform: perspective(2400px) rotateY(-8deg) rotateX(1.5deg) rotate(-1deg);
    transform-origin: left center;
  }

  .bar {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    flex: none;
    padding: 0.95rem 1.4rem;
    border-bottom: 1px solid var(--frame-line);
    background: var(--frame-bar);
    color: var(--ink);
    font-size: 1.05rem;
    font-weight: 700;
  }

  .dots {
    display: flex;
    gap: 0.45rem;
  }

  .dots i {
    width: 0.78rem;
    height: 0.78rem;
    border-radius: 50%;
  }

  .dots i:nth-child(1) { background: var(--dot-red); }
  .dots i:nth-child(2) { background: var(--dot-amber); }
  .dots i:nth-child(3) { background: var(--dot-green); }

  .shot {
    display: block;
    width: 100%;
    flex: 1;
    min-height: 0;
    object-fit: cover;
    object-position: left top;
  }
</style>
</head>
<body>
  <div class="stage" data-stage style="width: ${width}px; height: ${height}px">
    <div class="grid"></div>

    <div class="rail">
      <p class="eyebrow">${escape(eyebrow)}</p>
      <h1>
        <span class="name-quiet">${escape(title)}</span>
        <span class="name-loud">${escape(accent)}</span>
      </h1>
      <p class="tagline">${escape(tagline)}</p>
      <ul class="pills">${pills.map((pill) => `<li>${escape(pill)}</li>`).join('')}</ul>
      ${install ? `<p class="install"><code>${escape(install)}</code></p>` : ''}
    </div>

    <div class="window">
      <div class="bar">
        <span class="dots"><i></i><i></i><i></i></span>
        <span>${escape(caption)}</span>
      </div>
      <img class="shot" src="${escape(shot)}" alt="">
    </div>
  </div>
</body>
</html>`;
}
