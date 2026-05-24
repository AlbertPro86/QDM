// site-pages.jsx — All documentation sections content

// ─────────────────────────────────────────────────────────────
// HERO INTRO
// ─────────────────────────────────────────────────────────────
const HeroIntro = () => (
  <section id="intro" style={{ padding: '64px 56px 32px', borderBottom: '1px solid var(--q-border-subtle)', position:'relative', overflow:'hidden' }}>
    <div style={{
      position:'absolute', inset: 0, pointerEvents:'none', opacity: .5,
      backgroundImage: 'radial-gradient(circle at 1px 1px, rgba(14,14,12,.06) 1px, transparent 0)',
      backgroundSize: '14px 14px',
    }}/>
    <div style={{ position:'relative', display:'flex', alignItems:'center', gap: 10, marginBottom: 14 }}>
      <Badge tone="accent">v 1.0</Badge>
      <span className="q-meta">Actualizado · 20 Mayo 2026</span>
      <span style={{ flex: 1 }}/>
      <Btn variant="ghost" size="sm" icon={<IconExport size={11}/>}>Exportar tokens</Btn>
      <Btn variant="ghost" size="sm" icon={<IconCopy size={11}/>}>Handoff Claude Code</Btn>
    </div>

    <div style={{ position:'relative', maxWidth: 920 }}>
      <div className="page-eyebrow">QUANTUN Digital · CRM Software</div>
      <h1 style={{ fontSize: 64, fontWeight: 600, letterSpacing: '-0.035em', lineHeight: 1.02, margin: '8px 0 0' }}>
        Diseño <span style={{ background:'var(--q-lima)', padding:'0 8px', borderRadius: 3, fontStyle:'italic', fontWeight:500 }}>fresco</span>, claro<br/>
        y minimalista para tu CRM.
      </h1>
      <p style={{ fontSize: 16, color:'var(--q-ink-3)', marginTop: 18, maxWidth: 680, lineHeight: 1.55 }}>
        Un sistema de diseño completo para QUANTUN Digital. Inspirado en HubSpot, Linear y Stripe — tipografía Inter, fondo cálido off-white, negro como CTA principal y lima sutil como acento de marca.
      </p>
      <div style={{ display:'flex', gap: 10, marginTop: 24 }}>
        <a href="#color"><Btn variant="primary" icon={<IconSparkle size={13}/>}>Explorar el sistema</Btn></a>
        <a href="#screen-dashboard"><Btn variant="secondary">Ver pantallas →</Btn></a>
      </div>
    </div>

    <div style={{ position:'relative', display:'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: 12, marginTop: 48 }}>
      {[
        ['Tipografía','Inter + JetBrains Mono'],
        ['Color principal','Negro cálido #0E0E0C'],
        ['Acento','Lima #C6F24E sutil'],
        ['Densidad','Compacta · 1px borders'],
      ].map(([k,v]) => (
        <div key={k} className="q-card" style={{ padding: 14 }}>
          <div style={{ fontSize: 10.5, fontWeight: 600, color: 'var(--q-ink-4)', letterSpacing: '.08em', textTransform: 'uppercase' }}>{k}</div>
          <div style={{ fontSize: 14, fontWeight: 500, marginTop: 4 }}>{v}</div>
        </div>
      ))}
    </div>
  </section>
);

// ─────────────────────────────────────────────────────────────
// PRINCIPIOS
// ─────────────────────────────────────────────────────────────
const PrinciplesPage = () => (
  <PageSection id="principles" eyebrow="01 · Principios" title="Cómo se comporta este sistema"
    lead="Cinco ideas que guían cada decisión visual y de UX. Cuando dudes, vuelve aquí." h="h2">
    <div style={{ display:'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: 14 }}>
      {[
        ['01','Claro siempre, oscuro nunca','Fondo cálido off-white. El negro carga jerarquía como CTA y títulos, no como ambiente.'],
        ['02','Lima sólo como acento','Aparece en chips, dots, app icon y un highlight ocasional. Nunca llena un botón o fondo grande.'],
        ['03','Bordes antes que sombras','1px de borde hace el trabajo de separar. Las sombras se reservan para popovers y modales.'],
        ['04','Datos primero, decoración después','La tipografía monoespaciada tabular en cifras. Nada decorativo que no resuelva información.'],
        ['05','Una sola pieza protagonista','Por pantalla, máximo una card ink (negra). Todo lo demás respira en neutrales.'],
        ['06','Espacio para escalar','Densidad compacta por default, escalable a medio/espacioso vía tokens. Diseñado para mucha data.'],
      ].map(([n,t,d]) => (
        <div key={n} className="q-card" style={{ padding: 18 }}>
          <div className="q-mono" style={{ fontSize: 11, color:'var(--q-ink-4)', letterSpacing:'.08em' }}>{n}</div>
          <div style={{ fontSize: 16, fontWeight: 600, marginTop: 6, letterSpacing:'-0.01em' }}>{t}</div>
          <p style={{ fontSize: 13, color:'var(--q-ink-3)', marginTop: 4 }}>{d}</p>
        </div>
      ))}
    </div>
  </PageSection>
);

// ─────────────────────────────────────────────────────────────
// LOGO
// ─────────────────────────────────────────────────────────────
const LogoPage = () => (
  <PageSection id="logo" eyebrow="02 · Marca" title="Logo"
    lead="El wordmark QUANTUN DIGITAL con la 'U' estilizada. Tres aplicaciones canónicas." h="h2">
    <div style={{ display:'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 14, marginBottom: 14 }}>
      {[
        ['var(--q-bg)',    'Canvas · principal',    false],
        ['var(--q-surface)','Surface',              false],
        ['var(--q-ink)',   'Ink · invertido',       true],
      ].map(([bg, label, inv]) => (
        <div key={label} style={{ background: bg, padding: '48px 24px', borderRadius: 3, border: '1px solid var(--q-border)', display:'flex', flexDirection:'column', alignItems:'center', gap: 16 }}>
          <img src={window.QUANTUN_LOGO_URL} alt="" style={{ height: 44, width:'auto', filter: inv ? 'invert(1)' : 'none' }}/>
          <div className="q-mono" style={{ fontSize: 10.5, color: inv ? 'rgba(255,255,255,.55)' : 'var(--q-ink-4)', letterSpacing: '.1em', textTransform:'uppercase' }}>{label}</div>
        </div>
      ))}
    </div>

    <div style={{ display:'grid', gridTemplateColumns: '1.1fr 1fr', gap: 14 }}>
      <div className="q-card" style={{ padding: 18 }}>
        <SectionHead title="Escalas mínimas" sub="No reducir bajo 16px de alto."/>
        <div style={{ display:'flex', gap: 28, alignItems:'flex-end', marginTop: 14 }}>
          {[64, 44, 28, 16].map(h => (
            <div key={h} style={{ display:'flex', flexDirection:'column', alignItems:'center', gap: 6 }}>
              <img src={window.QUANTUN_LOGO_URL} alt="" style={{ height: h, width:'auto' }}/>
              <span className="q-mono" style={{ fontSize: 10, color:'var(--q-ink-4)' }}>{h}px</span>
            </div>
          ))}
        </div>
      </div>
      <div className="q-card" style={{ padding: 18 }}>
        <SectionHead title="App icon · símbolo Q" sub="Lima + 'Q' negra. Para favicons y app icons."/>
        <div style={{ display:'flex', gap: 14, alignItems:'flex-end', marginTop: 14 }}>
          {[64, 44, 28, 16].map(s => (
            <div key={s} style={{ width: s, height: s, background:'var(--q-lima)', borderRadius: Math.max(2, s/12), display:'inline-flex', alignItems:'center', justifyContent:'center' }}>
              <span style={{ fontSize: s * 0.62, fontWeight: 700, color:'var(--q-ink)', lineHeight: 1, letterSpacing: '-0.04em' }}>Q</span>
            </div>
          ))}
        </div>
      </div>
    </div>
  </PageSection>
);

// ─────────────────────────────────────────────────────────────
// COLOR
// ─────────────────────────────────────────────────────────────
const ColorPage = () => {
  const Group = ({ title, sub, items }) => (
    <div style={{ marginBottom: 24 }}>
      <SectionHead title={title} sub={sub}/>
      <div className="q-card" style={{ overflow:'hidden' }}>
        <table className="tokens">
          <tbody>
            {items.map(([token, value, name, contrast]) => (
              <tr key={token}>
                <td><span className="swatch" style={{ background: value }}/></td>
                <td style={{ fontWeight: 500 }}>{name}</td>
                <td><span className="q-mono" style={{ color:'var(--q-ink-3)' }}>{value}</span></td>
                <td><span className="q-mono" style={{ color:'var(--q-ink-4)' }}>{token}</span></td>
                <td className="q-meta" style={{ textAlign:'right' }}>{contrast || ''}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
  return (
    <PageSection id="color" eyebrow="03 · Fundamentos" title="Color"
      lead="Neutrales cálidos como base. Negro carga la jerarquía visual; lima aparece sólo como acento de marca." h="h2">
      <Group title="Surface — fondos, cards y bordes" sub="Toda superficie usa esta familia."
        items={[
          ['--q-bg','#FAFAF7','Canvas','Fondo app'],
          ['--q-bg-soft','#F5F4EF','Soft','Card secundaria'],
          ['--q-surface','#FFFFFF','Surface','Cards primarias'],
          ['--q-border','#E8E5DD','Border','1px default'],
          ['--q-border-strong','#D6D2C7','Border strong','Hover'],
        ]}/>
      <Group title="Ink — texto y CTA" sub="Negro cálido como acción principal."
        items={[
          ['--q-ink','#0E0E0C','Ink 100','Títulos · CTA primario'],
          ['--q-ink-2','#2A2926','Ink 80','Sub-headlines'],
          ['--q-ink-3','#57544D','Ink 60','Body'],
          ['--q-ink-4','#8A867C','Ink 40','Meta · helper'],
          ['--q-ink-5','#B5B0A4','Ink 20','Placeholder'],
        ]}/>
      <div style={{ display:'grid', gridTemplateColumns: '1fr 1fr', gap: 14 }}>
        <Group title="Brand · Lima" sub="Único acento de marca · sutil"
          items={[
            ['--q-lima','#C6F24E','Lima','App icon · highlights'],
            ['--q-lima-soft','#E8FA9E','Lima soft','Chips · backgrounds'],
            ['--q-lima-deep','#8FB31F','Lima deep','Texto sobre lima'],
          ]}/>
        <Group title="Estados" sub="Sólo para feedback funcional"
          items={[
            ['--q-success','#2D8F5A','Success','Activo · renovado'],
            ['--q-warning','#B47A1E','Warning','Por cobrar'],
            ['--q-danger','#B0382F','Danger','En mora · error'],
            ['--q-info','#3F5E9E','Info','Información'],
          ]}/>
      </div>

      <SectionHead title="Combinaciones canónicas" sub="Cuándo combina cada color."/>
      <div style={{ display:'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: 12 }}>
        {[
          {bg:'var(--q-bg)',     fg:'var(--q-ink)',      label:'Canvas → texto principal'},
          {bg:'var(--q-bg-soft)',fg:'var(--q-ink-2)',    label:'Soft → sub-headline'},
          {bg:'var(--q-ink)',    fg:'var(--q-bg)',       label:'Ink → CTA o destacado'},
          {bg:'var(--q-lima)',   fg:'var(--q-ink)',      label:'Lima → highlight breve'},
        ].map(c => (
          <div key={c.label} style={{ background: c.bg, color: c.fg, padding: 16, border:'1px solid var(--q-border)', borderRadius: 3, minHeight: 100, display:'flex', flexDirection:'column', justifyContent:'space-between' }}>
            <span style={{ fontSize: 22, fontWeight: 600, letterSpacing:'-0.015em' }}>Aa</span>
            <span style={{ fontSize: 11.5 }}>{c.label}</span>
          </div>
        ))}
      </div>
    </PageSection>
  );
};

// ─────────────────────────────────────────────────────────────
// TYPE
// ─────────────────────────────────────────────────────────────
const TypePage = () => {
  const row = (label, weight, size, sample, ls='-0.015em', lh=1.15) => (
    <div style={{ display:'grid', gridTemplateColumns: '160px 70px 70px 1fr', gap: 16, alignItems:'baseline', padding: '14px 16px', borderBottom: '1px solid var(--q-border-subtle)' }}>
      <span style={{ fontSize: 12, fontWeight: 500 }}>{label}</span>
      <span className="q-mono" style={{ fontSize: 11, color:'var(--q-ink-4)' }}>{size}px</span>
      <span className="q-mono" style={{ fontSize: 11, color:'var(--q-ink-4)' }}>w {weight}</span>
      <span style={{ fontSize: size, fontWeight: weight, letterSpacing: ls, lineHeight: lh, overflow:'hidden', textOverflow:'ellipsis', whiteSpace:'nowrap' }}>{sample}</span>
    </div>
  );
  return (
    <PageSection id="type" eyebrow="04 · Fundamentos" title="Tipografía"
      lead="Inter para UI y prosa, JetBrains Mono para datos numéricos. Tres pesos: 400, 500, 600. Tracking ajustado en tamaños grandes." h="h2">
      <div style={{ display:'grid', gridTemplateColumns: '1fr 1fr', gap: 14, marginBottom: 24 }}>
        <div style={{ padding: 28, background:'var(--q-surface)', border:'1px solid var(--q-border)', borderRadius: 3 }}>
          <div style={{ fontFamily:'var(--q-font-sans)', fontSize: 140, fontWeight: 500, lineHeight: 1, letterSpacing: '-0.04em' }}>Aa</div>
          <div className="q-mono" style={{ marginTop: 12, fontSize: 11, color: 'var(--q-ink-4)' }}>Inter · Variable · 300–700</div>
          <div style={{ marginTop: 8, fontSize: 13, color:'var(--q-ink-3)' }}>Familia principal · UI, prosa y títulos.</div>
        </div>
        <div style={{ padding: 28, background:'var(--q-surface)', border:'1px solid var(--q-border)', borderRadius: 3 }}>
          <div className="q-mono" style={{ fontSize: 64, fontWeight: 500, lineHeight: 1, letterSpacing: '-0.02em' }}>1.287,40</div>
          <div className="q-mono" style={{ marginTop: 12, fontSize: 11, color:'var(--q-ink-4)' }}>JetBrains Mono · Tabular · Datos</div>
          <div style={{ marginTop: 8, fontSize: 13, color:'var(--q-ink-3)' }}>Para cifras, IDs, fechas y código.</div>
        </div>
      </div>

      <div className="q-card" style={{ overflow:'hidden' }}>
        <div className="demo-head"><span>Escala tipográfica</span></div>
        {row('Display',  600, 56, 'Diseña tu CRM, no solo lo uses.')}
        {row('H1',       600, 40, 'Panel Operativo')}
        {row('H2',       600, 26, 'Catálogo de Servicios')}
        {row('H3',       600, 20, 'Pipeline de Leads')}
        {row('Title',    600, 14, 'Resumen mensual de tu agencia', '-0.005em', 1.4)}
        {row('Body',     400, 13, 'Texto base del sistema. Tracking ligero y leading cómodo.', '-0.005em', 1.5)}
        {row('Caption',  500, 11, 'NOMBRE MARCA / EMPRESA · ETIQUETA', '0.08em', 1.4)}
        {row('Mono',     500, 13, '$ 1.287.400 · COP', '0', 1.4)}
      </div>
    </PageSection>
  );
};

// ─────────────────────────────────────────────────────────────
// SPACING / RADIUS / SHADOWS
// ─────────────────────────────────────────────────────────────
const SpacingPage = () => {
  const sp = [['1',4],['2',8],['3',12],['4',16],['5',20],['6',24],['7',32],['8',40],['9',48],['10',64]];
  const ra = [['xs',2],['sm',3],['md',4],['lg',6],['xl',10],['pill',999]];
  return (
    <PageSection id="spacing" eyebrow="05 · Fundamentos" title="Espaciado · Radius · Sombra"
      lead="Base 4px. Radius afilado por default. Sombras casi invisibles — el peso visual lo cargan los bordes 1px." h="h2">
      <div style={{ display:'grid', gridTemplateColumns: '1.3fr 1fr 1fr', gap: 14 }}>
        <div className="q-card" style={{ padding: 18 }}>
          <SectionHead title="Espaciado · base 4px"/>
          <div style={{ display:'flex', flexDirection:'column', gap: 10, marginTop: 10 }}>
            {sp.map(([k,v]) => (
              <div key={k} style={{ display:'flex', alignItems:'center', gap: 14 }}>
                <div className="q-mono" style={{ width: 70, fontSize: 11, color:'var(--q-ink-3)' }}>--q-s-{k}</div>
                <div style={{ height: 12, width: v, background:'var(--q-ink)', borderRadius: 2 }}/>
                <div className="q-mono" style={{ fontSize: 11, color:'var(--q-ink-4)' }}>{v}px</div>
              </div>
            ))}
          </div>
        </div>

        <div className="q-card" style={{ padding: 18 }}>
          <SectionHead title="Border-radius"/>
          <div style={{ display:'flex', flexDirection:'column', gap: 12, marginTop: 10 }}>
            {ra.map(([k,v]) => (
              <div key={k} style={{ display:'flex', alignItems:'center', gap: 14 }}>
                <div style={{ width: 40, height: 40, background:'var(--q-surface)', border:'1.5px solid var(--q-ink)', borderRadius: v }} />
                <div>
                  <div className="q-mono" style={{ fontSize: 11 }}>--q-r-{k}</div>
                  <div className="q-mono" style={{ fontSize: 10.5, color:'var(--q-ink-4)' }}>{v === 999 ? 'full' : v + 'px'}</div>
                </div>
              </div>
            ))}
          </div>
        </div>

        <div className="q-card" style={{ padding: 18 }}>
          <SectionHead title="Elevación"/>
          <div style={{ display:'flex', flexDirection:'column', gap: 14, marginTop: 10 }}>
            {[
              ['shadow-1','0 1px 0 rgba(14,14,12,.04)','flat'],
              ['shadow-2','0 1px 2px rgba(14,14,12,.06)','cards'],
              ['shadow-3','0 4px 12px rgba(14,14,12,.08)','popovers'],
              ['shadow-pop','0 12px 32px rgba(14,14,12,.14)','modales'],
            ].map(([k,v,use]) => (
              <div key={k} style={{ display:'flex', alignItems:'center', gap: 14 }}>
                <div style={{ width: 56, height: 36, background:'var(--q-surface)', border:'1px solid var(--q-border)', borderRadius: 3, boxShadow: v }}/>
                <div>
                  <div className="q-mono" style={{ fontSize: 11 }}>--q-{k}</div>
                  <div className="q-mono" style={{ fontSize: 10, color:'var(--q-ink-4)', marginTop: 2 }}>{use}</div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </PageSection>
  );
};

// ─────────────────────────────────────────────────────────────
// BUTTONS
// ─────────────────────────────────────────────────────────────
const ButtonsPage = () => (
  <PageSection id="buttons" eyebrow="06 · Componentes" title="Botones"
    lead="Negro = acción principal. Secundario para soporte. Lima sólo para celebrar/destacar. Danger es discreto." h="h2">
    <div style={{ display:'grid', gridTemplateColumns: '1fr 1fr', gap: 14 }}>
      <Demo title="Variantes" code={`<Btn variant="primary">…</Btn>`}>
        <Btn variant="primary">Nuevo cliente</Btn>
        <Btn variant="secondary">Cancelar</Btn>
        <Btn variant="ghost">Limpiar</Btn>
        <Btn variant="accent">Crear paquete</Btn>
        <Btn variant="danger">Eliminar</Btn>
      </Demo>
      <Demo title="Con icono" code={`<Btn icon={<IconPlus/>}>…</Btn>`}>
        <Btn variant="primary" icon={<IconPlus size={13}/>}>Nuevo servicio</Btn>
        <Btn variant="secondary" icon={<IconExport size={13}/>}>Exportar</Btn>
        <Btn variant="ghost" icon={<IconFilter size={13}/>}>Filtrar</Btn>
        <Btn variant="primary" icon={<IconSparkle size={13}/>}>Enviar resumen</Btn>
      </Demo>
      <Demo title="Tamaños" code={`size="sm" | undefined | size="lg"`}>
        <Btn variant="primary" size="sm">Pequeño</Btn>
        <Btn variant="primary">Medio</Btn>
        <Btn variant="primary" size="lg">Grande</Btn>
      </Demo>
      <Demo title="Icon only">
        <Btn variant="secondary" size="sm" icon={<IconEdit size={12}/>}/>
        <Btn variant="secondary" size="sm" icon={<IconCopy size={12}/>}/>
        <Btn variant="secondary" size="sm" icon={<IconEye size={12}/>}/>
        <Btn variant="secondary" size="sm" icon={<IconTrash size={12}/>}/>
        <Btn variant="ghost" icon={<IconBell size={13}/>}/>
        <Btn variant="ghost" icon={<IconCog size={13}/>}/>
      </Demo>
      <Demo title="Estados">
        <Btn variant="primary">Default</Btn>
        <Btn variant="primary" style={{ background:'#1f1d18' }}>Hover</Btn>
        <Btn variant="primary" style={{ opacity:.5, pointerEvents:'none' }}>Disabled</Btn>
      </Demo>
      <Demo title="Segmented group">
        <div style={{ display:'inline-flex', border:'1px solid var(--q-border)', borderRadius: 3, overflow:'hidden' }}>
          <button className="q-btn q-btn--ghost" style={{ borderRadius:0, borderRight:'1px solid var(--q-border)', height:30, background:'var(--q-bg-soft)' }}>Día</button>
          <button className="q-btn q-btn--ghost" style={{ borderRadius:0, borderRight:'1px solid var(--q-border)', height:30 }}>Semana</button>
          <button className="q-btn q-btn--ghost" style={{ borderRadius:0, height:30 }}>Mes</button>
        </div>
      </Demo>
    </div>
  </PageSection>
);

// ─────────────────────────────────────────────────────────────
// FORMS
// ─────────────────────────────────────────────────────────────
const FormsPage = () => (
  <PageSection id="forms" eyebrow="07 · Componentes" title="Formularios e inputs"
    lead="Altura 32px. Focus con halo negro 6%. Labels uppercase 11px." h="h2">
    <div style={{ display:'grid', gridTemplateColumns: '1fr 1fr', gap: 14 }}>
      <Demo title="Inputs" stack>
        <div>
          <label className="q-label">Nombre de la empresa</label>
          <input className="q-input" defaultValue="ARRIENDA BIEN SAS"/>
          <div className="q-helper">Como aparece en facturación.</div>
        </div>
        <div>
          <label className="q-label">Buscar</label>
          <div style={{ position:'relative' }}>
            <IconSearch size={13} style={{ position:'absolute', left:10, top:10, color:'var(--q-ink-4)' }}/>
            <input className="q-input q-input--with-icon" placeholder="Buscar por cliente, NIT…"/>
          </div>
        </div>
        <div>
          <label className="q-label">Con error</label>
          <input className="q-input" defaultValue="correo@" style={{ borderColor:'var(--q-danger)', boxShadow:'0 0 0 3px rgba(176,56,47,.08)' }}/>
          <div className="q-helper" style={{ color:'var(--q-danger)' }}>Formato inválido.</div>
        </div>
      </Demo>
      <Demo title="Select & combobox" stack>
        <div>
          <label className="q-label">Frecuencia</label>
          <div style={{ position:'relative' }}>
            <select className="q-select" style={{ appearance:'none', paddingRight: 32 }}>
              <option>Anual</option><option>Mensual</option><option>Pago único</option>
            </select>
            <IconChevD size={13} style={{ position:'absolute', right:10, top:10, color:'var(--q-ink-4)', pointerEvents:'none' }}/>
          </div>
        </div>
        <div>
          <label className="q-label">Servicios (multi)</label>
          <div className="q-input" style={{ display:'flex', alignItems:'center', flexWrap:'wrap', gap: 4, height:'auto', minHeight:32, padding: '4px 6px' }}>
            <Badge tone="neutral">Dominios — .com</Badge>
            <Badge tone="neutral">Hosting — 5 Gigas</Badge>
            <input style={{ flex:1, minWidth: 80, border:0, outline:0, fontSize: 13, fontFamily:'inherit', background:'transparent' }} placeholder="Añadir…"/>
          </div>
        </div>
        <div>
          <label className="q-label">Toggle</label>
          <div style={{ display:'flex', alignItems:'center', gap: 14 }}>
            <span style={{ display:'inline-flex', width: 34, height: 20, background:'var(--q-ink)', borderRadius: 999, padding: 2 }}>
              <span style={{ width: 16, height: 16, background:'var(--q-bg)', borderRadius:'50%', marginLeft: 'auto' }}/>
            </span>
            <span style={{ fontSize: 13 }}>Enviar correo de bienvenida</span>
          </div>
        </div>
      </Demo>
    </div>
  </PageSection>
);

// ─────────────────────────────────────────────────────────────
// BADGES & AVATARES
// ─────────────────────────────────────────────────────────────
const BadgesPage = () => (
  <PageSection id="badges" eyebrow="08 · Componentes" title="Badges & avatares"
    lead="Discretos. Sólo el badge ink rompe ritmo para acentuar." h="h2">
    <div style={{ display:'grid', gridTemplateColumns: '1fr 1fr 1fr', gap: 14 }}>
      <Demo title="Tonos">
        <Badge tone="neutral">Neutral</Badge>
        <Badge tone="ink">Ink</Badge>
        <Badge tone="accent">Acento</Badge>
        <Badge tone="success">Activo</Badge>
        <Badge tone="warning">Por cobrar</Badge>
        <Badge tone="danger">En mora</Badge>
        <Badge tone="info">Info</Badge>
        <Badge tone="outline">Outline</Badge>
      </Demo>
      <Demo title="Con dot">
        <Badge tone="success" dot>Conectado</Badge>
        <Badge tone="warning" dot>Pendiente</Badge>
        <Badge tone="danger" dot>Vencido</Badge>
        <Badge tone="neutral" dot>Borrador</Badge>
      </Demo>
      <Demo title="Frecuencia · uso real">
        <Badge tone="neutral">Anual</Badge>
        <Badge tone="neutral">Mensual</Badge>
        <Badge tone="neutral">Pago único</Badge>
        <Badge tone="accent">Recurrente</Badge>
      </Demo>
      <Demo title="Avatares">
        <QAvatar initials="AA"/>
        <QAvatar initials="ES" soft/>
        <QAvatar initials="LP"/>
        <QAvatar initials="JM" soft/>
        <div style={{ display:'inline-flex' }}>
          {['AA','ES','LP','JM'].map((n,i)=>(
            <span key={n} style={{ marginLeft: i ? -8 : 0, border:'2px solid var(--q-surface)', borderRadius:'50%', display:'inline-flex' }}>
              <QAvatar initials={n} size={26} soft={i%2===1}/>
            </span>
          ))}
        </div>
      </Demo>
      <Demo title="Categoría">
        <Badge tone="accent">Dominios</Badge>
        <Badge tone="success">Hosting</Badge>
        <Badge tone="info">Correos</Badge>
        <Badge tone="warning">Web</Badge>
      </Demo>
      <Demo title="Estado dot inline">
        <span className="q-flex q-items-c q-gap-2"><span className="q-dot" style={{ background:'var(--q-success)' }}/> Renovado</span>
        <span className="q-flex q-items-c q-gap-2"><span className="q-dot" style={{ background:'var(--q-warning)' }}/> Próximo</span>
        <span className="q-flex q-items-c q-gap-2"><span className="q-dot" style={{ background:'var(--q-danger)' }}/> Atrasado</span>
      </Demo>
    </div>
  </PageSection>
);

// ─────────────────────────────────────────────────────────────
// CARDS
// ─────────────────────────────────────────────────────────────
const CardsPage = () => (
  <PageSection id="cards" eyebrow="09 · Componentes" title="Cards y KPIs"
    lead="Surface por default, soft para secundarios, ink para destacar — máximo una ink por pantalla." h="h2">
    <div style={{ display:'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 14, marginBottom: 14 }}>
      <KPI label="Total Servicios" value="6" sub="En catálogo" icon={<IconBox size={13}/>}/>
      <KPI label="Sub-servicios" value="20" sub="6 con variantes" icon={<IconLayers size={13}/>}/>
      <KPI label="Margen positivo" value="16" sub="80% del catálogo" delta="Saludable" deltaTone="success" icon={<IconSparkle size={13}/>}/>
    </div>
    <div style={{ display:'grid', gridTemplateColumns: '1.1fr 1fr 1fr', gap: 14 }}>
      <div className="q-card">
        <div className="q-card-head">
          <div>
            <div style={{ fontSize: 14, fontWeight: 600 }}>Servicio de Hosting</div>
            <div className="q-meta">5 variantes activas</div>
          </div>
          <Badge tone="neutral">Anual</Badge>
        </div>
        <div style={{ padding: '12px 16px' }}>
          {['DD 1 Gigas — 180.000','DD 5 Gigas — 210.000','DD 25 Gigas — 350.000'].map(v => (
            <div key={v} style={{ display:'flex', alignItems:'center', justifyContent:'space-between', padding:'6px 0', borderBottom:'1px solid var(--q-border-subtle)', fontSize: 12.5 }}>
              <span className="q-flex q-items-c q-gap-2"><span className="q-dot" style={{ background:'var(--q-success)' }}/>{v.split(' — ')[0]}</span>
              <span className="q-mono q-tab" style={{ color:'var(--q-ink-2)' }}>$ {v.split(' — ')[1]}</span>
            </div>
          ))}
        </div>
      </div>
      <div className="q-card q-card--soft" style={{ padding: 18 }}>
        <Badge tone="ink">Soft</Badge>
        <div style={{ fontSize: 16, fontWeight: 600, marginTop: 10 }}>Contenido secundario</div>
        <p style={{ fontSize: 12.5, color:'var(--q-ink-3)', marginTop: 4 }}>Sin borde, fondo cálido. Para agrupar elementos relacionados.</p>
        <div style={{ marginTop: 14 }}><Btn variant="secondary" size="sm">Importar CSV</Btn></div>
      </div>
      <div className="q-card q-card--ink" style={{ padding: 18 }}>
        <Badge tone="accent">Destacado</Badge>
        <div style={{ fontSize: 16, fontWeight: 600, marginTop: 10 }}>MRR · Recurrente</div>
        <div className="q-mono q-tab" style={{ fontSize: 28, fontWeight: 500, marginTop: 8, letterSpacing:'-0.02em' }}>$ 8.620.900</div>
        <div style={{ fontSize: 11, color:'rgba(250,250,247,.55)', marginTop: 2 }}>20 clientes activos</div>
      </div>
    </div>
  </PageSection>
);

// ─────────────────────────────────────────────────────────────
// TABLE
// ─────────────────────────────────────────────────────────────
const TablePage = () => (
  <PageSection id="table" eyebrow="10 · Componentes" title="Tablas"
    lead="Densa. Tabular nums en cifras. Encabezados uppercase 10.5px. Hover sutil." h="h2">
    <div className="q-card" style={{ overflow:'hidden' }}>
      <div style={{ padding: 12, display:'flex', gap: 8 }}>
        <div style={{ position:'relative', flex: 1 }}>
          <IconSearch size={13} style={{ position:'absolute', left:10, top:10, color:'var(--q-ink-4)' }}/>
          <input className="q-input q-input--with-icon" placeholder="Buscar…"/>
        </div>
        <Btn variant="secondary" size="sm" icon={<IconFilter size={11}/>}>Filtros · 2</Btn>
        <Btn variant="primary" size="sm" icon={<IconPlus size={11}/>}>Nuevo</Btn>
      </div>
      <table className="q-table">
        <thead>
          <tr>
            <th><input type="checkbox"/></th>
            <th>Marca / Empresa</th>
            <th>Servicios</th>
            <th>Próx. renovación</th>
            <th style={{ textAlign:'right' }}>Ingresos</th>
            <th>Estado</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          {[
            ['ALGAMA Asociados','9012744711',['Dominios','Correos','Hosting'],'7 Ago 26','338.900','success','Activo'],
            ['ARRIENDA BIEN SAS','9000801155',['Dominios'],'24 Mar 27','168.900','success','Activo'],
            ['CEICAR SAS','SIN NIT',['Correos','Dominios'],'2 Jun 26','259.000','warning','Por cobrar'],
            ['DOCTORA Luz Torralvo','SIN NIT',['Dominios','Hosting'],'14 Oct 26','259.900','danger','En mora'],
          ].map((r,i)=>(
            <tr key={i}>
              <td><input type="checkbox"/></td>
              <td>
                <div style={{ display:'flex', alignItems:'center', gap: 10 }}>
                  <QAvatar initials={r[0].split(' ').map(w=>w[0]).slice(0,2).join('').toUpperCase()} soft size={26}/>
                  <div>
                    <div style={{ fontSize: 12.5, fontWeight: 500 }}>{r[0]}</div>
                    <div className="q-mono" style={{ fontSize: 10.5, color:'var(--q-ink-4)' }}>{r[1]}</div>
                  </div>
                </div>
              </td>
              <td><div style={{ display:'flex', gap: 4, flexWrap:'wrap' }}>{r[2].map(s => <Badge key={s} tone="neutral">{s}</Badge>)}</div></td>
              <td className="q-mono" style={{ fontSize: 12 }}>{r[3]}</td>
              <td className="q-mono q-tab" style={{ textAlign:'right', fontWeight: 500 }}>$ {r[4]}</td>
              <td><Badge tone={r[5]} dot>{r[6]}</Badge></td>
              <td><Btn variant="ghost" size="sm" icon={<IconChevR size={11}/>}/></td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  </PageSection>
);

// ─────────────────────────────────────────────────────────────
// NAV & MODAL
// ─────────────────────────────────────────────────────────────
const NavModalPage = () => (
  <PageSection id="nav" eyebrow="11 · Componentes" title="Navegación, tabs & modales" h="h2">
    <div style={{ display:'grid', gridTemplateColumns: '1fr 1fr', gap: 14 }}>
      <Demo title="Tabs">
        <div className="q-tabs" style={{ width:'100%' }}>
          <button className="q-tab-item q-tab-item--active">Mis Clientes</button>
          <button className="q-tab-item">Pagos únicos</button>
          <button className="q-tab-item">Renovaciones <Badge tone="accent">3</Badge></button>
        </div>
      </Demo>
      <Demo title="Breadcrumb">
        <div className="q-crumb"><span>Dashboard</span><IconChevR size={11}/><span className="q-crumb-cur">Catálogo</span></div>
      </Demo>
      <Demo title="Modal de confirmación" stack>
        <div style={{ background:'var(--q-surface)', border:'1px solid var(--q-border)', borderRadius: 4, boxShadow:'var(--q-shadow-pop)', padding: 20 }}>
          <div style={{ display:'flex', justifyContent:'space-between', alignItems:'flex-start' }}>
            <div>
              <div style={{ fontSize: 14, fontWeight: 600 }}>Eliminar servicio “Hosting”</div>
              <p style={{ fontSize: 12.5, color:'var(--q-ink-3)', marginTop: 4 }}>5 variantes pasarán a archivo. No afecta facturación previa.</p>
            </div>
            <button className="q-btn q-btn--ghost q-btn--icon"><IconX size={14}/></button>
          </div>
          <div style={{ display:'flex', justifyContent:'flex-end', gap: 8, marginTop: 16 }}>
            <Btn variant="ghost" size="sm">Cancelar</Btn>
            <Btn variant="danger" size="sm" icon={<IconTrash size={11}/>}>Eliminar</Btn>
          </div>
        </div>
      </Demo>
      <Demo title="Empty state" stack>
        <div style={{ padding: '28px 20px', textAlign:'center', background:'var(--q-bg-soft)', border:'1px dashed var(--q-border-strong)', borderRadius: 3 }}>
          <div style={{ display:'inline-flex', width:32, height:32, alignItems:'center', justifyContent:'center', borderRadius:4, background:'var(--q-surface)', border:'1px solid var(--q-border)', marginBottom: 10 }}><IconLink size={14}/></div>
          <div style={{ fontSize: 13, fontWeight: 500 }}>No tienes enlaces de pago activos</div>
          <p style={{ fontSize: 12, color:'var(--q-ink-3)', maxWidth: 280, margin: '4px auto 12px' }}>Conecta tu pasarela y empieza a cobrar renovaciones automáticas.</p>
          <Btn variant="primary" size="sm" icon={<IconPlus size={11}/>}>Conectar pasarela</Btn>
        </div>
      </Demo>
    </div>
  </PageSection>
);

// ─────────────────────────────────────────────────────────────
// PANTALLAS — Viewer
// ─────────────────────────────────────────────────────────────
const ScreensPage = () => (
  <>
    <PageSection id="screen-dashboard" eyebrow="12 · Pantallas" title="Dashboard"
      lead="Centrado en lo que requiere atención. Action Center arriba, performance con sparklines, pipeline y actividad reciente al mismo nivel. Una sola card ink para el MRR." h="h2">
      <div style={{ display:'flex', alignItems:'center', gap: 6, marginBottom: 12 }}>
        <Badge tone="success">UX mejorada</Badge>
        <span className="q-meta">Greeting · Action Center · Sparklines · Activity feed · Tareas inline</span>
      </div>
      <ScreenViewer url="/dashboard" height={920}>
        <DashboardV2/>
      </ScreenViewer>
    </PageSection>

    <PageSection id="screen-servicios" eyebrow="13 · Pantallas" title="Servicios"
      lead="Cada servicio muestra margen visual, clientes activos y total facturable. Edición inline y filter chips removibles." h="h2">
      <div style={{ display:'flex', alignItems:'center', gap: 6, marginBottom: 12 }}>
        <Badge tone="success">UX mejorada</Badge>
        <span className="q-meta">Iconografía · Margen visual · Cliente count · Total mensual · Vista grid/lista</span>
      </div>
      <ScreenViewer url="/servicios" height={1020}>
        <ServiciosV2/>
      </ScreenViewer>
    </PageSection>

    <PageSection id="screen-clientes" eyebrow="14 · Pantallas" title="Clientes"
      lead="Vistas guardadas como tabs, filter chips removibles, barra de bulk actions, columna de salud y drawer lateral con detalle completo del cliente." h="h2">
      <div style={{ display:'flex', alignItems:'center', gap: 6, marginBottom: 12 }}>
        <Badge tone="success">UX mejorada</Badge>
        <span className="q-meta">Saved views · Filter chips · Bulk actions · Health bar · Detail drawer · LTV · Timeline</span>
      </div>
      <ScreenViewer url="/clientes" height={1020}>
        <ClientesV2/>
      </ScreenViewer>
    </PageSection>
  </>
);

// ─────────────────────────────────────────────────────────────
// CHANGELOG & HANDOFF
// ─────────────────────────────────────────────────────────────
const ChangelogPage = () => (
  <PageSection id="changelog" eyebrow="15 · Recursos" title="Changelog" h="h2"
    lead="Versionado semántico. Cambios documentados con cada release.">
    <div className="q-card">
      {[
        ['v 1.0', '20 May 2026', 'Release inicial', [
          'Tokens · color, tipografía, espaciado, radius, sombras',
          'Componentes · botones, formularios, badges, cards, tablas, navegación, modales',
          'Pantallas rediseñadas · Dashboard, Servicios y Clientes',
          'Logo QUANTUN Digital integrado en 3 variantes',
          'Tweaks en vivo · acento, tipografía, densidad, radius, KPI mode',
        ]],
        ['v 0.9', '15 May 2026', 'Beta interna', [
          'Primera versión de Design System en canvas',
          'Definición de paleta cálida y acento lima',
        ]],
      ].map(([v, d, t, items], i, arr) => (
        <div key={v} style={{ padding: 18, borderBottom: i < arr.length - 1 ? '1px solid var(--q-border-subtle)' : 'none' }}>
          <div style={{ display:'flex', alignItems:'baseline', gap: 10 }}>
            <Badge tone="ink">{v}</Badge>
            <span style={{ fontWeight: 600 }}>{t}</span>
            <span className="q-meta" style={{ marginLeft: 'auto' }}>{d}</span>
          </div>
          <ul style={{ marginTop: 10, paddingLeft: 18, color:'var(--q-ink-3)', fontSize: 13, lineHeight: 1.7 }}>
            {items.map(x => <li key={x}>{x}</li>)}
          </ul>
        </div>
      ))}
    </div>
  </PageSection>
);

const HandoffPage = () => (
  <PageSection id="handoff" eyebrow="16 · Recursos" title="Handoff a Claude Code" h="h2"
    lead="Pega este prompt al iniciar tu proyecto con Claude Code para que respete el Design System.">
    <div style={{ display:'grid', gridTemplateColumns: '1.2fr 1fr', gap: 14 }}>
      <div className="q-card">
        <div className="q-card-head">
          <span style={{ fontWeight: 600, fontSize: 13 }}>Prompt sugerido</span>
          <Btn variant="secondary" size="sm" icon={<IconCopy size={11}/>}>Copiar</Btn>
        </div>
        <div style={{ padding: 16, fontFamily:'var(--q-font-mono)', fontSize: 12, lineHeight: 1.6, color:'var(--q-ink-2)', whiteSpace:'pre-wrap', background:'var(--q-bg-soft)' }}>
{`Implementa la UI usando el Design System QUANTUN Digital v1.0.

Reglas no negociables:
• Fondo: off-white cálido #FAFAF7 (--q-bg). Nunca uses fondo oscuro.
• Texto principal: negro cálido #0E0E0C (--q-ink). CTA primario = negro sólido.
• Acento de marca: lima #C6F24E (--q-lima). Sólo para highlights breves
  (app icon, dot, chip ocasional). Nunca como fondo de pantalla o botón grande.
• Tipografía: Inter (UI/prosa) + JetBrains Mono (datos numéricos tabulares).
• Border-radius: 3px default (afilado, tipo Linear). Pill sólo para chips de marca.
• Bordes 1px (#E8E5DD) hacen la separación; sombras casi invisibles.
• Densidad compacta: botones 32px, inputs 32px, table padding 12px.
• Máximo UNA card ink (negra) por pantalla.

Tokens en CSS variables prefijo --q-* (ver ds-tokens.css).
Componentes con prefijo .q-* (q-btn, q-card, q-input, q-table, q-badge…).`}
        </div>
      </div>
      <div style={{ display:'flex', flexDirection:'column', gap: 14 }}>
        <div className="q-card" style={{ padding: 16 }}>
          <SectionHead title="Archivos a importar" sub="Copia estos a tu proyecto."/>
          <div style={{ display:'flex', flexDirection:'column', gap: 6, marginTop: 8 }}>
            {['ds-tokens.css','ds-components.jsx','ds-icons.jsx','assets/quantun-logo-negro.png'].map(f => (
              <div key={f} style={{ display:'flex', alignItems:'center', justifyContent:'space-between', padding: '6px 10px', background:'var(--q-bg-soft)', borderRadius: 3 }}>
                <span className="q-mono" style={{ fontSize: 11.5 }}>{f}</span>
                <Btn variant="ghost" size="sm" icon={<IconExport size={11}/>}/>
              </div>
            ))}
          </div>
        </div>
        <div className="q-card q-card--ink" style={{ padding: 16 }}>
          <Badge tone="accent">Tip</Badge>
          <div style={{ marginTop: 10, fontSize: 13, lineHeight: 1.5 }}>
            Compártele el link del DS a Claude Code para que tenga el contexto visual completo, no sólo los tokens.
          </div>
        </div>
      </div>
    </div>
  </PageSection>
);

Object.assign(window, {
  HeroIntro, PrinciplesPage, LogoPage, ColorPage, TypePage, SpacingPage,
  ButtonsPage, FormsPage, BadgesPage, CardsPage, TablePage, NavModalPage,
  ScreensPage, ChangelogPage, HandoffPage,
});
