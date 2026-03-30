/**
 * UsafiKonect - GSAP Animations
 * Hero, scroll-triggered reveals, parallax, counters
 */

document.addEventListener('DOMContentLoaded', () => {
    // Register ScrollTrigger
    if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') return;
    gsap.registerPlugin(ScrollTrigger);

    // Hero section animations
    const heroTimeline = gsap.timeline({ defaults: { ease: 'power3.out' } });
    
    if (document.querySelector('.hero-badge')) {
        heroTimeline
            .from('.hero-badge', { y: -30, opacity: 0, duration: 0.6 })
            .from('.hero-title', { y: 40, opacity: 0, duration: 0.8 }, '-=0.3')
            .from('.hero-subtitle', { y: 30, opacity: 0, duration: 0.6 }, '-=0.4')
            .from('.hero-cta', { y: 20, opacity: 0, scale: 0.95, duration: 0.5 }, '-=0.3')
            .from('.hero-stats > div', { y: 20, opacity: 0, stagger: 0.15, duration: 0.5 }, '-=0.2')
            .from('.hero-image', { x: 60, opacity: 0, duration: 0.8 }, '-=0.8')
            .from('.hero-float', { y: 20, opacity: 0, stagger: 0.2, duration: 0.6 }, '-=0.5');
    }

    // Scroll-triggered fade-in sections
    gsap.utils.toArray('.reveal-up').forEach(el => {
        gsap.from(el, {
            scrollTrigger: { trigger: el, start: 'top 85%', toggleActions: 'play none none none' },
            y: 40, opacity: 0, duration: 0.7, ease: 'power2.out'
        });
    });

    gsap.utils.toArray('.reveal-left').forEach(el => {
        gsap.from(el, {
            scrollTrigger: { trigger: el, start: 'top 85%' },
            x: -50, opacity: 0, duration: 0.7
        });
    });

    gsap.utils.toArray('.reveal-right').forEach(el => {
        gsap.from(el, {
            scrollTrigger: { trigger: el, start: 'top 85%' },
            x: 50, opacity: 0, duration: 0.7
        });
    });

    // Staggered cards
    gsap.utils.toArray('.stagger-container').forEach(container => {
        gsap.from(container.querySelectorAll('.stagger-item'), {
            scrollTrigger: { trigger: container, start: 'top 80%' },
            y: 30, opacity: 0, stagger: 0.15, duration: 0.6, ease: 'power2.out'
        });
    });

    // Counter animation on scroll
    gsap.utils.toArray('.count-up').forEach(el => {
        const target = parseInt(el.dataset.count || el.textContent);
        ScrollTrigger.create({
            trigger: el,
            start: 'top 90%',
            once: true,
            onEnter: () => UsafiKonect.animateCounter(el, target, 2000)
        });
    });

    // Parallax effect on hero decorative elements
    gsap.utils.toArray('.parallax').forEach(el => {
        const speed = parseFloat(el.dataset.speed || 0.5);
        gsap.to(el, {
            scrollTrigger: { trigger: el, start: 'top bottom', end: 'bottom top', scrub: true },
            y: () => -100 * speed
        });
    });

    // Navbar shadow on scroll
    ScrollTrigger.create({
        start: 'top -10',
        onUpdate: self => {
            const nav = document.getElementById('main-nav');
            if (nav) {
                nav.classList.toggle('shadow-md', self.direction === 1 && self.scroll() > 10);
                nav.classList.toggle('shadow-none', self.scroll() <= 10);
            }
        }
    });

    // Section title reveal
    gsap.utils.toArray('.section-title').forEach(el => {
        gsap.from(el, {
            scrollTrigger: { trigger: el, start: 'top 85%' },
            y: 20, opacity: 0, duration: 0.6
        });
    });

    // Scale-in for images/icons
    gsap.utils.toArray('.scale-in').forEach(el => {
        gsap.from(el, {
            scrollTrigger: { trigger: el, start: 'top 85%' },
            scale: 0.8, opacity: 0, duration: 0.5
        });
    });
});
