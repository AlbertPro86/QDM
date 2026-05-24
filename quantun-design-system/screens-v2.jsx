// screens-v2.jsx — Redesigned screens with improved UX

// ────────────────────────────────────────────────────────────────
// Shared: Sparkline & MiniBar
// ────────────────────────────────────────────────────────────────
const Sparkline = ({ data = [], w = 70, h = 22, color = 'var(--q-ink-3)', fill = false }) => {
  const max = Math.max(...data, 1);
  const min = Math.min(...data, 0);
  const range = max - min || 1;
  const points = data.map((v, i) => {
    const x = (i / (data.length - 1 || 1)) * w;
    const y = h - 2 - ((v - min) / range) * (h - 4);
    return `${x},${y}`;
  }).join(' ');
  const area = `0,${h} ${points} ${w},${h}`;
  return (
    <svg width={w} height={h} className="spark" viewBox={`0 0 ${w} ${h}`}>
      {fill ? <polygon points={area} fill={color} opacity={0.12}/> : null}
      <polyline points={points} fill="none" stroke={color} strokeWidth="1.4" strokeLinecap="round" strokeLinejoin="round" />
      {data.map((v, i) => i === data.length - 1 ? (
        <circle key={i} cx={(i / (data.length - 1)) * w} cy={h - 2 - ((v - min) / range) * (h - 4)} r="2" fill={color} />
      ) : null)}
    </svg>
  );
};

const Bar = ({ value, max, color = 'var(--q-ink)' }) => (
  <span style={{ display:'block', height: 4, background:'var(--q-bg-soft)', borderRadius: 2, position:'relative', overflow:'hidden' }}>
    <span style={{ position:'absolute', inset: 0, width: `${(value/max)*100}%`, background: color, borderRadius: 2 }}/>
  </span>
);

// ────────────────────────────────────────────────────────────────
// Sidebar v2 — collapsible-ish, persistent labels, search & shortcut
// ────────────────────────────────────────────────────────────────
const SidebarV2 = ({ active }) => {
  const items = [
    { i: <IconHome size={15}/>, l: "Dashboard", k:"D" },
    { i: <IconLeads size={15}/>, l: "Leads", k:"L", badge: 4 },
    { i: <IconUsers size={15}/>, l: "Clientes", k:"C" },
    { i: <IconCalc size={15}/>, l: "Cotizador", k:"Q" },
    { i: <IconDoc size={15}/>, l: "Cotizaciones", k:"Z" },
    { i: <IconChat size={15}/>, l: "Mensajes", k:"M", badge: 12 },
    { i: <IconMail size={15}/>, l: "Email Marketing", k:"E" },
    { i: <IconCheckSq size={15}/>, l: "Tareas", k:"T", badge: 1 },
  ];
  const itemsB = [
    { i: <IconCoin size={15}/>, l: "Finanzas" },
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
      <div style={{ padding: '0 14px 8px' }}>
        <div style={{ position:'relative' }}>
          <IconSearch size={13} style={{ position:'absolute', left:10, top:10, color:'var(--q-ink-4)' }}/>
          <input className="q-input q-input--with-icon" placeholder="Buscar…" style={{ paddingRight: 38 }}/>
          <span style={{ position:'absolute', right: 8, top: 8, fontFamily:'var(--q-font-mono)', fontSize: 9.5, color:'var(--q-ink-4)', padding:'2px 5px', border:'1px solid var(--q-border)', borderRadius: 3, background:'var(--q-bg)' }}>⌘ K</span>
        </div>
      </div>
      <div className="q-side-section">Operación</div>
      <nav className="q-side-nav" style={{ paddingTop: 0 }}>
        {items.map(it => (
          <div key={it.l} className={"q-side-item " + (active === it.l ? "q-side-item--active" : "")}>
            <span style={{ display:'inline-flex', width:16 }}>{it.i}</span>
            <span style={{ flex: 1 }}>{it.l}</span>
            {it.badge ? <Badge tone={active===it.l ? 'accent' : 'neutral'}>{it.badge}</Badge> : null}
            {!it.badge && it.k ? <span style={{ fontFamily:'var(--q-font-mono)', fontSize: 9.5, opacity: .5 }}>{it.k}</span> : null}
          </div>
        ))}
      </nav>
      <div className="q-side-section">Catálogo</div>
      <nav className="q-side-nav" style={{ paddingTop: 0 }}>
        {itemsB.map(it => (
          <div key={it.l} className={"q-side-item " + (active === it.l ? "q-side-item--active" : "")}>
            <span style={{ display:'inline-flex', width:16 }}>{it.i}</span>
            <span style={{ flex: 1 }}>{it.l}</span>
          </div>
        ))}
      </nav>
      <div style={{ flex: 1 }} />
      <div style={{ padding: '0 10px 10px' }}>
        <div style={{ background:'var(--q-bg-soft)', border:'1px solid var(--q-border)', borderRadius: 3, padding: 12 }}>
          <div style={{ display:'flex', alignItems:'center', gap: 8, marginBottom: 6 }}>
            <IconSparkle size={13} style={{ color:'var(--q-lima-deep)' }}/>
            <span style={{ fontSize: 11.5, fontWeight: 600 }}>Asistente IA</span>
            <Badge tone="accent">Beta</Badge>
          </div>
          <div className="q-meta" style={{ marginBottom: 8 }}>Genera cotizaciones, redacta correos y resume reuniones.</div>
          <Btn variant="primary" size="sm" style={{ width:'100%' }}>Probar ahora</Btn>
        </div>
      </div>
      <div className="q-side-foot">
        <QAvatar initials="AA" />
        <div style={{ flex: 1, minWidth: 0 }}>
          <div style={{ fontSize: 12.5, fontWeight: 500, lineHeight: 1.2 }}>Albert Anaya</div>
          <div style={{ fontSize: 10.5, color: 'var(--q-ink-4)', letterSpacing: '.04em', textTransform: 'uppercase' }}>Admin</div>
        </div>
        <button className="q-btn q-btn--ghost q-btn--icon" title="Ajustes">
          <IconCog size={13} />
        </button>
      </div>
    </aside>
  );
};

