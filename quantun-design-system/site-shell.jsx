// site-shell.jsx — Docs site shell: topbar, sidebar nav, TOC

const NAV = [
  {
    group: 'Empezar',
    items: [
      { id: 'intro', label: 'Introducción', icon: <IconHome size={13}/> },
      { id: 'principles', label: 'Principios', icon: <IconSparkle size={13}/> },
    ],
  },
  {
    group: 'Marca',
    items: [
      { id: 'logo', label: 'Logo', icon: <IconBox size={13}/> },
    ],
  },
  {
    group: 'Fundamentos',
    items: [
      { id: 'color', label: 'Color', icon: <IconLayers size={13}/> },
      { id: 'type',  label: 'Tipografía', icon: <IconDoc size={13}/> },
      { id: 'spacing', label: 'Espaciado · Radius · Sombra', icon: <IconCog size={13}/> },
    ],
  },
  {
    group: 'Componentes',
    items: [
      { id: 'buttons',  label: 'Botones', icon: <IconBolt size={13}/> },
      { id: 'forms',    label: 'Formularios', icon: <IconEdit size={13}/> },
      { id: 'badges',   label: 'Badges & Avatares', icon: <IconUsers size={13}/> },
      { id: 'cards',    label: 'Cards', icon: <IconBox size={13}/> },
      { id: 'table',    label: 'Tablas', icon: <IconLayers size={13}/> },
      { id: 'nav',      label: 'Navegación & Modales', icon: <IconChevR size={13}/> },
    ],
  },
  {
    group: 'Pantallas',
    items: [
      { id: 'screen-dashboard', label: 'Dashboard', icon: <IconHome size={13}/> },
      { id: 'screen-servicios', label: 'Servicios', icon: <IconBox size={13}/> },
      { id: 'screen-clientes',  label: 'Clientes',  icon: <IconUsers size={13}/> },
    ],
  },
  {
    group: 'Recursos',
    items: [
      { id: 'changelog', label: 'Changelog', icon: <IconDoc size={13}/> },
      { id: 'handoff',   label: 'Handoff a Claude Code', icon: <IconCopy size={13}/> },
    ],
  },
];

// flat list of all section ids in order
const ALL_IDS = NAV.flatMap(g => g.items.map(i => i.id));

const useActiveSection = () => {
  const [active, setActive] = React.useState(ALL_IDS[0]);
  React.useEffect(() => {
    const els = ALL_IDS.map(id => document.getElementById(id)).filter(Boolean);
    const onScroll = () => {
      let cur = ALL_IDS[0];
      const offset = 120;
      els.forEach(el => {
        if (el.getBoundingClientRect().top - offset <= 0) cur = el.id;
      });
      setActive(cur);
    };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
    return () => window.removeEventListener('scroll', onScroll);
  }, []);
  return active;
};

const SiteTopbar = ({ onToggleTweaks }) => (
  <header className="site-topbar">
    <a href="#intro" className="site-logo">
      <img src={window.QUANTUN_LOGO_URL} alt="QUANTUN Digital"/>
      <span style={{ fontSize: 11, padding:'3px 7px', border:'1px solid var(--q-border)', borderRadius: 3, color:'var(--q-ink-3)', fontFamily:'var(--q-font-mono)', letterSpacing:'.04em' }}>DS · v1.0</span>
    </a>
    <span style={{ flex: 1 }}/>
    <div className="site-search">
      <IconSearch size={12}/>
      <span>Buscar en la documentación</span>
      <kbd>⌘ K</kbd>
    </div>
    <nav className="site-nav">
      <a href="#intro" className="active">Docs</a>
      <a href="#screen-dashboard">Pantallas</a>
      <a href="#handoff">Handoff</a>
    </nav>
    <span style={{ width: 1, height: 22, background: 'var(--q-border)' }}/>
    <button className="q-btn q-btn--secondary q-btn--sm" onClick={onToggleTweaks} title="Tweaks">
      <IconCog size={12}/> <span>Tweaks</span>
    </button>
    <button className="q-btn q-btn--primary q-btn--sm">Compartir</button>
  </header>
);

