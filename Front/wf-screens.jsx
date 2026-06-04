// wf-screens.jsx — wireframe primitives + the 3 approach screen sets
const { useState } = React;

/* ----------------------------- primitives ----------------------------- */
function Phone({ label, sub, note, children }) {
  return (
    <figure className="phone-wrap" data-screen-label={label} style={{ margin: 0 }}>
      <div className="phone">
        <div className="notch"></div>
        <div className="screen">{children}</div>
      </div>
      <figcaption className="cap">
        <b>{label}</b>
        {sub}
        {note && <div className="note">{note}</div>}
      </figcaption>
    </figure>
  );
}
const Status = () => (
  <div className="status"><span>9:41</span><span>◔ ▮▮▮</span></div>
);
function Top({ ttl, left, right }) {
  return (
    <div className="topbar">
      <div style={{ display: "flex", alignItems: "center", gap: 8 }}>
        {left && <div className="icon-btn">{left}</div>}
        <div className="ttl">{ttl}</div>
      </div>
      {right && <div className="icon-btn">{right}</div>}
    </div>
  );
}
function MapBox({ children, pins = [], label }) {
  return (
    <div className="map">
      <div className="road" style={{ left: "22%", top: 0, bottom: 0, width: 3 }}></div>
      <div className="road" style={{ left: "64%", top: 0, bottom: 0, width: 5 }}></div>
      <div className="road" style={{ top: "38%", left: 0, right: 0, height: 4 }}></div>
      <div className="road" style={{ top: "72%", left: 0, right: 0, height: 3 }}></div>
      {pins.map((p, i) => (
        <div key={i} className={"pin" + (p.ghost ? " ghost" : "")} style={{ left: p.x, top: p.y }}>
          <span className="dot"></span>
        </div>
      ))}
      {label && <div className="maplabel">{label}</div>}
      {children}
    </div>
  );
}
const Photo = ({ h, children }) => (
  <div className="photo" style={h ? { minHeight: h } : null}>{children || "+ foto do local"}</div>
);
function Field({ lab, val, line }) {
  return (
    <div className={"field" + (line ? " line" : "")}>
      {lab && <div className="lab">{lab}</div>}
      <div className="val">{val}</div>
    </div>
  );
}
const Chip = ({ on, children }) => (
  <div className={"chip" + (on ? " on" : "")}><span className="bx"></span>{children}</div>
);
const Btn = ({ k = "", children }) => <div className={"btn " + k}>{children}</div>;
const Tag = ({ children }) => <span className="tag">{children}</span>;
const Steps = ({ n, of }) => (
  <div className="stepdots">{Array.from({ length: of }).map((_, i) => <i key={i} className={i < n ? "on" : ""}></i>)}</div>
);

/* ============================ APPROACH A ============================ */
/* Map-first: abre no mapa, registrar é uma folha que sobe */
function A1() {
  return (
    <Phone label="A1 · Mapa (início)" sub=" — visão geral da cidade" note="Abre direto no mapa com os avistamentos perto de você. Botão flutuante sempre à mão.">
      <Status />
      <Top ttl="Avistamentos" left="≡" right="⌕" />
      <MapBox label="📍 perto de você"
        pins={[{ x: "28%", y: "30%" }, { x: "55%", y: "22%" }, { x: "44%", y: "55%" }, { x: "70%", y: "64%" }, { x: "18%", y: "70%", ghost: true }]}>
        <div className="fab">＋ Registrar</div>
      </MapBox>
      <div className="seclab">Toque num pino para ver detalhes · pino claro = já atendido</div>
    </Phone>
  );
}
function A2() {
  return (
    <Phone label="A2 · Registrar" sub=" — folha que sobe" note="Localização já vem preenchida pelo GPS. Tudo em uma tela só, rolável.">
      <Status />
      <div className="h-ttl">Novo avistamento</div>
      <Field lab="local (GPS — toque p/ ajustar)" val="R. das Flores, 120 — Centro" />
      <div className="row-2">
        <Field lab="pessoas no local" val="2  ⊝ ⊕" />
        <Field lab="contato (opcional)" val="seu nome" />
      </div>
      <div>
        <div className="seclab">necessidades urgentes <Tag>toque os que valem</Tag></div>
        <div className="chips" style={{ marginTop: 6 }}>
          <Chip on>cobertor</Chip><Chip>comida</Chip><Chip on>saúde</Chip><Chip>água</Chip><Chip>criança</Chip>
        </div>
      </div>
      <Field lab="descrição (opcional)" val="Senhor idoso, parece com febre…" />
      <Photo h={66} />
      <div className="spacer"></div>
      <Btn k="primary block">Enviar avistamento →</Btn>
    </Phone>
  );
}
function A3() {
  return (
    <Phone label="A3 · Detalhe do pino" sub=" — quem pode ajudar vê aqui" note="Voluntários e ONGs veem o caso e marcam que vão atender.">
      <Status />
      <MapBox pins={[{ x: "50%", y: "30%" }]} label="" />
      <div className="sheet" style={{ borderRadius: 18, border: "2.5px solid var(--line)", marginTop: 10 }}>
        <div className="grab"></div>
        <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center" }}>
          <div className="h-ttl">Centro · R. das Flores</div>
          <span className="badge urg">urgente</span>
        </div>
        <div className="sub">2 pessoas · há 20 min · por @cidadão</div>
        <div className="chips"><Chip on>frio</Chip><Chip on>saúde</Chip></div>
        <Photo h={56}>foto enviada</Photo>
        <div className="row-2">
          <Btn k="ghost">↗ Compartilhar</Btn>
          <Btn k="primary">Vou atender 🤝</Btn>
        </div>
      </div>
    </Phone>
  );
}

