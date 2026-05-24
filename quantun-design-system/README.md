# QUANTUN Digital · Design System v1.0

Sistema de diseño completo para la plataforma CRM QUANTUN Digital.
Documentación + tokens + componentes + pantallas rediseñadas en un único sitio HTML.

---

## 📂 Contenido

| Archivo | Descripción |
|---|---|
| `index.html` | Sitio web de documentación (servir desde un servidor local o estático) |
| `ds-tokens.css` | Variables CSS · color, tipografía, espaciado, radius, sombras, motion |
| `site.css` | Estilos del shell del sitio de docs |
| `ds-components.jsx` | Componentes React base (Button, Badge, Card, Sidebar, Topbar, KPI…) |
| `ds-icons.jsx` | Set de íconos Lucide-style (outline 1.5px) |
| `screens-v2.jsx` | Pantallas rediseñadas (Dashboard / Servicios / Clientes) |
| `site-shell.jsx` | Topbar + Sidebar + TOC del sitio de documentación |
| `site-pages.jsx` | Contenido de las secciones de la documentación |
| `tweaks-panel.jsx` | Panel de tweaks en vivo (color, tipografía, densidad…) |
| `logo-data.js` | Logo QUANTUN embebido como data URI (sirve offline y standalone) |
| `assets/quantun-logo-negro.png` | Logo original PNG (702×227) |

---

## 🚀 Cómo verlo

### Opción A · Abrir directo

Abre `index.html` con doble clic en cualquier navegador moderno (Chrome, Safari, Firefox).
Funciona offline — el logo va embebido como data URI.

### Opción B · Servidor local (recomendado)

```bash
cd quantun-design-system
npx serve .
# o
python3 -m http.server 8000
```

Abre `http://localhost:8000`.

---

## 🎨 Tokens principales

```css
--q-bg:      #FAFAF7   /* canvas off-white cálido */
--q-surface: #FFFFFF   /* cards */
--q-ink:     #0E0E0C   /* texto principal + CTA */
--q-lima:    #C6F24E   /* acento de marca · sutil */
--q-border:  #E8E5DD   /* 1px border default */

--q-font-sans: 'Inter'
--q-font-mono: 'JetBrains Mono'

--q-r-sm: 3px          /* radius default · afilado */
--q-s-3:  12px         /* spacing base · escala 4px */
```

### Reglas no negociables

1. **Claro siempre, oscuro nunca.** Fondo off-white cálido.
2. **Negro carga el peso.** Títulos y CTA primario en `--q-ink`.
3. **Lima sólo como acento.** Nunca como fondo grande o botón principal.
4. **Bordes 1px antes que sombras.** Las sombras son casi invisibles.
5. **Tabular nums en cifras.** JetBrains Mono con `font-variant-numeric: tabular-nums`.
6. **Una sola card ink por pantalla.** El destacado se gana.
7. **Densidad compacta.** Botones 32px, inputs 32px, table padding 12px.

---

## 🧩 Para Diseño / Producto

El sitio incluye:
- **Documentación viva** con ejemplos en cada componente.
- **3 pantallas rediseñadas** (Dashboard, Servicios, Clientes) listas para validar.
- **Panel de Tweaks** (botón arriba a la derecha) para probar variaciones en vivo: acento de marca, tipografía, densidad, radius, modo de KPI.
- **Changelog** y **Handoff a Claude Code** con prompt sugerido.

---

## 🛠 Para Desarrollo

### Stack que asume el DS

- HTML5 + CSS Variables + React 18 (o cualquier framework que respete los tokens).
- **No depende de Tailwind**, pero los tokens se pueden mapear 1:1 a `tailwind.config.js`.

### Mapear a Tailwind (opcional)

```js
// tailwind.config.js
module.exports = {
  theme: {
    colors: {
      bg:      '#FAFAF7',
      surface: '#FFFFFF',
      ink:     { DEFAULT: '#0E0E0C', 80: '#2A2926', 60: '#57544D', 40: '#8A867C', 20: '#B5B0A4' },
      lima:    { DEFAULT: '#C6F24E', soft: '#E8FA9E', deep: '#8FB31F' },
      border:  '#E8E5DD',
      success: '#2D8F5A',
      warning: '#B47A1E',
      danger:  '#B0382F',
      info:    '#3F5E9E',
    },
    fontFamily: {
      sans: ['Inter', 'system-ui', 'sans-serif'],
      mono: ['JetBrains Mono', 'monospace'],
    },
    borderRadius: { xs:'2px', sm:'3px', md:'4px', lg:'6px', xl:'10px' },
  },
}
```

### Componentes clave (ver `ds-components.jsx`)

- `<Btn variant="primary|secondary|ghost|accent|danger" size="sm|lg" icon={...}>`
- `<Badge tone="neutral|ink|accent|success|warning|danger|info|outline" dot>`
- `<KPI label value unit sub delta deltaTone icon>`
- `<QAvatar initials size soft>`
- `<Sidebar active>` / `<Topbar title crumbs right>`

---

## 📋 Handoff a Claude Code · prompt sugerido

```
Implementa la UI usando el Design System QUANTUN Digital v1.0.

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
Componentes con prefijo .q-* (q-btn, q-card, q-input, q-table, q-badge…).
```

---

## 🪪 Versión

**v 1.0** · 20 de Mayo de 2026
Mantenido por el equipo de Diseño QUANTUN Digital.