const SiteSidebar = () => {
  const active = useActiveSection();
  return (
    <aside className="site-side">
      {NAV.map(group => (
        <div key={group.group} className="nav-group">
          <div className="nav-group-title">{group.group}</div>
          {group.items.map(it => (
            <a key={it.id} href={`#${it.id}`} className={"nav-link " + (active === it.id ? 'active' : '')}>
              <span style={{ display:'inline-flex', width: 14 }}>{it.icon}</span>
              <span>{it.label}</span>
            </a>
          ))}
        </div>
      ))}
    </aside>
  );
};

const SiteToc = ({ items = [] }) => {
  const [active, setActive] = React.useState(items[0]?.id);
  React.useEffect(() => {
    if (!items.length) return;
    const els = items.map(i => document.getElementById(i.id)).filter(Boolean);
    const onScroll = () => {
      let cur = items[0].id;
      els.forEach(el => {
        if (el.getBoundingClientRect().top - 140 <= 0) cur = el.id;
      });
      setActive(cur);
    };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
    return () => window.removeEventListener('scroll', onScroll);
  }, [items]);
  return (
    <aside className="site-toc">
      <div className="toc-title">En esta página</div>
      <nav className="toc">
        {items.map(it => (
          <a key={it.id} href={`#${it.id}`} className={active === it.id ? 'active' : ''}>{it.label}</a>
        ))}
      </nav>
      <div style={{ marginTop: 28, padding: 14, background:'var(--q-bg-soft)', borderRadius: 3 }}>
        <div style={{ fontSize: 11.5, fontWeight: 600, marginBottom: 6 }}>¿Te quedaste con dudas?</div>
        <div className="q-meta" style={{ marginBottom: 8 }}>Este DS evoluciona contigo. Reporta inconsistencias o pide nuevos componentes.</div>
        <Btn variant="secondary" size="sm" icon={<IconChat size={11}/>} style={{ width:'100%' }}>Hablar con diseño</Btn>
      </div>
    </aside>
  );
};

const Page = ({ children }) => <div className="page">{children}</div>;

const PageSection = ({ id, eyebrow, title, lead, children, h='h1' }) => (
  <section id={id} className="page-section">
    {eyebrow ? <div className="page-eyebrow">{eyebrow}</div> : null}
    {h === 'h1' ? <h1 className="page-h1">{title}</h1> : <h2 className="page-h2">{title}</h2>}
    {lead ? <p className="page-lead">{lead}</p> : null}
    <div style={{ marginTop: 28 }}>{children}</div>
  </section>
);

const Demo = ({ title, code, children, stack, left = true }) => (
  <div className="demo">
    {title ? <div className="demo-head"><span>{title}</span></div> : null}
    <div className={"demo-body" + (left ? ' left' : '') + (stack ? ' stack' : '')}>
      {children}
    </div>
    {code ? <div className="demo-code">{code}</div> : null}
  </div>
);

const ScreenViewer = ({ url, height = 720, children }) => (
  <div className="viewer">
    <div className="viewer-frame" style={{ height }}>
      <div className="viewer-bar">
        <span className="viewer-dot"/><span className="viewer-dot"/><span className="viewer-dot"/>
        <div className="viewer-url">app.quantundigital.com{url}</div>
        <Btn variant="ghost" size="sm" icon={<IconExport size={11}/>}>Abrir</Btn>
      </div>
      <div style={{ height: `calc(100% - 42px)`, overflow:'hidden' }}>{children}</div>
    </div>
  </div>
);

Object.assign(window, { SiteTopbar, SiteSidebar, SiteToc, Page, PageSection, Demo, ScreenViewer, NAV });
