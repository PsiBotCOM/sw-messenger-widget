(function () {
    'use strict';

    var cfg = window.SW_CONFIG || {};
    var interval    = cfg.carousel_interval || 1500;
    var animation   = cfg.animation || 'fade';
    var bubbleOn    = cfg.bubble_enabled || false;
    var bubbleDelay = cfg.bubble_delay || 3000;

    var widget  = document.getElementById('sw-widget');
    var toggle  = document.getElementById('sw-toggle');
    var list    = document.getElementById('sw-list');
    var bubble  = document.getElementById('sw-bubble');
    var slides  = widget ? widget.querySelectorAll('.sw-slide') : [];

    if (!widget || !toggle || !list) return;

    /* ── Carousel ── */
    var currentSlide = 0;
    var carouselTimer = null;

    if (animation === 'slide') {
        toggle.classList.add('sw-anim-slide');
    }

    function nextSlide() {
        if (slides.length < 2) return;
        slides[currentSlide].classList.remove('active');
        currentSlide = (currentSlide + 1) % slides.length;
        slides[currentSlide].classList.add('active');
    }

    function startCarousel() {
        if (slides.length < 2) return;
        carouselTimer = setInterval(nextSlide, interval);
    }

    function stopCarousel() {
        clearInterval(carouselTimer);
    }

    startCarousel();

    /* Pause on hover */
    toggle.addEventListener('mouseenter', stopCarousel);
    toggle.addEventListener('mouseleave', function () {
        if (!toggle.classList.contains('is-open')) startCarousel();
    });

    /* ── Open / Close ── */
    var isOpen = false;

    function openWidget() {
        isOpen = true;
        toggle.classList.add('is-open');
        toggle.setAttribute('aria-expanded', 'true');
        list.classList.add('is-open');
        list.setAttribute('aria-hidden', 'false');
        stopCarousel();

        /* stagger items */
        var items = list.querySelectorAll('.sw-item');
        items.forEach(function (item, i) {
            item.style.setProperty('--sw-delay', (i * 55) + 'ms');
        });

        /* hide bubble */
        if (bubble) bubble.style.display = 'none';
    }

    function closeWidget() {
        isOpen = false;
        toggle.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        list.classList.remove('is-open');
        list.setAttribute('aria-hidden', 'true');
        startCarousel();
    }

    toggle.addEventListener('click', function () {
        if (isOpen) { closeWidget(); } else { openWidget(); }
    });

    /* Close when clicking outside */
    document.addEventListener('click', function (e) {
        if (isOpen && !widget.contains(e.target)) {
            closeWidget();
        }
    });

    /* Close on Escape */
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && isOpen) closeWidget();
    });

    /* ── Bubble ── */
    if (bubble && bubbleOn) {
        var BUBBLE_KEY = 'sw_bubble_closed';

        if (!localStorage.getItem(BUBBLE_KEY)) {
            setTimeout(function () {
                if (!isOpen) {
                    bubble.style.display = '';
                }
            }, bubbleDelay);
        }

        var closeBtn = bubble.querySelector('.sw-bubble-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                bubble.style.display = 'none';
                try { localStorage.setItem(BUBBLE_KEY, '1'); } catch(e){}
            });
        }
    }
})();
