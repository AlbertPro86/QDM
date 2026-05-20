# Sistema de Diseño — Antigravity

## TABLA DE CONTENIDOS
1. Paletas de Color
2. Tipografía
3. Grid y Layout
4. Espaciado
5. Sombras y Efectos
6. Animaciones
7. Checklist final

---

## 1. PALETAS DE COLOR

### Dark Premium (default)

:root {
  --bg-base: #0a0a0a;
  --bg-surface: #111111;
  --bg-elevated: #1a1a1a;
  --bg-overlay: #222222;

  --brand-400: #818cf8;
  --brand-500: #6366f1;
  --brand-600: #4f46e5;
  --brand-700: #4338ca;

  --text-primary:   rgba(255,255,255,1.0);
  --text-secondary: rgba(255,255,255,0.6);
  --text-muted:     rgba(255,255,255,0.3);
  --text-disabled:  rgba(255,255,255,0.15);

  --border-default: rgba(255,255,255,0.08);
  --border-strong:  rgba(255,255,255,0.15);
  --border-brand:   rgba(99,102,241,0.4);

  --success: #22c55e;
  --warning: #f59e0b;
  --error:   #ef4444;
  --info:    #3b82f6;
}

### Light Clean (alternativa)

:root[data-theme="light"] {
  --bg-base: #ffffff;
  --bg-surface: #f8f9fa;
  --bg-elevated: #f0f1f5;
  --text-primary:   #0a0a0a;
  --text-secondary: rgba(10,10,10,0.6);
  --text-muted:     rgba(10,10,10,0.4);
  --border-default: rgba(0,0,0,0.08);
}

### Neutral Luxury (alternativa premium)

bg:      #0d0c0f
surface: #16141a
border:  #2d2833
accent:  #c084fc
text:    #f8f5ff

---

## 2. TIPOGRAFÍA

### Importar en el head de cada HTML

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

### CSS base

body {
  font-family: 'Inter', sans-serif;
  font-size: 16px;
  line-height: 1.6;
  color: var(--text-secondary);
}

h1, h2, h3, h4 {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1.1;
  letter-spacing: -0.02em;
}

### Escala fluida con clamp()

.text-display { font-size: clamp(2.5rem, 6vw, 5rem); }
.text-h1      { font-size: clamp(2rem, 4vw, 3.5rem); }
.text-h2      { font-size: clamp(1.5rem, 3vw, 2.5rem); }
.text-h3      { font-size: clamp(1.25rem, 2vw, 1.75rem); }
.text-body-lg { font-size: clamp(1rem, 1.5vw, 1.25rem); }
.text-body    { font-size: 1rem; }
.text-small   { font-size: 0.875rem; }
.text-xs      { font-size: 0.75rem; }

### Pesos y usos

800 → Display / Hero headlines
700 → H1, H2, botones CTA
600 → H3, H4, labels destacados
500 → Nav links, subheadings
400 → Body text, párrafos

---

## 3. GRID Y LAYOUT

### Contenedores

.container-tight  { max-width: 768px;  margin: 0 auto; padding: 0 1.5rem; }
.container-normal { max-width: 1024px; margin: 0 auto; padding: 0 1.5rem; }
.container-wide   { max-width: 1280px; margin: 0 auto; padding: 0 1.5rem; }
.container-full   { max-width: 1536px; margin: 0 auto; padding: 0 2rem; }

### Grids más usados

2 columnas:
<div class="grid md:grid-cols-2 gap-6 lg:gap-10">

3 columnas:
<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">

4 columnas:
<div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 lg:gap-6">

Contenido + sidebar:
<div class="grid lg:grid-cols-[1fr_320px] gap-10">

Sidebar + contenido:
<div class="grid lg:grid-cols-[280px_1fr] gap-0">

---

## 4. ESPACIADO

gap-4  = 16px  → entre elementos del mismo grupo
gap-6  = 24px  → padding de cards pequeñas
p-8    = 32px  → padding de cards normales
mb-12  = 48px  → entre grupos visuales
py-20  = 80px  → secciones pequeñas
py-24  = 96px  → secciones medianas
py-32  = 128px → secciones grandes

Regla: elementos relacionados juntos, secciones con mucho aire.

---

## 5. SOMBRAS Y EFECTOS

### Glows de marca

.glow-brand        { box-shadow: 0 0 40px rgba(99,102,241,0.2); }
.glow-brand-strong { box-shadow: 0 0 80px rgba(99,102,241,0.3); }

### Sombra para cards

.shadow-card       { box-shadow: 0 1px 3px rgba(0,0,0,0.3), 0 4px 12px rgba(0,0,0,0.2); }
.shadow-card-hover { box-shadow: 0 4px 24px rgba(0,0,0,0.4), 0 0 0 1px rgba(255,255,255,0.05); }

### Glassmorphism

.glass {
  background: rgba(255,255,255,0.03);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(255,255,255,0.08);
}

### Texto degradado

.gradient-text {
  background: linear-gradient(135deg, #818cf8, #c084fc);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

### Orb / spotlight de fondo

.bg-orb {
  position: absolute;
  width: 600px;
  height: 600px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(99,102,241,0.15), transparent 70%);
  pointer-events: none;
}

---

## 6. ANIMACIONES

### Keyframes

@keyframes fade-in {
  from { opacity: 0; transform: translateY(16px); }
  to   { opacity: 1; transform: translateY(0); }
}

@keyframes scale-in {
  from { opacity: 0; transform: scale(0.95); }
  to   { opacity: 1; transform: scale(1); }
}

@keyframes pulse-glow {
  0%, 100% { box-shadow: 0 0 20px rgba(99,102,241,0.2); }
  50%       { box-shadow: 0 0 40px rgba(99,102,241,0.4); }
}

### Accesibilidad (siempre incluir)

@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after {
    animation-duration: 0.01ms !important;
    transition-duration: 0.01ms !important;
  }
}

### Timing functions

--ease-smooth: cubic-bezier(0.4, 0, 0.2, 1);
--ease-spring: cubic-bezier(0.34, 1.56, 0.64, 1);
--ease-in:     cubic-bezier(0.4, 0, 1, 1);
--ease-out:    cubic-bezier(0, 0, 0.2, 1);

### Clases de utilidad

.animate-fade-in  { animation: fade-in  0.5s var(--ease-smooth) forwards; }
.animate-scale-in { animation: scale-in 0.3s var(--ease-spring) forwards; }

.delay-100 { animation-delay: 0.1s; }
.delay-200 { animation-delay: 0.2s; }
.delay-300 { animation-delay: 0.3s; }
.delay-400 { animation-delay: 0.4s; }
.delay-500 { animation-delay: 0.5s; }

---

## 7. CHECKLIST ANTES DE ENTREGAR

- Tokens CSS definidos, sin valores hardcodeados
- Fuentes importadas en el head
- Breakpoints revisados: 375px / 768px / 1280px
- Contraste texto/fondo mínimo 4.5:1
- Estados hover/focus/active en todos los elementos clickeables
- prefers-reduced-motion incluido
- Un solo h1 por página
- Imágenes con alt y loading="lazy"
- Meta tags básicos presentes en HTML completo