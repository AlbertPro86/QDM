// ds-components.jsx — shared shell pieces used across screens
// (Sidebar, Topbar, KPI tile, Avatar, Logo)

const QLogo = ({ subtitle = "DIGITAL", size = 18, invert = false }) => (
  <span className="q-logo" style={{ display:'inline-flex', alignItems:'center', gap: 0 }}>
    <img
      src={window.QUANTUN_LOGO_URL}
      alt="QUANTUN Digital"
      style={{
        height: size * 1.35,
        width: 'auto',
        display: 'block',
        filter: invert ? 'invert(1)' : 'none',
      }}
    />
  </span>
);

const QAvatar = ({ initials = "AA", size = 28, soft = false }) => (
  <span className={"q-avatar " + (soft ? "q-avatar--soft" : "")} style={{ width: size, height: size, fontSize: size * 0.38 }}>
    {initials}
  </span>
);

const Btn = ({ variant = "secondary", size, icon, children, style, ...rest }) => (
  <button
    className={`q-btn q-btn--${variant}${size ? ' q-btn--' + size : ''}`}
    style={style} {...rest}
  >
    {icon}
    {children ? <span>{children}</span> : null}
  </button>
);

const Badge = ({ tone = "neutral", dot, children }) => (
  <span className={`q-badge q-badge--${tone}`}>
    {dot ? <span className="q-dot" style={{ background: 'currentColor' }} /> : null}
    {children}
  </span>
);

// Sidebar nav item + section
const SideItem = ({ icon, label, active, badge }) => (
  <div className={"q-side-item " + (active ? "q-side-item--active" : "")}>
    <span style={{ display:'inline-flex', width:16 }}>{icon}</span>
    <span style={{ flex: 1 }}>{label}</span>
    {badge ? <span style={{ fontSize: 10, opacity: .8 }}>{badge}</span> : null}
  </div>
);

const Sidebar = ({ active = "Dashboard" }) => {
  const items = [
    { i: <IconHome size={15}/>, l: "Dashboard" },
    { i: <IconLeads size={15}/>, l: "Gestión de Leads" },
    { i: <IconUsers size={15}/>, l: "Área de Clientes" },
    { i: <IconCalc size={15}/>, l: "Cotizador" },
    { i: <IconDoc size={15}/>, l: "Cotizaciones" },
    { i: <IconChat size={15}/>, l: "Mensajes" },
    { i: <IconMail size={15}/>, l: "Email Marketing" },
    { i: <IconCheckSq size={15}/>, l: "Tareas" },
  ];
  const itemsB = [
    { i: <IconCoin size={15}/>, l: "Núcleo Financiero" },
    { i: <IconTruck size={15}/>, l: "Proveedores" },
    { i: <IconLayers size={15}/>, l: "Plantillas" },
    { i: <IconBox size={15}/>, l: "Servicios" },
  ];
  return (
    <aside className="q-side">
      <div className="q-side-head">
        <img src={window.QUANTUN_LOGO_URL} alt="QUANTUN Digital" style={{ height: 22, width:'auto' }}/>
        <button className="q-btn q-btn--ghost q-btn--icon" title="Colapsar">
          <IconChevDouble size={14} />
        </button>
      </div>
      <div className="q-side-section">Operación</div>
      <nav className="q-side-nav" style={{ paddingTop: 0 }}>
        {items.map(it => <SideItem key={it.l} icon={it.i} label={it.l} active={active === it.l} />)}
      </nav>
      <div className="q-side-section">Catálogo</div>
      <nav className="q-side-nav" style={{ paddingTop: 0 }}>
        {itemsB.map(it => <SideItem key={it.l} icon={it.i} label={it.l} active={active === it.l} />)}
      </nav>
      <div style={{ flex: 1 }} />
      <nav className="q-side-nav">
        <SideItem icon={<IconCog size={15}/>} label="Configuración" active={active === "Configuración"} />
      </nav>
      <div className="q-side-foot">
        <QAvatar initials="AA" />
        <div style={{ flex: 1, minWidth: 0 }}>
          <div style={{ fontSize: 12.5, fontWeight: 500, lineHeight: 1.2 }}>Albert Anaya</div>
          <div style={{ fontSize: 10.5, color: 'var(--q-ink-4)', letterSpacing: '.04em', textTransform: 'uppercase' }}>Admin</div>
        </div>
        <button className="q-btn q-btn--ghost q-btn--icon" title="Salir">
          <IconChevR size={13} />
        </button>
      </div>
    </aside>
  );
};