/* ============================ APPROACH B ============================ */
/* Action-first: home minimalista, fluxo guiado em poucos toques */
function B1() {
  return (
    <Phone label="B1 · Início (ação)" sub=" — foco em registrar rápido" note="Tela enxuta. Um botão gigante para agir no momento em que você vê a pessoa.">
      <Status />
      <Top ttl="Moradores" right="≡" />
      <div className="spacer"></div>
      <div style={{ textAlign: "center" }}>
        <div className="big-num" style={{ fontSize: 30 }}>Viu alguém</div>
        <div className="sub" style={{ fontSize: 19 }}>precisando de ajuda?</div>
      </div>
      <Btn k="primary big block">＋ Registrar agora</Btn>
      <div style={{ textAlign: "center" }}><Tag>3 toques e pronto</Tag></div>
      <div className="spacer"></div>
      <Btn k="ghost block">🗺  Ver mapa de avistamentos</Btn>
      <div className="seclab" style={{ textAlign: "center" }}>132 pessoas ajudadas este mês</div>
    </Phone>
  );
}
function B2() {
  return (
    <Phone label="B2 · Passo 1/3 — local" sub=" — confirme o ponto" note="GPS pega o local sozinho. É só confirmar ou arrastar o pino.">
      <Status />
      <div className="topbar"><div className="ttl">Onde?</div><div className="sub">passo 1 de 3</div></div>
      <Steps n={1} of={3} />
      <MapBox pins={[{ x: "50%", y: "44%" }]} label="arraste p/ ajustar" />
      <Field lab="endereço detectado" val="Av. Paulista, 900 — próx. metrô" />
      <div className="spacer"></div>
      <Btn k="primary block">Confirmar local →</Btn>
    </Phone>
  );
}
function B3() {
  return (
    <Phone label="B3 · Passo 2/3 — situação" sub=" — o essencial" note="Quantas pessoas e o que precisam, em toques grandes. Foto e descrição são opcionais (passo 3).">
      <Status />
      <div className="topbar"><div className="ttl">A situação</div><div className="sub">passo 2 de 3</div></div>
      <Steps n={2} of={3} />
      <div className="field">
        <div className="lab">quantas pessoas?</div>
        <div className="counter" style={{ marginTop: 8 }}>
          <div className="pm">−</div><div className="big-num">3</div><div className="pm">+</div>
        </div>
      </div>
      <div className="seclab">precisam de… <Tag>toque vários</Tag></div>
      <div className="chips">
        <Chip on>frio</Chip><Chip>comida</Chip><Chip on>saúde</Chip><Chip>água</Chip><Chip>criança</Chip><Chip>animal</Chip>
      </div>
      <div className="spacer"></div>
      <div className="row-2">
        <Btn k="ghost">Pular foto</Btn>
        <Btn k="primary">Próximo →</Btn>
      </div>
    </Phone>
  );
}

