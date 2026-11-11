<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { ArrowUpRight, ArrowRight, ArrowDown, Menu, X, Plus, Minus, Heart, Sprout, Stethoscope, GraduationCap, BriefcaseBusiness, Check, MapPin } from '@lucide/vue'
import InterestDialog from './components/InterestDialog.vue'

const menuOpen = ref(false)
const joinOpen = ref(false)
const selectedPriority = ref(0)
const galleryIndex = ref(null)
const galleryFilter = ref('All moments')
const visiblePhotoCount = ref(3)
const openQuestion = ref(0)
const activeSection = ref('home')
const scrollProgress = ref(0)
const galleryFilters = ['All moments', 'Community', 'Leadership']
const questions = [
  { question: 'What is the vision for Busia in 2027?', answer: 'A people-first future built around livelihoods, accessible healthcare, education and opportunity. Explore the four proposed priorities above to see the areas this website puts forward for discussion.' },
  { question: 'Is a political party represented here?', answer: 'This website presents Arthur Amug Osiya’s Busia 2027 identity without indicating a political party. The focus is on the candidate, the proposed priorities and the people of Busia.' },
  { question: 'Are these the final campaign commitments?', answer: 'The priorities on this website are draft proposals for review and conversation. They should not be treated as a final, published manifesto until approved by the candidate.' },
  { question: 'How can I get involved?', answer: 'Choose “Get involved” to prepare your interest in volunteering, sharing an idea or joining community conversations. In this preview, the form saves a draft on your device and lets you download or remove it. It does not yet send your details to the campaign.' },
]
const priorities = [
  { icon: Sprout, name: 'Agriculture & livelihoods', short: 'A stronger foundation for every family.', title: 'Make hard work\nwork for our farmers.', description: 'A proposed agenda that puts the people who grow our food at the centre of our county’s progress.', points: ['Better access to inputs, extension services and markets', 'Value addition that keeps more opportunity close to home', 'Practical support for small businesses and cooperatives'], tag: 'Growing opportunity' },
  { icon: Stethoscope, name: 'Accessible healthcare', short: 'Quality care, closer to home.', title: 'Better care.\nGreater peace of mind.', description: 'A vision for accessible, dignified healthcare, with stronger local services and a focus on prevention.', points: ['Stronger primary healthcare and community health services', 'Reliable essential supplies and responsive facilities', 'Maternal and child health at the heart of planning'], tag: 'Health with dignity' },
  { icon: GraduationCap, name: 'Education & skills', short: 'Give the next generation a fair start.', title: 'Open doors.\nUnlock possibility.', description: 'A proposed focus on early learning and practical skills that help people turn ambition into opportunity.', points: ['Supportive early childhood learning environments', 'Practical vocational skills and pathways into work', 'Transparent, fair access to county education support'], tag: 'Potential without limits' },
  { icon: BriefcaseBusiness, name: 'Enterprise & opportunity', short: 'An economy that includes everyone.', title: 'Local ambition.\nReal opportunity.', description: 'A vision for a county where enterprise can grow and public services help people move forward.', points: ['A friendlier environment for local enterprise', 'Better connections between producers and markets', 'Public participation and accountable county spending'], tag: 'Progress we can share' },
]
const activePriority = computed(() => priorities[selectedPriority.value])
function movePriority(index, direction) {
  selectedPriority.value = (index + direction + priorities.length) % priorities.length
  nextTick(() => document.getElementById(`priority-tab-${selectedPriority.value}`)?.focus())
}
const gallery = [
  { src: '/images/candidate-community.jpeg', width: 480, height: 640, alt: 'Arthur Amug Osiya smiling while holding a microphone at a gathering', label: 'People at the heart', caption: 'A campaign vision rooted in connection.', category: 'Community' },
  { src: '/images/candidate-address.jpeg', width: 679, height: 452, alt: 'Arthur Amug Osiya delivering an outdoor address', label: 'A shared direction', caption: 'Space for ideas. A voice for everyone.', category: 'Community' },
  { src: '/images/candidate-conversation.jpeg', width: 606, height: 506, alt: 'Arthur Amug Osiya speaking during a seated discussion', label: 'Ideas into possibility', caption: 'A conversation about the future.', category: 'Leadership' },
  { src: '/images/candidate-leadership.webp', width: 960, height: 640, alt: 'Arthur Amug Osiya addressing an indoor gathering from a lectern', label: 'Purpose in every conversation', caption: 'The person behind the proposed vision.', category: 'Leadership' },
  { src: '/images/candidate-speaking.jpeg', width: 590, height: 520, alt: 'Arthur Amug Osiya speaking into a microphone beside flags', label: 'A voice for the future', caption: 'Putting shared aspirations into words.', category: 'Leadership' },
]
const filteredGallery = computed(() => galleryFilter.value === 'All moments' ? gallery : gallery.filter(photo => photo.category === galleryFilter.value))
const visibleGallery = computed(() => filteredGallery.value.slice(0, visiblePhotoCount.value))
function filterGallery(filter) { galleryFilter.value = filter; visiblePhotoCount.value = 3 }
function openPhoto(photo) { galleryIndex.value = gallery.indexOf(photo) }
function revealPhotos() {
  const firstNewPhoto = visiblePhotoCount.value
  visiblePhotoCount.value += 3
  nextTick(() => document.querySelectorAll('.gallery-card')[firstNewPhoto]?.focus())
}
const closeMenu = () => { menuOpen.value = false }
const openJoin = () => { closeMenu(); joinOpen.value = true }
const closeGallery = () => { galleryIndex.value = null }
const changePhoto = (direction) => { galleryIndex.value = (galleryIndex.value + direction + gallery.length) % gallery.length }
let galleryTrigger
let galleryOverflow
watch(galleryIndex, async (value, previous) => {
  if (value !== null && previous === null) {
    galleryTrigger = document.activeElement
    galleryOverflow = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    await nextTick()
    document.querySelector('.lightbox-close')?.focus()
  } else if (value === null) {
    document.body.style.overflow = galleryOverflow
    galleryTrigger?.focus()
  }
})
const handleKey = (event) => {
  if (event.key === 'Escape') { closeMenu(); closeGallery() }
  if (galleryIndex.value !== null && event.key === 'ArrowRight') changePhoto(1)
  if (galleryIndex.value !== null && event.key === 'ArrowLeft') changePhoto(-1)
  if (galleryIndex.value !== null && event.key === 'Tab') {
    const controls = document.querySelectorAll('.lightbox button')
    const first = controls[0]; const last = controls[controls.length - 1]
    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus() }
    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus() }
  }
}
let sectionObserver
function updateProgress() {
  const available = document.documentElement.scrollHeight - window.innerHeight
  scrollProgress.value = available > 0 ? Math.min(100, Math.max(0, window.scrollY / available * 100)) : 0
}
onMounted(() => {
  document.addEventListener('keydown', handleKey)
  window.addEventListener('scroll', updateProgress, { passive: true })
  if ('IntersectionObserver' in window) {
    sectionObserver = new window.IntersectionObserver(entries => {
      entries.forEach(entry => { if (entry.isIntersecting) activeSection.value = entry.target.id })
    }, { rootMargin: '-20% 0px -55% 0px' })
    document.querySelectorAll('main section[id]').forEach(section => sectionObserver.observe(section))
  }
})
onBeforeUnmount(() => {
  document.removeEventListener('keydown', handleKey)
  window.removeEventListener('scroll', updateProgress)
  sectionObserver?.disconnect()
})
</script>