const ScreenV2 = ({ active, title, sub, headerRight, children }) => (
  <div className="q" style={{ width:'100%', height:'100%', background: 'var(--q-bg)', overflow:'hidden', borderRadius: 'inherit' }}>
    <div className="q-shell" style={{ height: '100%' }}>
      <SidebarV2 active={active} />
      <div style={{ display:'flex', flexDirection:'column', overflow:'auto' }}>
        <header className="q-top" style={{ alignItems:'flex-end' }}>
          <div>
            <h1 style={{ margin: 0, fontSize: 22, fontWeight: 600, letterSpacing: '-0.02em' }}>{title}</h1>
            {sub ? <div className="q-meta" style={{ marginTop: 3 }}>{sub}</div> : null}
          </div>
          <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
            {headerRight}
            <button className="q-btn q-btn--ghost q-btn--icon" style={{ position: 'relative' }}>
              <IconBell size={15} />
              <span style={{ position:'absolute', top:5, right:5, width:6, height:6, background:'var(--q-danger)', borderRadius:'50%' }}/>
            </button>
            <QAvatar initials="AA" size={30}/>
          </div>
        </header>
        <main style={{ padding: '24px 28px 32px', display:'flex', flexDirection:'column', gap: 18 }}>
          {children}
        </main>
      </div>
    </div>
  </div>
);

// ════════════════════════════════════════════════════════════════
//   DASHBOARD v2
// ════════════════════════════════════════════════════════════════

