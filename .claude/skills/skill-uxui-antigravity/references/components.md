# Componentes UI — Antigravity Reference

## TABLA DE CONTENIDOS
1. Hero Sections
2. Cards
3. Navigation
4. CTA Section
5. Features Grid
6. Pricing
7. Footer

---

## 1. HERO SECTIONS

### Hero Minimal (dark, centrado)

<section class="relative min-h-screen flex items-center justify-center overflow-hidden bg-[#0a0a0a]">
  <div class="absolute inset-0 bg-gradient-to-b from-indigo-950/20 to-transparent pointer-events-none"></div>
  <div class="relative z-10 text-center max-w-4xl mx-auto px-6">
    <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/5 border border-white/10 text-sm text-indigo-400 mb-8">
      ✦ Nuevo · Antigravity Studio
    </span>
    <h1 class="text-5xl md:text-7xl font-bold text-white leading-[1.1] tracking-tight mb-6">
      Diseño que convierte.<br>
      <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-400">
        Código que escala.
      </span>
    </h1>
    <p class="text-lg md:text-xl text-white/50 max-w-2xl mx-auto mb-10 leading-relaxed">
      Transformamos ideas en experiencias digitales que generan resultados medibles.
    </p>
    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
      <a href="#" class="px-8 py-4 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-full transition-all duration-200 hover:scale-105">
        Empezar ahora
      </a>
      <a href="#" class="px-8 py-4 text-white/70 hover:text-white transition-colors duration-200">
        Ver proyectos →
      </a>
    </div>
  </div>
</section>

### Hero Split (imagen + texto)

<section class="min-h-screen grid lg:grid-cols-2 bg-[#0a0a0a]">
  <div class="flex items-center justify-end px-8 lg:px-16 py-20">
    <div class="max-w-lg">
      <span class="text-indigo-400 text-sm font-medium tracking-widest uppercase mb-4 block">Antigravity Studio</span>
      <h1 class="text-4xl lg:text-6xl font-bold text-white leading-tight mb-6">
        Experiencias digitales que no se olvidan
      </h1>
      <p class="text-white/50 text-lg mb-10 leading-relaxed">
        Diseño estratégico + ingeniería de software para marcas que quieren destacar.
      </p>
      <a href="#" class="inline-flex items-center gap-3 px-8 py-4 bg-white text-black font-semibold rounded-full hover:bg-white/90 transition-all duration-200">
        Ver trabajo
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
      </a>
    </div>
  </div>
  <div class="relative overflow-hidden">
    <img src="https://picsum.photos/800/1000?random=1" alt="Hero visual" class="w-full h-full object-cover opacity-60">
    <div class="absolute inset-0 bg-gradient-to-r from-[#0a0a0a] to-transparent"></div>
  </div>
</section>

---

## 2. CARDS

### Feature Card

<div class="group p-6 rounded-2xl bg-white/[0.03] border border-white/10 hover:border-indigo-500/40 hover:bg-white/[0.06] transition-all duration-300">
  <div class="w-12 h-12 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center mb-5 group-hover:bg-indigo-500/20 transition-colors duration-300">
    <svg class="w-6 h-6 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"></svg>
  </div>
  <h3 class="text-white font-semibold text-lg mb-2">Título del feature</h3>
  <p class="text-white/50 text-sm leading-relaxed">Descripción breve que explica el beneficio de forma clara.</p>
</div>

### Product Card (ecommerce)

<div class="group relative rounded-2xl overflow-hidden bg-[#141414] border border-white/5 hover:border-white/15 transition-all duration-300">
  <div class="relative aspect-square overflow-hidden">
    <img src="https://picsum.photos/400/400?random=2" alt="Producto" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
    <span class="absolute top-3 left-3 px-3 py-1 bg-indigo-600 text-white text-xs font-semibold rounded-full">Nuevo</span>
    <button class="absolute bottom-3 right-3 w-10 h-10 rounded-full bg-white/10 backdrop-blur border border-white/20 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
      <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
    </button>
  </div>
  <div class="p-4">
    <p class="text-white/40 text-xs mb-1">Categoría</p>
    <h3 class="text-white font-medium mb-2">Nombre del producto</h3>
    <div class="flex items-center justify-between">
      <span class="text-white font-bold text-lg">$99.00</span>
      <button class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-xl transition-colors duration-200">Agregar</button>
    </div>
  </div>
</div>

### Testimonial Card

<div class="p-6 rounded-2xl bg-white/[0.03] border border-white/10">
  <div class="flex gap-1 mb-4"><span class="text-yellow-400 text-sm">★★★★★</span></div>
  <p class="text-white/70 text-sm leading-relaxed mb-6">
    "Este servicio transformó completamente la manera en que presentamos nuestra marca."
  </p>
  <div class="flex items-center gap-3">
    <img src="https://picsum.photos/40/40?random=3" alt="Avatar" class="w-10 h-10 rounded-full object-cover">
    <div>
      <p class="text-white font-medium text-sm">Nombre Apellido</p>
      <p class="text-white/40 text-xs">CEO · Empresa</p>
    </div>
  </div>
