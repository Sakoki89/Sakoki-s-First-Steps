const els = document.querySelectorAll('.reveal');
if (!els.length) {
  console.warn('Keine .reveal-Elemente gefunden.');
}

const io = new IntersectionObserver((entries, obs) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('in-view');
      obs.unobserve(entry.target); 
    }
  });
}, { threshold: 0.1, rootMargin: '0px 0px -5% 0px' });

els.forEach(el => io.observe(el));