const DashboardV2 = () => {
  const right = (
    <>
      <div style={{ display:'inline-flex', alignItems:'center', border:'1px solid var(--q-border)', borderRadius: 3, background:'var(--q-surface)' }}>
        {['7D','30D','90D','12M'].map((p,i) => (
          <button key={p} className="q-btn q-btn--ghost" style={{ borderRadius:0, height: 30, padding:'0 10px', borderRight: i<3 ? '1px solid var(--q-border)' : 'none', background: i===1 ? 'var(--q-bg-soft)' : 'transparent', fontWeight: i===1 ? 600 : 500, color: i===1 ? 'var(--q-ink)' : 'var(--q-ink-3)' }}>{p}</button>
        ))}
      </div>
      <Btn variant="secondary" icon={<IconCoin size={13}/>}>Finanzas</Btn>
      <Btn variant="primary" icon={<IconPlus size={13}/>}>Nuevo cliente</Btn>
    </>
  );

  return (
    <ScreenV2 active="Dashboard" title="Buen día, Albert" sub="Esto pasó en QUANTUN Digital · 20 May 2026" headerRight={right}>

      {/* ─ Action Center — qué requiere atención ───────────── */}
      <section>
        <div style={{ display:'flex', alignItems:'center', justifyContent:'space-between', marginBottom: 10 }}>
          <div style={{ display:'flex', alignItems:'center', gap: 8 }}>
            <IconBolt size={14} style={{ color:'var(--q-ink)' }}/>
            <span style={{ fontSize: 13, fontWeight: 600, letterSpacing:'-0.005em' }}>Requieren tu atención</span>
            <Badge tone="ink">9</Badge>
          </div>
          <Btn variant="ghost" size="sm">Ver bandeja →</Btn>
        </div>

        <div style={{ display:'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: 10 }}>
          {[
            {tone:'danger', icon:<IconCoin size={14}/>, count:'1', label:'En mora', desc:'CEICAR SAS · $ 200.000', cta:'Cobrar'},
            {tone:'warning', icon:<IconBell size={14}/>, count:'5', label:'Renovaciones · 30d', desc:'$ 1.247.300 por confirmar', cta:'Revisar'},
            {tone:'accent', icon:<IconLeads size={14}/>, count:'3', label:'Leads sin contactar', desc:'> 24h en pipeline', cta:'Contactar'},
            {tone:'neutral', icon:<IconCheckSq size={14}/>, count:'0', label:'Tareas vencidas', desc:'Todo al día ✓', cta:'Ver tareas'},
          ].map((c,i)=>(
            <div key={i} className="q-card q-card--hover" style={{ padding: 14, cursor:'pointer', display:'flex', flexDirection:'column', gap: 10 }}>
              <div style={{ display:'flex', alignItems:'center', justifyContent:'space-between' }}>
                <span style={{ width:24, height:24, display:'inline-flex', alignItems:'center', justifyContent:'center', borderRadius: 3, background: c.tone==='danger' ? 'var(--q-danger-soft)' : c.tone==='warning' ? 'var(--q-warning-soft)' : c.tone==='accent' ? 'var(--q-lima-soft)' : 'var(--q-bg-soft)', color: c.tone==='danger' ? 'var(--q-danger-ink)' : c.tone==='warning' ? 'var(--q-warning-ink)' : c.tone==='accent' ? 'var(--q-lima-deep)' : 'var(--q-ink-3)' }}>{c.icon}</span>
                <span className="q-mono q-tab" style={{ fontSize: 24, fontWeight: 500, letterSpacing:'-0.02em' }}>{c.count}</span>
              </div>
              <div>
                <div style={{ fontSize: 12.5, fontWeight: 500 }}>{c.label}</div>
                <div className="q-meta" style={{ marginTop: 1 }}>{c.desc}</div>
              </div>
              <div style={{ display:'flex', justifyContent:'space-between', alignItems:'center', borderTop: '1px solid var(--q-border-subtle)', paddingTop: 8, marginTop: 'auto' }}>
                <span style={{ fontSize: 11.5, fontWeight: 500, color: c.tone==='danger' ? 'var(--q-danger)' : 'var(--q-ink-2)' }}>{c.cta}</span>
                <IconChevR size={12} style={{ color: 'var(--q-ink-4)' }}/>
              </div>
            </div>
          ))}
        </div>
      </section>

      {/* ─ Performance KPIs con sparklines ─────────────────── */}
      <section>
        <div style={{ display:'flex', alignItems:'center', justifyContent:'space-between', marginBottom: 10 }}>
          <span style={{ fontSize: 13, fontWeight: 600 }}>Rendimiento · últimos 30 días</span>
          <Btn variant="ghost" size="sm" icon={<IconExport size={11}/>}>Exportar</Btn>
        </div>
        <div style={{ display:'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: 10 }}>
          {[
            { l:'MRR · Recurrente', v:'$ 8.620.900', d:'+ 4,2%', tone:'success', data:[7.9,8.0,8.1,8.0,8.2,8.3,8.4,8.5,8.6,8.6], spark:'var(--q-success)' },
            { l:'Ingresos del mes', v:'$ 0', d:'– 100%', tone:'danger', data:[5,3,4,2,1,0,0,0], spark:'var(--q-danger)' },
            { l:'Tasa conversión', v:'0%', d:'0 ganados', tone:'neutral', data:[3,2,4,3,2,1,1,0], spark:'var(--q-ink-3)' },
            { l:'Clientes activos', v:'20', d:'+ 2 nuevos', tone:'accent', data:[16,17,17,18,19,19,20,20], spark:'var(--q-lima-deep)' },
          ].map((k,i)=>(
            <div key={i} className="q-card" style={{ padding: 14 }}>
              <div style={{ display:'flex', justifyContent:'space-between' }}>
                <span style={{ fontSize: 10.5, fontWeight: 600, color: 'var(--q-ink-4)', letterSpacing: '.08em', textTransform: 'uppercase' }}>{k.l}</span>
                <Sparkline data={k.data} color={k.spark} fill/>
              </div>
              <div className="q-mono q-tab" style={{ fontSize: 26, fontWeight: 500, letterSpacing: '-0.02em', marginTop: 6 }}>{k.v}</div>
              <div style={{ marginTop: 4 }}><Badge tone={k.tone}>{k.d}</Badge></div>
            </div>
          ))}
        </div>
      </section>

      {/* ─ Pipeline funnel + Activity feed ─────────────────── */}
      <section style={{ display:'grid', gridTemplateColumns: '1.4fr 1fr', gap: 14 }}>
        <div className="q-card">
          <div className="q-card-head">
            <div>
              <div style={{ fontSize: 13.5, fontWeight: 600 }}>Pipeline de Leads · Mayo 2026</div>
              <div className="q-meta">7 leads activos · proyección $ 1.4M</div>
            </div>
            <div style={{ display:'flex', gap: 6 }}>
              <Btn variant="ghost" size="sm" icon={<IconFilter size={11}/>}>Filtros</Btn>
              <Btn variant="secondary" size="sm">Vista Kanban</Btn>
            </div>
          </div>
          {/* Funnel rows */}
          <div style={{ padding: 16 }}>
            {[
              ['Nuevos',       4, 4, 'var(--q-info)',    '$ 380K'],
              ['Contactados',  3, 4, 'var(--q-warning)', '$ 290K'],
              ['Cotización',   2, 4, 'var(--q-ink-3)',   '$ 240K'],
              ['Negociación',  1, 4, 'var(--q-lima-deep)','$ 90K'],
              ['Ganados',      0, 4, 'var(--q-success)', '$ 0'],
              ['Perdidos',     0, 4, 'var(--q-danger)',  '$ 0'],
            ].map(([n,c,m,col,money],i)=>(
              <div key={n} style={{ display:'grid', gridTemplateColumns: '120px 1fr 60px 70px', gap: 14, alignItems:'center', padding: '10px 0', borderBottom: i<5 ? '1px solid var(--q-border-subtle)' : 'none' }}>
                <span style={{ display:'inline-flex', alignItems:'center', gap: 8, fontSize: 12.5, fontWeight: 500 }}>
                  <span className="q-dot" style={{ background: col }}/>{n}
                </span>
                <Bar value={c} max={m} color={col}/>
                <span className="q-mono q-tab" style={{ fontSize: 12.5, color:'var(--q-ink)', fontWeight: 500 }}>{c} <span style={{ color:'var(--q-ink-4)' }}>· {Math.round((c/m)*100)}%</span></span>
                <span className="q-mono q-tab" style={{ fontSize: 12, color:'var(--q-ink-3)', textAlign:'right' }}>{money}</span>
              </div>
            ))}
          </div>
        </div>

        <div className="q-card">
          <div className="q-card-head">
            <div>
              <div style={{ fontSize: 13.5, fontWeight: 600 }}>Actividad reciente</div>
              <div className="q-meta">Hoy · Equipo de 4 personas</div>
            </div>
            <Btn variant="ghost" size="sm">Todo →</Btn>
          </div>
          <div style={{ padding: '4px 4px 8px' }}>
            {[
              ['ES','Edha Silva','firmó cotización','ALGAMA · $ 338K', '14:22', 'success'],
              ['AA','Albert Anaya','agregó nota a','LOGTING S&S','13:51', 'neutral'],
              ['MO','Marisol Otero','nuevo lead vía WhatsApp','Clínica dental','13:08', 'accent'],
              ['CA','Camila R.','envió correo a','CEICAR SAS','12:40', 'info'],
              ['AA','Albert Anaya','renovó','DOCTORA Luz Torralvo','11:15', 'success'],
              ['LP','Leidy Pacheco','solicitó cambio en','Hosting 25Gb','10:02', 'warning'],
            ].map((r,i)=>(
              <div key={i} style={{ display:'flex', alignItems:'flex-start', gap: 10, padding: '8px 12px', borderRadius: 3, position:'relative' }}>
                <QAvatar initials={r[0]} soft size={26}/>
                <div style={{ flex: 1, minWidth: 0 }}>
                  <div style={{ fontSize: 12.5, lineHeight: 1.4 }}>
                    <span style={{ fontWeight: 500 }}>{r[1]}</span>
                    <span style={{ color:'var(--q-ink-4)' }}> {r[2]} </span>
                    <span style={{ fontWeight: 500 }}>{r[3]}</span>
                  </div>
                  <div className="q-meta" style={{ marginTop: 1 }}>hace {r[4]}</div>
                </div>
                <span className="q-dot" style={{ background: r[5]==='success' ? 'var(--q-success)' : r[5]==='warning' ? 'var(--q-warning)' : r[5]==='accent' ? 'var(--q-lima)' : 'var(--q-ink-5)', marginTop: 8 }}/>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* ─ MRR ink highlight + Tareas ───────────────────────── */}
      <section style={{ display:'grid', gridTemplateColumns: '1.4fr 1fr', gap: 14 }}>
        <div className="q-card q-card--ink" style={{ padding: 20, display:'grid', gridTemplateColumns: '1.5fr 1fr 1fr auto', gap: 24, alignItems:'center' }}>
          <div>
            <div style={{ fontSize: 10.5, color:'rgba(250,250,247,.55)', letterSpacing:'.08em', textTransform:'uppercase', fontWeight: 600 }}>MRR · Servicios recurrentes</div>
            <div className="q-mono q-tab" style={{ fontSize: 38, fontWeight: 500, letterSpacing:'-0.02em', marginTop: 4 }}>$ 8.620.900</div>
            <div style={{ fontSize: 12, color:'rgba(250,250,247,.65)', marginTop: 2 }}>20 clientes activos · proyección mensual</div>
          </div>
          <div>
            <div style={{ fontSize: 10.5, color:'rgba(250,250,247,.55)', letterSpacing:'.08em', textTransform:'uppercase', fontWeight: 600 }}>Próx. 30 días</div>
            <div className="q-mono q-tab" style={{ fontSize: 22, fontWeight: 500, marginTop: 6 }}>$ 1.247.300</div>
            <div style={{ fontSize: 11.5, color:'rgba(250,250,247,.65)', marginTop: 2 }}>5 renovaciones</div>
          </div>
          <div>
            <div style={{ fontSize: 10.5, color:'rgba(250,250,247,.55)', letterSpacing:'.08em', textTransform:'uppercase', fontWeight: 600 }}>Mora</div>
            <div className="q-mono q-tab" style={{ fontSize: 22, fontWeight: 500, marginTop: 6 }}>$ 200.000</div>
            <div style={{ fontSize: 11.5, color:'rgba(250,250,247,.65)', marginTop: 2 }}>1 cliente</div>
          </div>
          <button className="q-btn q-btn--accent">Enviar resumen</button>
        </div>

        <div className="q-card">
          <div className="q-card-head">
            <div>
              <div style={{ fontSize: 13.5, fontWeight: 600 }}>Mis tareas de hoy</div>
              <div className="q-meta">3 abiertas · 1 vence en 2h</div>
            </div>
            <Btn variant="ghost" size="sm" icon={<IconPlus size={11}/>}>Añadir</Btn>
          </div>
          <div style={{ padding: 4 }}>
            {[
              [true,'Llamar a CEICAR SAS · cobro pendiente','Hoy 16:00','danger'],
              [false,'Crear formulario PQRS','28 May','warning'],
              [false,'Revisar cotización de Café Sereno','29 May','neutral'],
              [false,'Reunión equipo · planeación Junio','30 May','neutral'],
            ].map(([d,t,when,tone],i)=>(
              <label key={i} style={{ display:'flex', alignItems:'center', gap: 10, padding: 10, borderBottom: i<3 ? '1px solid var(--q-border-subtle)' : 'none', cursor:'pointer' }}>
                <input type="checkbox" defaultChecked={d}/>
                <span style={{ flex: 1, fontSize: 12.5, textDecoration: d ? 'line-through' : 'none', color: d ? 'var(--q-ink-4)' : 'var(--q-ink-2)' }}>{t}</span>
                <Badge tone={tone}>{when}</Badge>
              </label>
            ))}
          </div>
        </div>
      </section>

    </ScreenV2>
  );
};

// ════════════════════════════════════════════════════════════════
//   SERVICIOS v2
// ════════════════════════════════════════════════════════════════

const MarginDot = ({ margin }) => (
  <span style={{ display:'inline-flex', alignItems:'center', gap: 4 }}>
    <span className="q-dot" style={{ background: margin > 30 ? 'var(--q-success)' : margin > 10 ? 'var(--q-warning)' : margin < 0 ? 'var(--q-danger)' : 'var(--q-ink-5)' }}/>
    <span className="q-mono q-tab" style={{ fontSize: 11, color: margin < 0 ? 'var(--q-danger)' : 'var(--q-ink-3)' }}>{margin > 0 ? '+' : ''}{margin}%</span>
  </span>
);

const ServiceCardV2 = ({ title, desc, freq, variants, icon, used }) => (
  <div className="q-card q-card--hover" style={{ display:'flex', flexDirection:'column' }}>
    <div className="q-card-head" style={{ alignItems:'flex-start' }}>
      <div style={{ minWidth: 0, display:'flex', gap: 10, alignItems:'flex-start' }}>
        <span style={{ width: 32, height: 32, display:'inline-flex', alignItems:'center', justifyContent:'center', borderRadius: 3, background:'var(--q-bg-soft)', color:'var(--q-ink-2)', flexShrink: 0 }}>{icon}</span>
        <div style={{ minWidth: 0 }}>
          <div style={{ display:'flex', alignItems:'center', gap: 6, flexWrap:'wrap' }}>
            <div style={{ fontSize: 14, fontWeight: 600 }}>{title}</div>
            <Badge tone="neutral">{freq}</Badge>
            {used ? <Badge tone="accent">★ {used}</Badge> : null}
          </div>
          <div className="q-meta" style={{ marginTop: 2 }}>{desc}</div>
        </div>
      </div>
      <Btn variant="ghost" size="sm" icon={<IconCog size={12}/>}/>
    </div>
    <div style={{ padding: '0 16px 12px', flex: 1 }}>
      <div style={{ display:'flex', justifyContent:'space-between', alignItems:'center', padding:'8px 0', borderBottom:'1px solid var(--q-border-subtle)' }}>
        <span className="q-meta">Variantes · <span className="q-mono q-tab" style={{ color:'var(--q-ink)' }}>{variants.length}</span></span>
        <button className="q-btn q-btn--ghost q-btn--sm"><IconPlus size={11}/> Agregar variante</button>
      </div>
      {variants.map((v,i) => (
        <div key={i} style={{ display:'grid', gridTemplateColumns: '1fr auto auto auto', gap: 10, alignItems:'center', padding: '8px 0', borderBottom: i<variants.length-1 ? '1px solid var(--q-border-subtle)' : 'none' }}>
          <div style={{ minWidth: 0 }}>
            <div style={{ fontSize: 12.5, fontWeight: 500 }}>{v.name}</div>
            <div className="q-meta">{v.clients || 0} clientes</div>
          </div>
          <MarginDot margin={v.margin}/>
          <span className="q-mono q-tab" style={{ fontSize: 13, fontWeight: 500, textAlign:'right', minWidth: 76 }}>$ {v.price}</span>
          <div style={{ display:'flex', gap: 2 }}>
            <Btn variant="ghost" size="sm" icon={<IconEdit size={11}/>}/>
            <Btn variant="ghost" size="sm" icon={<IconCopy size={11}/>}/>
          </div>
        </div>
      ))}
    </div>
    <div style={{ borderTop: '1px solid var(--q-border-subtle)', padding: '10px 16px', display:'flex', justifyContent:'space-between', alignItems:'center', background:'var(--q-bg-soft)' }}>
      <span className="q-meta">Total facturable mensual</span>
      <span className="q-mono q-tab" style={{ fontSize: 13, fontWeight: 600 }}>$ {variants.reduce((s,v)=> s + (v.clients||0) * parseInt(v.price.replace(/\./g,'')), 0).toLocaleString('es-CO')}</span>
    </div>
  </div>
);

const ServiciosV2 = () => {
  const headerRight = (
    <>
      <Btn variant="secondary" icon={<IconLayers size={13}/>}>Crear paquete</Btn>
      <Btn variant="primary" icon={<IconPlus size={13}/>}>Nuevo servicio</Btn>
    </>
  );
  return (
    <ScreenV2 active="Servicios" title="Catálogo de Servicios" sub="6 servicios · 20 variantes · 16 con margen positivo" headerRight={headerRight}>
      {/* KPIs compactas */}
      <div className="q-kpi-grid" style={{ display:'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: 10 }}>
        <KPI label="Total servicios" value="6"  sub="En catálogo" icon={<IconBox size={13}/>}/>
        <KPI label="Sub-servicios"   value="20" sub="6 con variantes" icon={<IconLayers size={13}/>}/>
        <KPI label="Margen positivo" value="16" sub="80% del catálogo" delta="Saludable" deltaTone="success" icon={<IconSparkle size={13}/>}/>
        <KPI label="Con enlace pago"   value="0" sub="Conecta tu pasarela" delta="Acción" deltaTone="warning" icon={<IconLink size={13}/>}/>
      </div>

      {/* Toolbar: búsqueda, filtros, vista, ordenamiento */}
      <div style={{ display:'flex', alignItems:'center', justifyContent:'space-between', gap: 12, flexWrap:'wrap' }}>
        <div style={{ display:'flex', alignItems:'center', gap: 8, flexWrap:'wrap' }}>
          <div style={{ position:'relative', width: 280 }}>
            <IconSearch size={13} style={{ position:'absolute', left:10, top:10, color:'var(--q-ink-4)' }}/>
            <input className="q-input q-input--with-icon" placeholder="Buscar servicio, variante o cliente…"/>
          </div>
          {/* Active filter chips */}
          <Badge tone="neutral" dot>Anual <IconX size={10} style={{ marginLeft: 4, cursor:'pointer' }}/></Badge>
          <Badge tone="neutral" dot>Activos <IconX size={10} style={{ marginLeft: 4, cursor:'pointer' }}/></Badge>
          <Btn variant="ghost" size="sm" icon={<IconPlus size={11}/>}>Filtro</Btn>
        </div>
        <div style={{ display:'flex', alignItems:'center', gap: 6 }}>
          <Btn variant="secondary" size="sm" icon={<IconUpDown size={11}/>}>Margen ↓</Btn>
          <div style={{ display:'inline-flex', border:'1px solid var(--q-border)', borderRadius: 3, overflow:'hidden' }}>
            <button className="q-btn q-btn--ghost q-btn--sm" style={{ borderRadius: 0, borderRight:'1px solid var(--q-border)', background:'var(--q-bg-soft)' }}><IconLayers size={11}/></button>
            <button className="q-btn q-btn--ghost q-btn--sm" style={{ borderRadius: 0, borderRight:'1px solid var(--q-border)' }}>=</button>
            <button className="q-btn q-btn--ghost q-btn--sm" style={{ borderRadius: 0 }}>≡</button>
          </div>
        </div>
      </div>

      {/* Grid de servicios */}
      <div style={{ display:'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 14 }}>
        <ServiceCardV2 title="Dominios" freq="Anual" icon={<IconLink size={16}/>} used="6"
          desc="Registro y renovación de dominios .com, .co, .org y otros TLDs."
          variants={[
            {name:'.com', price:'79.900', margin: 62, clients: 14},
            {name:'.co',  price:'180.000', margin: 48, clients: 4},
            {name:'.org', price:'79.900', margin: 60, clients: 1},
            {name:'.com.co', price:'180.000', margin: 50, clients: 2},
          ]}/>
        <ServiceCardV2 title="Hosting" freq="Anual" icon={<IconBox size={16}/>} used="5"
          desc="Planes de alojamiento web con capacidades diferenciadas."
          variants={[
            {name:'DD 1 Gigas', price:'180.000', margin: 35, clients: 8},
            {name:'DD 5 Gigas', price:'210.000', margin: 38, clients: 5},
            {name:'DD 25 Gigas', price:'350.000', margin: 42, clients: 3},
            {name:'MW Soluciones Enterprise', price:'579.000', margin: 28, clients: 2},
            {name:'Rentar Inmob. Enterprise', price:'1.100.000', margin: 22, clients: 1},
          ]}/>
        <ServiceCardV2 title="Correos" freq="Anual" icon={<IconMail size={16}/>}
          desc="Correos corporativos y plataformas digitales empresariales."
          variants={[
            {name:'Plan Pymes (5 user)', price:'79.000', margin: 55, clients: 4},
            {name:'Plan Pymes (1 user)', price:'79.000', margin: 30, clients: 2},
          ]}/>
        <ServiceCardV2 title="Actualizaciones Web" freq="Mensual" icon={<IconEdit size={16}/>}
          desc="Multimedia, páginas, categorías e integraciones recurrentes."
          variants={[
            {name:'Web Básica', price:'48.000', margin: 18, clients: 6},
            {name:'Ecommerce', price:'79.000', margin: 22, clients: 3},
            {name:'Landing pages', price:'27.000', margin: -8, clients: 4},
          ]}/>
        <ServiceCardV2 title="Plataformas" freq="Anual" icon={<IconLayers size={16}/>}
          desc="Sistemas de eventos, inmobiliaria, inventarios y a medida."
          variants={[
            {name:'Sistema de eventos', price:'0', margin: 0, clients: 0},
            {name:'Sistema Inmobiliario', price:'0', margin: 0, clients: 0},
            {name:'Sistema Inventarios', price:'0', margin: 0, clients: 0},
          ]}/>
        <ServiceCardV2 title="Otros" freq="Pago único" icon={<IconSparkle size={16}/>}
          desc="Branding, marketing, diseño gráfico e impresos."
          variants={[
            {name:'Diseño para impresos', price:'150.000', margin: 60, clients: 2},
            {name:'IVC Imagen Corporativa', price:'550.000', margin: 65, clients: 1},
            {name:'Carrusel Instagram', price:'100.000', margin: 70, clients: 3},
          ]}/>
      </div>
    </ScreenV2>
  );
};

// ════════════════════════════════════════════════════════════════
//   CLIENTES v2
// ════════════════════════════════════════════════════════════════

const ClientesV2 = () => {
  const [selected, setSelected] = React.useState([0,2]);
  const headerRight = (
    <>
      <Btn variant="secondary" icon={<IconExport size={13}/>}>Exportar</Btn>
      <Btn variant="primary" icon={<IconPlus size={13}/>}>Nuevo cliente</Btn>
    </>
  );

  const rows = [
    {brand:'ALGAMA Asociados S.A.S', nit:'9012744711', contact:'Edha Silva Rugeles', services:[['Dominios','.com'],['Correos','Pymes (5u)'],['Hosting','1Gb']], renew:'7 Ago 2026', days: 79, money:'338.900', wsp:'3176159404', state:'success', stateLabel:'Activo'},
    {brand:'ARECICLAR Sincelejo', nit:'SIN NIT', contact:'Daniel Areciclar', services:[['Dominios','.com'],['Hosting','1Gb']], renew:'11 Dic 2026', days: 205, money:'259.900', wsp:'3022670830', state:'success', stateLabel:'Activo'},
    {brand:'ARRIENDA BIEN SAS', nit:'9000801155', contact:'Valentina Combat', services:[['Dominios','.com']], renew:'24 Mar 2027', days: 308, money:'168.900', wsp:'3045425938', state:'success', stateLabel:'Activo'},
    {brand:'CEICAR SAS', nit:'SIN NIT', contact:'Darlys Altamiranda', services:[['Correos','Pymes (5u)'],['Dominios','.co']], renew:'2 Jun 2026', days: 13, money:'259.000', wsp:'3145979983', state:'warning', stateLabel:'Por cobrar'},
    {brand:'CLÍNICA CATTH', nit:'SIN NIT', contact:'Leidy Pacheco Borja', services:[['Correos','Plan 10u'],['Dominios','.com'],['Hosting','25Gb']], renew:'9 Oct 2026', days: 142, money:'1.434.900', wsp:'31345101132', state:'success', stateLabel:'Activo'},
    {brand:'DOCTORA Luz Torralvo', nit:'SIN NIT', contact:'Luz Torralvo', services:[['Dominios','.com'],['Hosting','3Gb']], renew:'14 Oct 2026', days: 147, money:'259.900', wsp:'33649899489', state:'danger', stateLabel:'En mora'},
    {brand:'DOUBLE ONE GK', nit:'SIN NIT', contact:'Juan Mendoza Anaya', services:[['Dominios','.com'],['Hosting','1Gb']], renew:'29 Jul 2026', days: 70, money:'229.900', wsp:'3106990380', state:'success', stateLabel:'Activo'},
    {brand:'ECOASEO', nit:'SIN NIT', contact:'Ana Milena González', services:[['Dominios','.co'],['Hosting','5Gb']], renew:'10 Ago 2026', days: 82, money:'390.000', wsp:'3004953174', state:'success', stateLabel:'Activo'},
  ];

  const toggle = (i) => setSelected(s => s.includes(i) ? s.filter(x => x!==i) : [...s, i]);

  return (
    <ScreenV2 active="Clientes" title="Clientes" sub="20 activos · $ 8.620.900 MRR · 1 en mora" headerRight={headerRight}>

      {/* Saved views (tabs) */}
      <div className="q-tabs">
        <button className="q-tab-item q-tab-item--active">Todos <span className="q-mono q-tab" style={{ color:'var(--q-ink-4)' }}>20</span></button>
        <button className="q-tab-item">Activos <Badge tone="success">19</Badge></button>
        <button className="q-tab-item">Renovaciones · 30d <Badge tone="warning">5</Badge></button>
        <button className="q-tab-item">En mora <Badge tone="danger">1</Badge></button>
        <button className="q-tab-item">Pago único <span className="q-mono q-tab" style={{ color:'var(--q-ink-4)' }}>4</span></button>
        <button className="q-tab-item q-muted">+ Crear vista</button>
      </div>

      {/* Filter chip bar + search */}
      <div className="q-card" style={{ padding: 12, display:'flex', alignItems:'center', gap: 10, flexWrap:'wrap' }}>
        <div style={{ position:'relative', flex: 1, minWidth: 240 }}>
          <IconSearch size={13} style={{ position:'absolute', left:10, top:10, color:'var(--q-ink-4)' }}/>
          <input className="q-input q-input--with-icon" placeholder="Buscar por marca, contacto, NIT o WhatsApp…"/>
        </div>
        <Badge tone="neutral" dot>Servicio: Dominios <IconX size={10} style={{ marginLeft: 4, cursor:'pointer' }}/></Badge>
        <Badge tone="neutral" dot>Frecuencia: Anual <IconX size={10} style={{ marginLeft: 4, cursor:'pointer' }}/></Badge>
        <Btn variant="ghost" size="sm" icon={<IconPlus size={11}/>}>Filtro</Btn>
        <Btn variant="ghost" size="sm" icon={<IconX size={11}/>}>Limpiar</Btn>
      </div>

      {/* Bulk action bar (visible when selected.length > 0) */}
      {selected.length > 0 ? (
        <div className="q-card q-card--ink" style={{ padding: '10px 14px', display:'flex', alignItems:'center', gap: 12 }}>
          <Badge tone="accent">{selected.length}</Badge>
          <span style={{ fontSize: 12.5 }}>{selected.length} clientes seleccionados</span>
          <span style={{ flex: 1 }}/>
          <Btn variant="ghost" size="sm" icon={<IconMail size={11}/>} style={{ color: 'var(--q-bg)' }}>Email</Btn>
          <Btn variant="ghost" size="sm" icon={<IconWhatsApp size={11}/>} style={{ color: 'var(--q-bg)' }}>WhatsApp</Btn>
          <Btn variant="ghost" size="sm" icon={<IconExport size={11}/>} style={{ color: 'var(--q-bg)' }}>Exportar</Btn>
          <span style={{ width: 1, height: 18, background:'rgba(250,250,247,.15)' }}/>
          <Btn variant="accent" size="sm" icon={<IconCheck size={11}/>}>Marcar pagado</Btn>
          <button className="q-btn q-btn--ghost q-btn--icon" onClick={()=> setSelected([])} style={{ color: 'var(--q-bg)' }}><IconX size={12}/></button>
        </div>
      ) : null}

      {/* Main grid: tabla + drawer */}
      <div style={{ display:'grid', gridTemplateColumns: '1fr 340px', gap: 14, alignItems:'flex-start' }}>
        <div className="q-card" style={{ overflow:'hidden' }}>
          <div style={{ overflow:'auto', maxHeight: 580 }}>
          <table className="q-table">
            <thead>
              <tr>
                <th style={{ width: 28 }}><input type="checkbox" checked={selected.length === rows.length} onChange={()=> setSelected(selected.length===rows.length ? [] : rows.map((_,i)=>i))}/></th>
                <th>Marca / Empresa</th>
                <th>Servicios</th>
                <th>Salud · Renovación</th>
                <th style={{ textAlign:'right' }}>Ingresos</th>
                <th style={{ width: 100 }}>Contacto rápido</th>
                <th style={{ width: 32 }}></th>
              </tr>
            </thead>
            <tbody>
              {rows.map((r,i) => {
                const isSelected = selected.includes(i);
                const isOpen = i === 0;
                const healthColor = r.state==='danger' ? 'var(--q-danger)' : r.days < 30 ? 'var(--q-warning)' : 'var(--q-success)';
                return (
                  <tr key={i} style={{ background: isOpen ? 'var(--q-bg-soft)' : 'transparent' }}>
                    <td><input type="checkbox" checked={isSelected} onChange={()=> toggle(i)}/></td>
                    <td>
                      <div style={{ display:'flex', alignItems:'center', gap: 10 }}>
                        <QAvatar initials={r.brand.split(' ').map(w=>w[0]).slice(0,2).join('').toUpperCase()} soft size={28}/>
                        <div>
                          <div style={{ fontSize: 12.5, fontWeight: 500, letterSpacing:'-0.005em' }}>{r.brand}</div>
                          <div style={{ fontSize: 11, color:'var(--q-ink-4)' }}>
                            <span className="q-mono">{r.nit}</span> · {r.contact}
                          </div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div style={{ display:'flex', gap: 4, flexWrap:'wrap', maxWidth: 220 }}>
                        {r.services.map(([k,v]) => (
                          <span key={k+v} className="q-badge q-badge--neutral" style={{ gap: 2 }}>
                            <span style={{ fontWeight: 500 }}>{k}</span>
                            <span style={{ color:'var(--q-ink-4)' }}>·</span>
                            <span style={{ color:'var(--q-ink-4)' }}>{v}</span>
                          </span>
                        ))}
                      </div>
                    </td>
                    <td>
                      <div style={{ display:'flex', alignItems:'center', gap: 8 }}>
                        <span style={{ width: 3, height: 24, borderRadius: 2, background: healthColor }}/>
                        <div>
                          <div style={{ fontSize: 12.5, fontWeight: 500 }}>{r.renew}</div>
                          <div className="q-meta" style={{ marginTop: 1 }}>{r.days < 30 ? `Vence en ${r.days} días` : `${r.days} días restantes`}</div>
                        </div>
                      </div>
                    </td>
                    <td className="q-mono q-tab" style={{ textAlign:'right', fontWeight: 500 }}>$ {r.money}</td>
                    <td>
                      <div style={{ display:'flex', gap: 2 }}>
                        <Btn variant="ghost" size="sm" icon={<IconWhatsApp size={12}/>} />
                        <Btn variant="ghost" size="sm" icon={<IconMail size={12}/>} />
                        <Btn variant="ghost" size="sm" icon={<IconChat size={12}/>} />
                      </div>
                    </td>
                    <td><Btn variant="ghost" size="sm" icon={<IconChevR size={12}/>}/></td>
                  </tr>
                );
              })}
            </tbody>
          </table>
          </div>
          <div style={{ padding: '10px 14px', display:'flex', justifyContent:'space-between', alignItems:'center', borderTop: '1px solid var(--q-border-subtle)' }}>
            <span className="q-meta">Mostrando 1–8 de 20 clientes</span>
            <div style={{ display:'flex', gap: 6 }}>
              <Btn variant="secondary" size="sm" icon={<IconChevL size={12}/>}/>
              <Btn variant="secondary" size="sm">1</Btn>
              <Btn variant="ghost" size="sm">2</Btn>
              <Btn variant="ghost" size="sm">3</Btn>
              <Btn variant="secondary" size="sm" icon={<IconChevR size={12}/>}/>
            </div>
          </div>
        </div>

        {/* Detail drawer */}
        <aside className="q-card" style={{ position:'sticky', top: 20 }}>
          <div className="q-card-head" style={{ alignItems:'flex-start' }}>
            <div style={{ display:'flex', gap: 10, alignItems:'flex-start' }}>
              <QAvatar initials="AA" size={36}/>
              <div>
                <div style={{ fontSize: 14, fontWeight: 600, letterSpacing:'-0.01em' }}>ALGAMA Asociados S.A.S</div>
                <div className="q-meta">NIT 9012744711 · Cliente desde 2023</div>
              </div>
            </div>
            <Btn variant="ghost" size="sm" icon={<IconX size={12}/>}/>
          </div>
          <div style={{ padding: 16 }}>
            <div style={{ display:'flex', gap: 6, marginBottom: 14 }}>
              <Btn variant="primary" size="sm" icon={<IconWhatsApp size={11}/>} style={{ flex: 1 }}>WhatsApp</Btn>
              <Btn variant="secondary" size="sm" icon={<IconMail size={11}/>} style={{ flex: 1 }}>Email</Btn>
              <Btn variant="secondary" size="sm" icon={<IconCalc size={11}/>}/>
            </div>

            <div style={{ display:'grid', gridTemplateColumns: '1fr 1fr', gap: 10, marginBottom: 14 }}>
              <div style={{ padding: 10, background:'var(--q-bg-soft)', borderRadius: 3 }}>
                <div className="q-meta">Ingresos LTV</div>
                <div className="q-mono q-tab" style={{ fontSize: 16, fontWeight: 600, marginTop: 2 }}>$ 1.2M</div>
              </div>
              <div style={{ padding: 10, background:'var(--q-bg-soft)', borderRadius: 3 }}>
                <div className="q-meta">Servicios activos</div>
                <div className="q-mono q-tab" style={{ fontSize: 16, fontWeight: 600, marginTop: 2 }}>3</div>
              </div>
            </div>

            <div style={{ marginBottom: 14 }}>
              <div className="q-meta" style={{ marginBottom: 6 }}>Salud del cliente</div>
              <div style={{ display:'flex', alignItems:'center', gap: 8 }}>
                <div style={{ flex: 1, height: 6, background:'var(--q-bg-soft)', borderRadius: 3, overflow:'hidden' }}>
                  <div style={{ height:'100%', width:'82%', background:'var(--q-success)' }}/>
                </div>
                <span className="q-mono q-tab" style={{ fontSize: 12, fontWeight: 600 }}>82</span>
              </div>
              <div className="q-meta" style={{ marginTop: 4 }}>Renovaciones puntuales · sin tickets abiertos</div>
            </div>

            <div className="q-meta" style={{ marginBottom: 6 }}>Servicios activos</div>
            <div style={{ display:'flex', flexDirection:'column', gap: 6, marginBottom: 14 }}>
              {[
                ['Dominios — .com', '$ 79.900', 'Renueva 7 Ago'],
                ['Correos — Pymes 5u', '$ 79.000', 'Renueva 7 Ago'],
                ['Hosting — 1 Gigas', '$ 180.000', 'Renueva 7 Ago'],
              ].map(([n,p,d]) => (
                <div key={n} style={{ display:'flex', alignItems:'center', gap: 8, padding: 8, border:'1px solid var(--q-border-subtle)', borderRadius: 3 }}>
                  <span className="q-dot" style={{ background:'var(--q-success)' }}/>
                  <div style={{ flex: 1, minWidth: 0 }}>
                    <div style={{ fontSize: 12, fontWeight: 500 }}>{n}</div>
                    <div className="q-meta">{d}</div>
                  </div>
                  <span className="q-mono q-tab" style={{ fontSize: 12, fontWeight: 500 }}>{p}</span>
                </div>
              ))}
            </div>

            <div className="q-meta" style={{ marginBottom: 6 }}>Línea de tiempo</div>
            <div style={{ display:'flex', flexDirection:'column', gap: 8 }}>
              {[
                ['7 May 26','Renovación firmada','success'],
                ['12 Abr 26','Llamada de seguimiento','neutral'],
                ['28 Mar 26','Cotización enviada','info'],
              ].map(([d,a,t],i) => (
                <div key={i} style={{ display:'flex', gap: 8, alignItems:'flex-start' }}>
                  <span className="q-mono" style={{ fontSize: 10.5, color:'var(--q-ink-4)', width: 56, paddingTop: 2 }}>{d}</span>
                  <span className="q-dot" style={{ background: t==='success' ? 'var(--q-success)' : t==='info' ? 'var(--q-info)' : 'var(--q-ink-5)', marginTop: 6 }}/>
                  <span style={{ fontSize: 12, color:'var(--q-ink-2)' }}>{a}</span>
                </div>
              ))}
            </div>
          </div>
        </aside>
      </div>
    </ScreenV2>
  );
};

Object.assign(window, { DashboardV2, ServiciosV2, ClientesV2, Sparkline, Bar, MarginDot });
