document.addEventListener('DOMContentLoaded', () => {
  // Find all instances of the carousel on the page to support multiple shortcodes
  document.querySelectorAll('.mag-wrap.custom-premium-carousel').forEach(wrapper => {
    const track = wrapper.querySelector('.carousel-track');
    if (!track) return;
    
    const slides = Array.from(track.children);
    const dotsContainer = wrapper.querySelector('.dots');
    const prevBtn = wrapper.querySelector('.arrow.left');
    const nextBtn = wrapper.querySelector('.arrow.right');

    const totalOriginals = parseInt(wrapper.getAttribute('data-total-originals'), 10);
    if (isNaN(totalOriginals) || totalOriginals === 0) return;

    let current = 2; // Index 2 is the first original item
    const slideWidth = 330;
    
    let isAnimating = false;
    let autoPlay;
    let isHovered = false;

    // Create dots
    for(let i = 0; i < totalOriginals; i++){
      const dot = document.createElement('span');
      dot.className = 'dot' + (i === 0 ? ' active' : '');
      dot.onclick = () => goToDot(i); 
      dotsContainer.appendChild(dot);
    }

    function setSlidesTransition(duration) {
      slides.forEach(s => {
        if (duration === 'none') {
          s.style.transition = 'none';
        } else {
          s.style.transition = `all ${duration} cubic-bezier(0.25, 1, 0.5, 1)`;
        }
      });
    }

    function getCurrentDotIndex() {
      let dotIndex = current - 2;
      if (dotIndex < 0) dotIndex = totalOriginals + (dotIndex % totalOriginals);
      return dotIndex % totalOriginals;
    }

    function update() {
      slides.forEach((s, i) => {
        s.classList.toggle('active', i === current);
      });

      const activeDot = getCurrentDotIndex();
      dotsContainer.querySelectorAll('.dot').forEach((d, i) => {
        d.classList.toggle('active', i === activeDot);
      });

      const carouselWidth = track.parentElement.offsetWidth;
      const offset = (carouselWidth / 2) - (current * slideWidth) - (slideWidth / 2);
      track.style.transform = `translateX(${offset}px)`;
    }

    function goToDot(targetDotIndex) {
      if (isAnimating) return;
      
      // Normalize targetDotIndex (for next/prev wrap arounds)
      targetDotIndex = (targetDotIndex + totalOriginals) % totalOriginals;
      const currentDotIndex = getCurrentDotIndex();

      if (currentDotIndex === targetDotIndex) return;

      startAutoPlay(); // Reset autoplay timer on manual action

      let diff = targetDotIndex - currentDotIndex;

      // Shortest path calculation for a circular carousel
      if (diff > totalOriginals / 2) {
        diff -= totalOriginals;
      } else if (diff < -totalOriginals / 2) {
        diff += totalOriginals;
      }

      isAnimating = true;
      track.style.transition = "transform 0.6s cubic-bezier(0.25, 1, 0.5, 1)";
      setSlidesTransition("0.6s");
      
      current += diff;
      update();

      // After animation, check if we landed on a clone and secretly snap back
      setTimeout(() => {
        let needsSnap = false;
        if (current <= 1) { 
           current += totalOriginals; 
           needsSnap = true;
        } else if (current >= totalOriginals + 2) { 
           current -= totalOriginals; 
           needsSnap = true;
        }

        if (needsSnap) {
          track.style.transition = "none";
          setSlidesTransition("none");
          update();
          void track.offsetWidth; // Force layout recalculation
        }
        isAnimating = false;
      }, 600);
    }

    function next() {
       goToDot(getCurrentDotIndex() + 1);
    }

    function prev() {
       goToDot(getCurrentDotIndex() - 1);
    }

    function startAutoPlay() {
      clearInterval(autoPlay);
      if (!isHovered) {
        autoPlay = setInterval(next, 3000);
      }
    }

    function stopAutoPlay() {
      clearInterval(autoPlay);
    }

    if(nextBtn) nextBtn.onclick = next;
    if(prevBtn) prevBtn.onclick = prev;

    // Pause autoplay on hover
    wrapper.querySelector('.carousel').addEventListener('mouseenter', () => {
      isHovered = true;
      stopAutoPlay();
    });
    
    wrapper.querySelector('.carousel').addEventListener('mouseleave', () => {
      isHovered = false;
      startAutoPlay();
    });

    window.addEventListener('resize', update);

    // Initial setup
    update();
    startAutoPlay();
  });
});