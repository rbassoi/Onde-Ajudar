// wf-app.jsx — main: header, tabs, tweaks
const { useState: useStateApp, useEffect } = React;

const TWEAK_DEFAULTS = /*EDITMODE-BEGIN*/{
  "accent": "#cf6a44",
  "hand": "'Patrick Hand', cursive",
  "annotations": true,
  "compare": false
}/*EDITMODE-END*/;

function App() {
  const [t, setTweak] = useTweaks(TWEAK_DEFAULTS);
  const [tab, setTab] = useStateApp(0);

  useEffect(() => {
    const r = document.documentElement;
    r.style.setProperty("--accent", t.accent);
    r.style.setProperty("--accent-soft", t.accent + "22");
    r.style.setProperty("--hand", t.hand);
    r.classList.toggle("no-anno", !t.annotations);
  }, [t.accent, t.hand, t.annotations]);

  const shown = t.compare ? APPROACHES : [APPROACHES[tab]];

  return (
    <div>
      <header className="doc-head">
        <span className="kicker">◷ wireframes · baixa fidelidade</span>
        <h1>Onde ajudar — avistamentos na rua</h1>
        <p>Três caminhos para <span className="scrib">registrar e visualizar</span> pessoas em situação de rua, no celular. Rascunhos para comparar a estrutura antes de partir pro visual. Use os <b>Tweaks</b> para trocar cor, letra ou ver tudo lado a lado.</p>
      </header>

      {!t.compare && (
        <nav className="tabs" role="tablist">
          {APPROACHES.map((a, i) => (
            <div key={a.id} className="tab" role="tab" aria-selected={tab === i} onClick={() => setTab(i)}>
              <span className="num">{i + 1}</span>{a.tab}
            </div>
          ))}
        </nav>
      )}

      <main className="panel">
        {shown.map((a) => (
          <section key={a.id} style={{ marginBottom: t.compare ? 46 : 0 }}>
            <div className="approach-head">
              <div>
                <div className="big">{t.compare ? "▶ " : ""}{a.title}</div>
                <div className="desc">{a.desc}</div>
                <div className="pill-row">{a.pills.map((p, i) => <span key={i} className="pill" dangerouslySetInnerHTML={{ __html: p.replace(/(Melhor p\/|Risco|Toques p\/ registrar):/, "<b>$1:</b>") }} />)}</div>
              </div>
            </div>
            <div className="row">
              {a.screens.map((S, i) => <S key={i} />)}
            </div>
          </section>
        ))}
      </main>

      <TweaksPanel>
        <TweakSection label="Visualização" />
        <TweakToggle label="Comparar todas lado a lado" value={t.compare} onChange={(v) => setTweak("compare", v)} />
        <TweakToggle label="Mostrar anotações" value={t.annotations} onChange={(v) => setTweak("annotations", v)} />
        <TweakSection label="Aparência do rascunho" />
        <TweakColor label="Cor de acento (quente)" value={t.accent}
          options={["#cf6a44", "#c98a2e", "#c14d3e", "#b1683f", "#9a7b4a"]}
          onChange={(v) => setTweak("accent", v)} />
        <TweakSelect label="Letra (handwritten)" value={t.hand}
          options={[
            { value: "'Patrick Hand', cursive", label: "Patrick Hand" },
            { value: "'Gaegu', cursive", label: "Gaegu" },
            { value: "'Caveat', cursive", label: "Caveat" },
          ]}
          onChange={(v) => setTweak("hand", v)} />
      </TweaksPanel>
    </div>
  );
}

ReactDOM.createRoot(document.getElementById("root")).render(<App />);