<template>
  <a class="skip-link" href="#main">Skip to content</a>
  <div class="announcement"><span class="live-dot"></span> A new chapter for Busia. A future we build together. <a href="#vision">Discover the vision <ArrowUpRight :size="13" /></a></div>
  <header class="site-header">
    <div class="reading-progress" :style="{ width: `${scrollProgress}%` }" aria-hidden="true"></div>
    <div class="container nav-wrap">
      <a class="brand" href="#home" aria-label="Busia 2027 home" @click="closeMenu"><span class="brand-mark">b<span>✳</span></span><span class="brand-word">ARTHUR<span>AMUG OSIYA · 2027</span></span></a>
      <nav class="desktop-nav" aria-label="Main navigation"><a href="#about" :aria-current="activeSection === 'about' ? 'location' : undefined">The candidate</a><a href="#vision" :aria-current="activeSection === 'vision' ? 'location' : undefined">Our vision</a><a href="#moments" :aria-current="activeSection === 'moments' ? 'location' : undefined">In the community</a><a href="#questions" :aria-current="activeSection === 'questions' ? 'location' : undefined">Q&A</a></nav>
      <button class="button nav-cta" @click="openJoin">Get involved <ArrowUpRight :size="17" /></button>
      <button class="menu-toggle" :aria-expanded="menuOpen" aria-controls="mobile-nav" :aria-label="menuOpen ? 'Close menu' : 'Open menu'" @click="menuOpen = !menuOpen"><X v-if="menuOpen" /><Menu v-else /></button>
    </div>
    <nav v-if="menuOpen" id="mobile-nav" class="mobile-nav" aria-label="Mobile navigation"><a href="#about" @click="closeMenu">The candidate <ArrowUpRight :size="18" /></a><a href="#vision" @click="closeMenu">Our vision <ArrowUpRight :size="18" /></a><a href="#moments" @click="closeMenu">In the community <ArrowUpRight :size="18" /></a><a href="#questions" @click="closeMenu">Questions & answers <ArrowUpRight :size="18" /></a><button class="button" @click="openJoin">Get involved <ArrowUpRight :size="18" /></button></nav>
  </header>

  <main id="main">
    <section id="home" class="hero hero-expedition section-anchor" aria-labelledby="hero-title">
      <div class="campaign-backdrop"><img class="campaign-scene" src="/images/candidate-leadership.webp" alt="Arthur Amug Osiya addressing an indoor gathering from a lectern" width="960" height="640" fetchpriority="high" decoding="async" /></div>
      <div class="campaign-shade" aria-hidden="true"></div>
      <div class="campaign-glow" aria-hidden="true"></div>
      <div class="container campaign-hero-content">
        <div class="eyebrow campaign-pill"><span class="live-dot"></span> ARTHUR AMUG OSIYA · BUSIA GOVERNOR 2027</div>
        <h1 id="hero-title"><span class="campaign-heading">A stronger Busia.</span><span class="campaign-heading-accent">Built together.</span></h1>
        <p class="hero-description">Our home. Our potential. Our shared future. A people-first vision for a county where everyone has the chance to move forward.</p>
        <div class="hero-actions"><a class="button campaign-primary" href="#vision">Explore our vision <ArrowUpRight :size="18" /></a><button class="button campaign-secondary" @click="openJoin">Be part of the journey <ArrowUpRight :size="18" /></button></div>
      </div>
      <div class="container campaign-hero-footer"><a class="campaign-scroll" href="#about">Discover the person behind the vision <span><ArrowDown :size="16" /></span></a></div>
      <div class="campaign-side-note" aria-hidden="true"><MapPin :size="13" /> BUSIA COUNTY, KENYA</div>
    </section>

    <div class="values-strip"><div class="container"><span>People first.</span><span class="strip-star">✳</span><span>Opportunity for all.</span><span class="strip-star">✳</span><span>Progress, together.</span><span class="strip-star">✳</span><span class="strip-kiswahili">Busia ni yetu.</span></div></div>

    <section id="about" class="about section-anchor section-space">
      <div class="container about-grid">
        <div class="about-visual"><div class="about-image"><img src="/images/candidate-community.jpeg" width="480" height="640" alt="Arthur Amug Osiya smiling at a community gathering" loading="lazy" decoding="async" /></div><div class="image-note"><span class="note-star">✳</span><span>A shared home.<br /><strong>A shared responsibility.</strong></span></div><span class="vertical-label">THE PEOPLE ARE THE PURPOSE</span></div>
        <div class="about-copy"><div class="eyebrow">01 / MEET ARTHUR AMUG OSIYA</div><h2>Arthur Amug Osiya.<br /><span class="serif-word">A shared vision.</span></h2><p class="intro-copy">Because a better future begins with the people who call Busia home.</p><p>From the farmer and the small business owner to the student imagining what comes next, every voice deserves a place in the conversation.</p><p>This is a vision built around everyday aspirations: a dignified livelihood, care when it matters, and opportunity that reaches further.</p><a class="text-link underlined" href="#vision">Discover the priorities <ArrowUpRight :size="19" /></a><div class="about-signoff"><span class="handwritten">For our home. For our future.</span><span>ARTHUR AMUG OSIYA · BUSIA 2027</span></div></div>
      </div>
    </section>

    <section id="vision" class="vision section-space section-anchor">
      <div class="container"><div class="section-heading"><div><div class="eyebrow">02 / A PEOPLE-FIRST AGENDA</div><h2>Big possibilities.<br /><span class="serif-word">Everyday priorities.</span></h2></div><p>A proposed vision focused on what matters<br class="desktop-break" /> to our families, our livelihoods and our future.</p></div>
        <div class="priorities-layout"><div class="priority-tabs" role="tablist" aria-label="Proposed campaign priorities" aria-orientation="vertical"><button v-for="(priority, index) in priorities" :id="`priority-tab-${index}`" :key="priority.name" role="tab" :aria-selected="selectedPriority === index" :aria-controls="`priority-panel-${index}`" :tabindex="selectedPriority === index ? 0 : -1" :class="['priority-tab', { active: selectedPriority === index }]" @click="selectedPriority = index" @keydown.down.prevent="movePriority(index, 1)" @keydown.up.prevent="movePriority(index, -1)"><span class="priority-icon"><component :is="priority.icon" :size="23" :stroke-width="1.5" /></span><span class="priority-label"><strong>{{ priority.name }}</strong><small>{{ priority.short }}</small></span><Minus v-if="selectedPriority === index" :size="18" /><Plus v-else :size="18" /></button></div>
        <div :id="`priority-panel-${selectedPriority}`" class="priority-panel" role="tabpanel" :aria-labelledby="`priority-tab-${selectedPriority}`" tabindex="0"><div class="panel-top"><span class="pill"><span class="live-dot"></span>{{ activePriority.tag }}</span><span class="panel-number">0{{ selectedPriority + 1 }}</span></div><h3>{{ activePriority.title }}</h3><p>{{ activePriority.description }}</p><ul><li v-for="point in activePriority.points" :key="point"><span><Check :size="13" /></span>{{ point }}</li></ul><div class="panel-bottom"><span>THE PROPOSED 2027 AGENDA</span><component :is="activePriority.icon" :size="33" :stroke-width="1" /></div></div></div>
      </div>
    </section>

    <section class="statement">
      <div class="container statement-inner">
        <div class="statement-belief">
          <span class="statement-star" aria-hidden="true">✳</span>
          <div>
            <div class="eyebrow">OUR SHARED BELIEF</div>
            <div class="statement-copy">Progress means more<br />when it <span>includes everyone.</span></div>
          </div>
        </div>
        <div class="statement-side">ONE COUNTY.<br />A WORLD OF POSSIBILITY.</div>
      </div>
    </section>

    <section id="moments" class="moments section-space section-anchor">
      <div class="container">
        <div class="section-heading"><div><div class="eyebrow">03 / PEOPLE. PURPOSE. CONNECTION.</div><h2>A vision with<br /><span class="serif-word">a human face.</span></h2></div><p>Explore the photographs, discover the person,<br class="desktop-break" /> and see the connections behind the vision.</p></div>
        <div class="gallery-toolbar"><div class="gallery-filters" role="group" aria-label="Filter campaign photographs"><button v-for="filter in galleryFilters" :key="filter" :aria-pressed="galleryFilter === filter" :class="{ active: galleryFilter === filter }" @click="filterGallery(filter)">{{ filter }}<span>{{ filter === 'All moments' ? gallery.length : gallery.filter(photo => photo.category === filter).length }}</span></button></div><span class="gallery-results" role="status">{{ filteredGallery.length }} photographs</span></div>
        <div class="gallery-grid"><button v-for="(photo, index) in visibleGallery" :key="photo.src" class="gallery-card" @click="openPhoto(photo)" :aria-label="`View photo: ${photo.label}`"><div class="gallery-image"><img :src="photo.src" :alt="photo.alt" :width="photo.width" :height="photo.height" :style="{ maxWidth: `${photo.width}px`, maxHeight: `${photo.height}px` }" loading="lazy" decoding="async" /><span class="gallery-expand"><ArrowUpRight :size="22" /></span><span class="gallery-count">{{ photo.category.toUpperCase() }} / {{ String(index + 1).padStart(2, '0') }}</span></div><div class="gallery-info"><h3>{{ photo.label }}</h3><p>{{ photo.caption }}</p></div></button></div>
        <div v-if="visiblePhotoCount < filteredGallery.length" class="gallery-more"><button class="button button-outline" @click="revealPhotos">More moments <Plus :size="18" /></button><span>{{ visibleGallery.length }} of {{ filteredGallery.length }} photographs</span></div>
        <div class="gallery-foot"><span>Different conversations. One shared future.</span><span>BUSIA, TOGETHER <span class="foot-star">✳</span></span></div>
      </div>
    </section>

    <section id="questions" class="questions section-space section-anchor"><div class="container questions-grid"><div class="questions-intro"><div class="eyebrow">04 / GET TO KNOW THE VISION</div><h2>A little more<br /><span class="serif-word">clarity.</span></h2><p>Get to know the proposed agenda and find your way into the conversation.</p><div class="questions-photo"><img src="/images/candidate-speaking.jpeg" width="590" height="520" alt="Arthur Amug Osiya speaking into a microphone beside flags" loading="lazy" decoding="async" /><span>ARTHUR AMUG OSIYA<br /><strong>BUSIA 2027</strong></span><span class="questions-photo-star" aria-hidden="true">✳</span></div></div><div class="faq-list"><div v-for="(item, index) in questions" :key="item.question" :class="['faq-item', { open: openQuestion === index }]"><h3><button :id="`question-${index}`" :aria-expanded="openQuestion === index" :aria-controls="`answer-${index}`" @click="openQuestion = openQuestion === index ? null : index"><span class="faq-number">0{{ index + 1 }}</span><span>{{ item.question }}</span><Minus v-if="openQuestion === index" :size="19" /><Plus v-else :size="19" /></button></h3><div :id="`answer-${index}`" v-show="openQuestion === index" role="region" :aria-labelledby="`question-${index}`"><p>{{ item.answer }}</p><button v-if="index === 3" class="text-link" @click="openJoin">Prepare your interest <ArrowUpRight :size="17" /></button></div></div><div class="faq-foot"><span class="small-sun" aria-hidden="true">✳</span><p>Our strongest ideas start with<br /><strong>an open conversation.</strong></p></div></div></div></section>

    <section id="get-involved" class="join-section section-anchor"><div class="container join-inner"><div class="join-copy"><div class="eyebrow">THE NEXT CHAPTER NEEDS YOU</div><h2>Our home.<br />Our future.<br /><span>Our move.</span><span class="join-star">✳</span></h2><p>A stronger Busia begins with all of us.<br />Bring your ideas, your energy and your voice.</p><button class="button button-cream" @click="openJoin">Be part of the journey <ArrowUpRight :size="20" /></button></div><div class="join-art" aria-hidden="true"><div class="art-ring ring-one"></div><div class="art-ring ring-two"></div><div class="art-ring ring-three"></div><span class="art-year">20<br />27<span>BUILT TOGETHER.</span></span><span class="art-flower">✳</span><span class="art-word">BUSIA NI YETU.</span></div></div></section>
  </main>

  <footer><div class="container"><div class="footer-top"><a class="brand" href="#home" aria-label="Busia 2027 home"><span class="brand-mark">b<span>✳</span></span><span class="brand-word">ARTHUR<span>AMUG OSIYA · 2027</span></span></a><p>A stronger Busia.<br />Built together.</p><nav aria-label="Footer navigation"><a href="#about">The candidate</a><a href="#vision">Our vision</a><button @click="openJoin">Get involved <ArrowUpRight :size="14" /></button></nav><a class="back-top" href="#home">Back to top <ArrowUpRight :size="18" /></a></div><div class="footer-bottom"><span>© {{ new Date().getFullYear() }} Busia 2027 · Website concept</span><span>Draft campaign priorities — subject to candidate approval.</span><span>Made for a shared future <Heart :size="12" /></span></div></div></footer>

  <InterestDialog v-if="joinOpen" @close="joinOpen = false" />
  <div v-if="galleryIndex !== null" class="lightbox" role="dialog" aria-modal="true" aria-label="Campaign photo gallery" @click.self="closeGallery"><button class="lightbox-close" @click="closeGallery" aria-label="Close gallery" autofocus><X /></button><button class="lightbox-prev" @click="changePhoto(-1)" aria-label="Previous photo"><ArrowRight /></button><figure><img :src="gallery[galleryIndex].src" :alt="gallery[galleryIndex].alt" :width="gallery[galleryIndex].width" :height="gallery[galleryIndex].height" decoding="async" /><figcaption>{{ gallery[galleryIndex].label }} <span>{{ galleryIndex + 1 }} / {{ gallery.length }}</span></figcaption><div class="lightbox-thumbs" role="group" aria-label="Choose gallery photo"><button v-for="(photo, index) in gallery" :key="photo.src" :aria-label="`Open photo ${index + 1}: ${photo.label}`" :aria-pressed="galleryIndex === index" @click="galleryIndex = index"><img :src="photo.src" alt="" loading="lazy" /></button></div></figure><button class="lightbox-next" @click="changePhoto(1)" aria-label="Next photo"><ArrowRight /></button></div>
</template>