const Topbar = ({ title, crumbs = [], right }) => (
  <header className="q-top">
    <div>
      <h1 style={{ margin: 0, fontSize: 20, fontWeight: 600, letterSpacing: '-0.015em' }}>{title}</h1>
      {crumbs.length ? (
        <div className="q-crumb" style={{ marginTop: 3 }}>
          {crumbs.map((c, i) => (
            <React.Fragment key={c + i}>
              <span className={i === crumbs.length - 1 ? "q-crumb-cur" : ""}>{c}</span>
              {i < crumbs.length - 1 ? <IconChevR size={11} /> : null}
            </React.Fragment>
          ))}
        </div>
      ) : null}
    </div>
    <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
      {right}
      <button className="q-btn q-btn--ghost q-btn--icon" style={{ position: 'relative' }}>
        <IconBell size={15} />
        <span style={{ position:'absolute', top:5, right:5, width:6, height:6, background:'var(--q-danger)', borderRadius:'50%' }}/>
      </button>
      <QAvatar initials="AA" size={30}/>
    </div>
  </header>
);

// KPI tile — all neutral, distinguished by icon + number
const KPI = ({ label, value, unit, sub, delta, deltaTone = "neutral", icon, accent }) => (
  <div className="q-card" style={{ padding: '14px 16px', minHeight: 110, display:'flex', flexDirection:'column', justifyContent:'space-between' }}>
    <div style={{ display:'flex', alignItems:'center', justifyContent:'space-between' }}>
      <span style={{ fontSize: 10.5, fontWeight: 600, color: 'var(--q-ink-4)', letterSpacing: '.08em', textTransform: 'uppercase' }}>{label}</span>
      <span style={{ width: 24, height: 24, display:'inline-flex', alignItems:'center', justifyContent:'center', color: accent || 'var(--q-ink-4)', background: accent ? 'transparent' : 'var(--q-bg-soft)', border:'1px solid var(--q-border-subtle)', borderRadius: 'var(--q-r-sm)' }}>
        {icon}
      </span>
    </div>
    <div style={{ marginTop: 8 }}>
      <div style={{ display: 'flex', alignItems: 'baseline', gap: 4 }}>
        <span className="q-mono q-tab" style={{ fontSize: 26, fontWeight: 500, letterSpacing: '-0.02em', color: 'var(--q-ink)' }}>{value}</span>
        {unit ? <span className="q-mono" style={{ fontSize: 11, color: 'var(--q-ink-4)' }}>{unit}</span> : null}
      </div>
      <div style={{ display:'flex', alignItems:'center', gap: 8, marginTop: 4 }}>
        {delta != null ? <Badge tone={deltaTone}>{delta}</Badge> : null}
        {sub ? <span className="q-meta">{sub}</span> : null}
      </div>
    </div>
  </div>
);

// Section header (used inside screens)
const SectionHead = ({ title, sub, right }) => (
  <div style={{ display:'flex', alignItems:'flex-end', justifyContent:'space-between', marginBottom: 12 }}>
    <div>
      <div style={{ fontSize: 14, fontWeight: 600, letterSpacing: '-0.005em' }}>{title}</div>
      {sub ? <div className="q-meta" style={{ marginTop: 2 }}>{sub}</div> : null}
    </div>
    {right}
  </div>
);

Object.assign(window, { QLogo, QAvatar, Btn, Badge, Sidebar, Topbar, KPI, SectionHead, SideItem });