/* ============================ APPROACH C ============================ */
/* Feed/community: lista de casos com status, transparência e acompanhamento */
function C1() {
  return (
    <Phone label="C1 · Feed da comunidade" sub=" — casos perto de você" note="Lista de avistamentos com status (pendente / atendendo / atendido). Dá pra acompanhar e dar continuidade.">
      <Status />
      <Top ttl="Perto de você" left="≡" right="🗺" />
      <div className="filterbar">
        <span className="f on">Urgentes</span><span className="f">Recentes</span><span className="f">Perto</span><span className="f">Atendidos</span>
      </div>
      <div style={{ display: "flex", flexDirection: "column", gap: 9, overflow: "hidden" }}>
        <div className="card">
          <div className="top"><div className="h-ttl" style={{ fontSize: 20 }}>Centro · Praça da Sé</div><span className="badge urg">urgente</span></div>
          <div style={{ display: "flex", gap: 9 }}>
            <div className="thumb"></div>
            <div>
              <div className="chips"><Chip on>frio</Chip><Chip on>saúde</Chip></div>
              <div className="meta">3 pessoas · há 12 min</div>
            </div>
          </div>
        </div>
        <div className="card">
          <div className="top"><div className="h-ttl" style={{ fontSize: 20 }}>Bela Vista</div><span className="badge wait">pendente</span></div>
          <div className="meta">1 pessoa · há 1h · 2 querem ajudar</div>
        </div>
        <div className="card">
          <div className="top"><div className="h-ttl" style={{ fontSize: 20 }}>Liberdade</div><span className="badge ok">✓ atendido</span></div>
          <div className="meta">ONG Acolher passou hoje</div>
        </div>
      </div>
      <div className="fab" style={{ position: "absolute", right: 25, bottom: 22 }}>＋</div>
      <Tabbar active="feed" />
    </Phone>
  );
}
function C2() {
  return (
    <Phone label="C2 · Detalhe do caso" sub=" — histórico e ações" note="Mostra a linha do tempo: quem registrou, quem assumiu, atualizações. Transparência para ONGs e prefeitura.">
      <Status />
      <Top ttl="Praça da Sé" left="‹" right="↗" />
      <Photo h={92}>foto do local</Photo>
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center" }}>
        <span className="badge urg">urgente</span><div className="sub">3 pessoas · Centro</div>
      </div>
      <div className="chips"><Chip on>frio</Chip><Chip on>saúde</Chip><Chip>comida</Chip></div>
      <div className="seclab">linha do tempo</div>
      <div style={{ display: "flex", flexDirection: "column", gap: 6 }}>
        <Field val="• @ana registrou — 12 min" line />
        <Field val="• 2 voluntários por perto" line />
      </div>
      <div className="spacer"></div>
      <div className="row-2"><Btn k="ghost">Atualizar</Btn><Btn k="primary">Vou atender 🤝</Btn></div>
      <Tabbar active="feed" />
    </Phone>
  );
}
function C3() {
  return (
    <Phone label="C3 · Registrar (no feed)" sub=" — alimenta a lista" note="Mesmo formulário, simples. O caso entra no feed como ‘pendente’ para a rede acompanhar.">
      <Status />
      <Top ttl="Novo registro" left="‹" />
      <Field lab="local (GPS)" val="toque no mapa / use minha posição" />
      <MapBox pins={[{ x: "50%", y: "45%" }]} />
      <div className="row-2"><Field lab="pessoas" val="2 ⊝⊕" /><Photo h={44}>foto</Photo></div>
      <div className="chips"><Chip on>frio</Chip><Chip>comida</Chip><Chip>saúde</Chip><Chip>água</Chip></div>
      <Field lab="contato (opcional)" val="nome / telefone" line />
      <div className="spacer"></div>
      <Btn k="primary block">Publicar no feed →</Btn>
      <Tabbar active="new" />
    </Phone>
  );
}
function Tabbar({ active }) {
  const it = (k, ic, l) => (
    <div className={"it" + (active === k ? " on" : "")}><span className="ic" style={k === "new" ? { borderRadius: 50 } : null}>{k === "new" ? "＋" : ""}</span>{l}</div>
  );
  return <div className="tabbar">{it("feed", "", "Feed")}{it("map", "", "Mapa")}{it("new", "+", "Registrar")}{it("me", "", "Perfil")}</div>;
}

/* ----------------------------- approaches export ----------------------------- */
const APPROACHES = [
  {
    id: "mapa",
    tab: "Mapa primeiro",
    title: "Mapa primeiro",
    desc: "Abre direto num mapa vivo da cidade. Visual, ótimo para enxergar concentração de casos e agir onde está mais perto. Registrar é uma folha que sobe sobre o mapa.",
    pills: ["Melhor p/: visão geográfica", "Risco: formulário some atrás do mapa", "Toques p/ registrar: ~5"],
    screens: [A1, A2, A3],
  },
  {
    id: "acao",
    tab: "Ação rápida",
    title: "Ação rápida (urgente)",
    desc: "Home minimalista com um botão gigante. Fluxo guiado em 3 passos curtos, pensado para quem está na rua e quer registrar em segundos. O mapa fica em segundo plano.",
    pills: ["Melhor p/: velocidade na rua", "Risco: menos contexto visual", "Toques p/ registrar: ~3"],
    screens: [B1, B2, B3],
  },
  {
    id: "feed",
    tab: "Feed da comunidade",
    title: "Feed da comunidade",
    desc: "Lista de casos com status (pendente / atendendo / atendido) e linha do tempo. Foca em acompanhamento e transparência — ideal para voluntários, ONGs e prefeitura coordenarem.",
    pills: ["Melhor p/: coordenação e follow-up", "Risco: mais denso", "Toques p/ registrar: ~5"],
    screens: [C1, C2, C3],
  },
];

Object.assign(window, { APPROACHES, Phone });