</div>

---

## 3. NAVIGATION

### Topbar Sticky

<header class="fixed top-0 left-0 right-0 z-50 px-6 py-4">
  <nav class="max-w-6xl mx-auto flex items-center justify-between bg-black/40 backdrop-blur-xl border border-white/10 rounded-2xl px-6 py-3">
    <a href="/" class="text-white font-bold text-xl tracking-tight">Antigravity</a>
    <ul class="hidden md:flex items-center gap-8">
      <li><a href="#" class="text-white/60 hover:text-white text-sm transition-colors duration-200">Servicios</a></li>
      <li><a href="#" class="text-white/60 hover:text-white text-sm transition-colors duration-200">Proyectos</a></li>
      <li><a href="#" class="text-white/60 hover:text-white text-sm transition-colors duration-200">Nosotros</a></li>
      <li><a href="#" class="text-white/60 hover:text-white text-sm transition-colors duration-200">Blog</a></li>
    </ul>
    <div class="flex items-center gap-3">
      <a href="#" class="hidden md:inline-flex px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold rounded-xl transition-colors duration-200">Contactar</a>
      <button class="md:hidden w-9 h-9 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center">
        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
    </div>
  </nav>
</header>

---

## 4. CTA SECTION

<section class="py-24 px-6">
  <div class="max-w-4xl mx-auto text-center">
    <div class="relative rounded-3xl overflow-hidden p-12 md:p-16 bg-gradient-to-br from-indigo-900/50 to-purple-900/30 border border-indigo-500/20">
      <div class="absolute top-0 left-1/2 -translate-x-1/2 w-96 h-96 bg-indigo-500/20 rounded-full blur-3xl -z-10"></div>
      <h2 class="text-3xl md:text-5xl font-bold text-white mb-4 leading-tight">
        ¿Listo para escalar<br>tu presencia digital?
      </h2>
      <p class="text-white/50 text-lg mb-10 max-w-xl mx-auto">
        Agenda una consultoría gratuita y descubre cómo podemos transformar tu negocio.
      </p>
      <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
        <a href="#" class="px-8 py-4 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-full transition-all duration-200 hover:scale-105">Agendar consultoría</a>
        <a href="#" class="px-8 py-4 text-white/60 hover:text-white transition-colors duration-200">Ver casos de éxito →</a>
      </div>
    </div>
  </div>
</section>

---

## 5. FEATURES GRID

<section class="py-24 px-6 bg-[#0a0a0a]">
  <div class="max-w-6xl mx-auto">
    <div class="text-center mb-16">
      <span class="text-indigo-400 text-sm font-medium tracking-widest uppercase mb-3 block">¿Por qué elegirnos?</span>
      <h2 class="text-3xl md:text-5xl font-bold text-white">Todo lo que necesitas,<br>en un solo lugar</h2>
    </div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <!-- Repetir Feature Card aquí -->
    </div>
  </div>
</section>

---

## 6. PRICING CARD

<div class="p-6 rounded-2xl bg-white/[0.03] border border-white/10 flex flex-col">
  <div class="mb-6">
    <p class="text-white/50 text-sm mb-1">Starter</p>
    <div class="flex items-end gap-1">
      <span class="text-4xl font-bold text-white">$499</span>
      <span class="text-white/40 text-sm mb-1">/mes</span>
    </div>
  </div>
  <ul class="space-y-3 flex-1 mb-8">
    <li class="flex items-center gap-3 text-white/60 text-sm">
      <span class="w-5 h-5 rounded-full bg-indigo-500/10 border border-indigo-500/30 flex items-center justify-center flex-shrink-0">
        <svg class="w-3 h-3 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
      </span>
      Landing page completa
    </li>
  </ul>
  <a href="#" class="w-full py-3 text-center border border-white/10 hover:border-indigo-500/40 text-white/70 hover:text-white text-sm font-medium rounded-xl transition-all duration-200">Empezar</a>
</div>

---

## 7. FOOTER

<footer class="border-t border-white/5 py-12 px-6">
  <div class="max-w-6xl mx-auto flex flex-col md:flex-row items-center justify-between gap-6">
    <a href="/" class="text-white font-bold text-xl">Antigravity</a>
    <ul class="flex items-center gap-8">
      <li><a href="#" class="text-white/40 hover:text-white text-sm transition-colors duration-200">Servicios</a></li>
      <li><a href="#" class="text-white/40 hover:text-white text-sm transition-colors duration-200">Proyectos</a></li>
      <li><a href="#" class="text-white/40 hover:text-white text-sm transition-colors duration-200">Contacto</a></li>
    </ul>
    <p class="text-white/30 text-sm">© 2025 Antigravity. Todos los derechos reservados.</p>
  </div>
</footer>
