document.addEventListener('DOMContentLoaded', function() {

    // ===== PRELOADER =====
    const preloader = document.getElementById('preloader');
    if (preloader) {
        const hidePreloader = () => {
            preloader.classList.add('hidden');
            document.body.classList.remove('preloader-active');
            setTimeout(() => { if (preloader.parentNode) preloader.remove(); }, 600);
        };
        window.addEventListener('load', () => setTimeout(hidePreloader, 2400));
        setTimeout(hidePreloader, 5000);
    }

    // ===== PARTICLE CANVAS =====
    const canvas = document.getElementById('particleCanvas');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        let particles = [];

        function resizeCanvas() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }

        function createParticles() {
            particles = [];
            const count = Math.floor((canvas.width * canvas.height) / 15000);
            for (let i = 0; i < count; i++) {
                particles.push({
                    x: Math.random() * canvas.width,
                    y: Math.random() * canvas.height,
                    size: Math.random() * 1.5 + 0.5,
                    speedX: (Math.random() - 0.5) * 0.3,
                    speedY: (Math.random() - 0.5) * 0.3,
                    opacity: Math.random() * 0.4 + 0.1
                });
            }
        }

        function drawParticles() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            particles.forEach(p => {
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(37, 99, 235, ${p.opacity})`;
                ctx.fill();
                p.x += p.speedX;
                p.y += p.speedY;
                if (p.x < 0) p.x = canvas.width;
                if (p.x > canvas.width) p.x = 0;
                if (p.y < 0) p.y = canvas.height;
                if (p.y > canvas.height) p.y = 0;
            });

            for (let i = 0; i < particles.length; i++) {
                for (let j = i + 1; j < particles.length; j++) {
                    const dx = particles[i].x - particles[j].x;
                    const dy = particles[i].y - particles[j].y;
                    const dist = Math.sqrt(dx * dx + dy * dy);
                    if (dist < 100) {
                        ctx.beginPath();
                        ctx.strokeStyle = `rgba(37, 99, 235, ${0.06 * (1 - dist / 100)})`;
                        ctx.lineWidth = 0.5;
                        ctx.moveTo(particles[i].x, particles[i].y);
                        ctx.lineTo(particles[j].x, particles[j].y);
                        ctx.stroke();
                    }
                }
            }
            requestAnimationFrame(drawParticles);
        }

        resizeCanvas();
        createParticles();
        drawParticles();
        window.addEventListener('resize', () => { resizeCanvas(); createParticles(); });
    }

    // ===== HEADER SCROLL =====
    const header = document.getElementById('header');
    if (header) {
        window.addEventListener('scroll', () => {
            header.classList.toggle('scrolled', window.scrollY > 50);
        });
    }

    // ===== MOBILE MENU =====
    const mobileToggle = document.getElementById('mobileToggle');
    const mainNav = document.getElementById('mainNav');
    if (mobileToggle && mainNav) {
        mobileToggle.addEventListener('click', () => {
            mobileToggle.classList.toggle('active');
            mainNav.classList.toggle('open');
        });
        mainNav.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                mobileToggle.classList.remove('active');
                mainNav.classList.remove('open');
            });
        });
    }

    // ===== SMOOTH SCROLLING =====
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const target = this.getAttribute('href');
            if (target === '#') return;
            const el = document.querySelector(target);
            if (el) {
                e.preventDefault();
                const offset = header ? header.offsetHeight + 20 : 80;
                const top = el.getBoundingClientRect().top + window.pageYOffset - offset;
                window.scrollTo({ top, behavior: 'smooth' });
            }
        });
    });

    // ===== SCROLL ANIMATIONS =====
    const animElements = document.querySelectorAll('.animate-on-scroll');
    if (animElements.length > 0) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animated');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
        animElements.forEach(el => observer.observe(el));
    }

    // ===== HERO COUNTERS =====
    const statValues = document.querySelectorAll('.hero-stat-value');
    if (statValues.length > 0) {
        let counted = false;
        const heroSection = document.getElementById('hero');

        function animateCounters() {
            if (counted) return;
            counted = true;
            statValues.forEach(el => {
                const target = parseInt(el.dataset.target) || 0;
                const duration = 2000;
                const step = target / (duration / 16);
                let current = 0;
                const timer = setInterval(() => {
                    current += step;
                    if (current >= target) {
                        current = target;
                        clearInterval(timer);
                    }
                    el.textContent = Math.floor(current).toLocaleString();
                }, 16);
            });
        }

        if (heroSection) {
            const counterObserver = new IntersectionObserver((entries) => {
                if (entries[0].isIntersecting) {
                    animateCounters();
                    counterObserver.unobserve(heroSection);
                }
            }, { threshold: 0.3 });
            counterObserver.observe(heroSection);
        }
    }

    // ===== SCROLL TO TOP =====
    const scrollTopBtn = document.getElementById('scrollTop');
    if (scrollTopBtn) {
        window.addEventListener('scroll', () => {
            scrollTopBtn.classList.toggle('visible', window.scrollY > 500);
        });
        scrollTopBtn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // ===== SOFTWARE TABS + PAGINATION =====
    const GAMES_PER_PAGE = (window.SITE_CONFIG && window.SITE_CONFIG.gamesPerPage) ? window.SITE_CONFIG.gamesPerPage : 12;

    const softwareTabs = document.querySelectorAll('.software-tab');
    const softwarePanels = document.querySelectorAll('.software-panel[data-panel]');

    function paginatePanel(panel) {
        const grid = panel.querySelector('.software-grid');
        const paginationContainer = panel.querySelector('.pagination');
        if (!grid || !paginationContainer) return;

        const cards = Array.from(grid.querySelectorAll('.software-card'));
        if (cards.length === 0) return;

        const totalPages = Math.ceil(cards.length / GAMES_PER_PAGE);

        function showPage(page) {
            cards.forEach((card, i) => {
                const start = (page - 1) * GAMES_PER_PAGE;
                const end = start + GAMES_PER_PAGE;
                card.style.display = (i >= start && i < end) ? '' : 'none';
            });

            paginationContainer.innerHTML = '';
            if (totalPages <= 1) return;

            for (let i = 1; i <= totalPages; i++) {
                const btn = document.createElement('button');
                btn.className = 'pagination-btn' + (i === page ? ' active' : '');
                btn.textContent = i;
                btn.addEventListener('click', () => {
                    showPage(i);
                    const section = document.getElementById('software');
                    if (section) {
                        const offset = header ? header.offsetHeight + 20 : 80;
                        window.scrollTo({ top: section.getBoundingClientRect().top + window.pageYOffset - offset, behavior: 'smooth' });
                    }
                });
                paginationContainer.appendChild(btn);
            }
        }

        showPage(1);
    }

    softwareTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const target = this.dataset.tab;

            const searchPanel = document.getElementById('searchResultsPanel');
            if (searchPanel) searchPanel.style.display = 'none';

            const sInput = document.getElementById('softwareSearch');
            const sClear = document.getElementById('softwareSearchClear');
            if (sInput) sInput.value = '';
            if (sClear) sClear.style.display = 'none';

            softwareTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            softwarePanels.forEach(p => {
                if (p.dataset.panel === target) {
                    p.classList.add('active');
                    p.style.display = '';
                    paginatePanel(p);
                } else {
                    p.classList.remove('active');
                    p.style.display = 'none';
                }
            });
        });
    });

    const activePanel = document.querySelector('.software-panel.active');
    if (activePanel) paginatePanel(activePanel);

    // ===== SOFTWARE SEARCH =====
    const searchInput = document.getElementById('softwareSearch');
    const searchClear = document.getElementById('softwareSearchClear');
    const searchResultsPanel = document.getElementById('searchResultsPanel');
    const searchResultsGrid = document.getElementById('searchResultsGrid');
    const searchPagination = document.getElementById('searchPagination');

    if (searchInput && searchResultsPanel && searchResultsGrid) {
        let searchTimeout;

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim().toLowerCase();

            if (searchClear) {
                searchClear.style.display = query.length > 0 ? 'flex' : 'none';
            }

            if (query.length === 0) {
                searchResultsPanel.style.display = 'none';
                searchResultsGrid.innerHTML = '';
                if (searchPagination) searchPagination.innerHTML = '';

                const activeTab = document.querySelector('.software-tab.active');
                if (activeTab) {
                    const target = activeTab.dataset.tab;
                    softwarePanels.forEach(p => {
                        if (p.dataset.panel === target) {
                            p.style.display = '';
                            p.classList.add('active');
                            paginatePanel(p);
                        }
                    });
                } else {
                    softwareTabs.forEach(t => { if (t.dataset.tab === 'all') t.classList.add('active'); });
                    softwarePanels.forEach(p => {
                        if (p.dataset.panel === 'all') { p.style.display = ''; p.classList.add('active'); paginatePanel(p); }
                    });
                }
                return;
            }

            searchTimeout = setTimeout(() => { performSearch(query); }, 200);
        });

        if (searchClear) {
            searchClear.addEventListener('click', () => {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input'));
                searchInput.focus();
            });
        }

        function performSearch(query) {
            softwarePanels.forEach(p => { p.style.display = 'none'; p.classList.remove('active'); });

            const allGrid = document.getElementById('allGamesGrid');
            if (!allGrid) return;

            const allCards = Array.from(allGrid.querySelectorAll('.software-card'));
            const matched = allCards.filter(card => {
                const name = (card.getAttribute('data-game-name') || '').toLowerCase();
                return name.indexOf(query) !== -1;
            });

            searchResultsGrid.innerHTML = '';
            if (searchPagination) searchPagination.innerHTML = '';

            if (matched.length === 0) {
                searchResultsGrid.innerHTML = '<div class="software-empty"><i class="fas fa-search"></i><p>No software found for "' + escapeHtml(query) + '"</p></div>';
            } else {
                matched.forEach(card => {
                    const clone = card.cloneNode(true);
                    clone.removeAttribute('style');
                    clone.style.cursor = 'pointer';
                    const gameId = clone.getAttribute('data-game-id');
                    if (gameId) {
                        clone.addEventListener('click', function(e) {
                            if (e.target.closest('button') || e.target.closest('a')) return;
                            openSoftwareModal(parseInt(gameId));
                        });
                    }
                    searchResultsGrid.appendChild(clone);
                });

                const searchCards = Array.from(searchResultsGrid.querySelectorAll('.software-card'));
                const totalPages = Math.ceil(searchCards.length / GAMES_PER_PAGE);

                function showSearchPage(page) {
                    const start = (page - 1) * GAMES_PER_PAGE;
                    const end = start + GAMES_PER_PAGE;
                    searchCards.forEach((c, i) => { c.style.display = (i >= start && i < end) ? '' : 'none'; });

                    if (searchPagination) {
                        searchPagination.innerHTML = '';
                        if (totalPages > 1) {
                            for (let i = 1; i <= totalPages; i++) {
                                const btn = document.createElement('button');
                                btn.className = 'pagination-btn' + (i === page ? ' active' : '');
                                btn.textContent = i;
                                btn.addEventListener('click', () => {
                                    showSearchPage(i);
                                    const section = document.getElementById('software');
                                    if (section) {
                                        const offset = header ? header.offsetHeight + 20 : 80;
                                        window.scrollTo({ top: section.getBoundingClientRect().top + window.pageYOffset - offset, behavior: 'smooth' });
                                    }
                                });
                                searchPagination.appendChild(btn);
                            }
                        }
                    }
                }
                showSearchPage(1);
            }

            softwareTabs.forEach(t => t.classList.remove('active'));
            searchResultsPanel.style.display = 'block';
        }
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // ===== SOFTWARE DETAIL MODAL =====
    const sdOverlay = document.getElementById('sdOverlay');
    const sdModal = sdOverlay ? sdOverlay.querySelector('.sd-modal') : null;
    const sdBody = sdOverlay ? sdOverlay.querySelector('.sd-body') : null;
    const sdCloseBtn = sdOverlay ? sdOverlay.querySelector('.sd-close') : null;
    let currentSlide = 0;
    let slideshowData = [];
    let modalOpen = false;

    document.querySelectorAll('.software-card').forEach(card => {
        card.style.cursor = 'pointer';
    });

    document.querySelectorAll('.software-grid').forEach(grid => {
        grid.addEventListener('click', function(e) {
            if (e.target.closest('button') || e.target.closest('a.btn')) return;
            const card = e.target.closest('.software-card');
            if (!card) return;
            const gameId = parseInt(card.getAttribute('data-game-id'));
            if (gameId) openSoftwareModal(gameId);
        });
    });

    function openSoftwareModal(gameId) {
        if (!window.GAMES_DATA || !sdOverlay || !sdBody) return;
        const game = window.GAMES_DATA.find(g => g.id == gameId);
        if (!game) return;

        try {
            currentSlide = 0;
            slideshowData = game.screenshots || [];

            const catName = game.category || game.category_name || 'Uncategorized';

            let featuresHtml = '';
            if (game.features) {
                const lines = game.features.split('\n').filter(l => l.trim());
                if (lines.length) {
                    featuresHtml = '<div class="sd-features"><h4><i class="fas fa-check-circle"></i> Key Features</h4><ul>';
                    lines.forEach(l => { featuresHtml += '<li>' + escapeHtml(l.trim()) + '</li>'; });
                    featuresHtml += '</ul></div>';
                }
            }

            let sysreqHtml = '';
            if (game.system_requirements) {
                sysreqHtml = '<div class="sd-sysreq"><h4><i class="fas fa-desktop"></i> System Requirements</h4><p>' + escapeHtml(game.system_requirements).replace(/\n/g, '<br>') + '</p></div>';
            }

            let screensHtml = '';
            if (slideshowData.length > 0) {
                screensHtml = '<div class="sd-screenshots"><h4><i class="fas fa-images"></i> Screenshots</h4>' +
                    '<div class="sd-slideshow">' +
                        '<button class="sd-slide-btn sd-slide-prev" id="sdPrevBtn"><i class="fas fa-chevron-left"></i></button>' +
                        '<div class="sd-slide-container" id="sdSlideContainer">';
                slideshowData.forEach((src, i) => {
                    screensHtml += '<img src="' + escapeHtml(src) + '" class="sd-slide ' + (i === 0 ? 'active' : '') + '" alt="Screenshot ' + (i+1) + '">';
                });
                screensHtml += '</div>' +
                        '<button class="sd-slide-btn sd-slide-next" id="sdNextBtn"><i class="fas fa-chevron-right"></i></button>' +
                    '</div>' +
                    '<div class="sd-slide-dots" id="sdSlideDots">';
                slideshowData.forEach((_, i) => {
                    screensHtml += '<span class="sd-dot ' + (i === 0 ? 'active' : '') + '" data-slide="' + i + '"></span>';
                });
                screensHtml += '</div></div>';
            }

            sdBody.innerHTML =
                '<div class="sd-header-section">' +
                    '<div class="sd-cover">' +
                        (game.image ? '<img src="' + escapeHtml(game.image) + '" alt="' + escapeHtml(game.name) + '">' : '<div class="sd-cover-placeholder"><i class="fas fa-image"></i></div>') +
                    '</div>' +
                    '<div class="sd-info">' +
                        '<h2 class="sd-title">' + escapeHtml(game.name) + '</h2>' +
                        '<div class="sd-meta-row" style="display:flex;gap:10px;flex-wrap:wrap;margin:10px 0;">' +
                            '<span class="sd-meta-tag"><i class="fas fa-folder"></i> ' + escapeHtml(catName) + '</span>' +
                            (game.version ? '<span class="sd-meta-tag"><i class="fas fa-code-branch"></i> ' + escapeHtml(game.version) + '</span>' : '') +
                            (game.developer ? '<span class="sd-meta-tag"><i class="fas fa-building"></i> ' + escapeHtml(game.developer) + '</span>' : '') +
                        '</div>' +
                        (game.description ? '<p class="sd-description">' + escapeHtml(game.description) + '</p>' : '<p class="sd-description sd-empty">No description available yet.</p>') +
                        (game.slug ? '<a href="/software/' + encodeURIComponent(game.slug) + '" class="sd-seo-link" style="display:inline-block;margin-top:10px;color:#a29bfe;text-decoration:none;font-size:0.9rem;"><i class="fas fa-external-link-alt"></i> View full page</a>' : '') +
                    '</div>' +
                '</div>' +
                featuresHtml +
                sysreqHtml +
                screensHtml +
                '<div class="sd-actions" style="text-align:center;margin-top:24px;">' +
                    '<a href="#download" class="sd-download-btn" style="display:inline-block;padding:14px 36px;background:linear-gradient(135deg,#6c5ce7,#a29bfe);color:#fff;border-radius:10px;text-decoration:none;font-weight:600;font-size:1.05rem;transition:all 0.3s;"><i class="fas fa-download"></i> Download Now</a>' +
                '</div>';

            const prevBtn = document.getElementById('sdPrevBtn');
            const nextBtn = document.getElementById('sdNextBtn');
            if (prevBtn) prevBtn.addEventListener('click', function(e) { e.stopPropagation(); slidePrev(); });
            if (nextBtn) nextBtn.addEventListener('click', function(e) { e.stopPropagation(); slideNext(); });
            document.querySelectorAll('#sdSlideDots .sd-dot').forEach(dot => {
                dot.addEventListener('click', function(e) { e.stopPropagation(); goToSlide(parseInt(this.getAttribute('data-slide'))); });
            });

            if (game.slug) {
                history.pushState({ gameId: gameId, modal: true }, game.name, '/software/' + game.slug);
            }

            sdOverlay.setAttribute('style', 'display:flex !important; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.75); z-index:10000; justify-content:center; align-items:center; padding:20px; overflow-y:auto; backdrop-filter:blur(6px);');
            sdOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
            modalOpen = true;

        } catch (err) {
            console.error('Error opening software modal:', err);
            document.body.style.overflow = '';
        }
    }

    function closeSoftwareModal() {
        if (!sdOverlay) return;
        sdOverlay.classList.remove('active');
        sdOverlay.setAttribute('style', 'display:none;');
        document.body.style.overflow = '';
        if (modalOpen && window.location.pathname.startsWith('/software/')) {
            history.pushState({}, document.title, '/');
        }
        modalOpen = false;
    }

    if (sdCloseBtn) sdCloseBtn.addEventListener('click', closeSoftwareModal);

    if (sdOverlay) {
        sdOverlay.addEventListener('click', function(e) {
            if (e.target === sdOverlay) closeSoftwareModal();
        });
    }

    if (sdOverlay) {
        sdOverlay.addEventListener('click', function(e) {
            const dlBtn = e.target.closest('.sd-download-btn');
            if (dlBtn) {
                e.preventDefault();
                closeSoftwareModal();
                const downloadSection = document.getElementById('download');
                if (downloadSection) {
                    downloadSection.scrollIntoView({ behavior: 'smooth' });
                }
            }
        });
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modalOpen) closeSoftwareModal();
    });

    window.addEventListener('popstate', function(e) {
        if (modalOpen) {
            sdOverlay.classList.remove('active');
            sdOverlay.setAttribute('style', 'display:none;');
            document.body.style.overflow = '';
            modalOpen = false;
        }
    });

    function slideNext() {
        if (slideshowData.length === 0) return;
        currentSlide = (currentSlide + 1) % slideshowData.length;
        updateSlide();
    }

    function slidePrev() {
        if (slideshowData.length === 0) return;
        currentSlide = (currentSlide - 1 + slideshowData.length) % slideshowData.length;
        updateSlide();
    }

    function goToSlide(i) {
        currentSlide = i;
        updateSlide();
    }

    function updateSlide() {
        const slides = document.querySelectorAll('#sdSlideContainer .sd-slide');
        const dots = document.querySelectorAll('#sdSlideDots .sd-dot');
        slides.forEach((s, i) => s.classList.toggle('active', i === currentSlide));
        dots.forEach((d, i) => d.classList.toggle('active', i === currentSlide));
    }

    // ===== ARCHIVE PASSWORD MODAL HELPERS =====
    function showArchivePasswordModal() {
        var archivePass = (window.SITE_CONFIG && window.SITE_CONFIG.archivePassword) ? window.SITE_CONFIG.archivePassword : '';
        if (!archivePass) return;
        var modal = document.getElementById('archivePasswordModal');
        var passText = document.getElementById('archivePasswordText');
        if (!modal || !passText) return;
        passText.textContent = archivePass;
        modal.style.display = 'flex';
    }

    window._copyArchivePass = function() {
        var t = document.getElementById('archivePasswordText');
        if (!t) return;
        navigator.clipboard.writeText(t.textContent).then(function() {
            var h = document.getElementById('archiveCopyHint');
            if (h) {
                h.innerHTML = '<i class="fas fa-check-circle" style="color:#00b894;"></i> <span style="color:#00b894;">Password copied!</span>';
                setTimeout(function() { h.innerHTML = '<i class="fas fa-info-circle"></i> Click password to copy'; }, 2000);
            }
            var d = document.getElementById('archivePasswordDisplay');
            if (d) { d.style.borderColor = '#00b894'; setTimeout(function() { d.style.borderColor = 'rgba(108,92,231,0.4)'; }, 1500); }
        }).catch(function() {});
    };

    var archiveOkBtn = document.getElementById('archivePasswordOkBtn');
    if (archiveOkBtn) {
        archiveOkBtn.addEventListener('click', function() {
            var modal = document.getElementById('archivePasswordModal');
            if (modal) modal.style.display = 'none';
        });
    }

    // ===== DOWNLOAD WIZARD =====
    const generateKeyBtn = document.getElementById('generateKey');
    const trialKeyEl = document.getElementById('trialKey');
    const copyKeyBtn = document.getElementById('copyKey');
    const goStep3Btn = document.getElementById('goStep3');
    const downloadBtn = document.getElementById('downloadBtn');

    const dsi1 = document.getElementById('dsi1');
    const dsi2 = document.getElementById('dsi2');
    const dsi3 = document.getElementById('dsi3');
    const dsiFill1 = document.getElementById('dsiFill1');
    const dsiFill2 = document.getElementById('dsiFill2');

    function setStep(step) {
        document.querySelectorAll('.download .step-content').forEach(s => s.classList.remove('active'));
        if (dsi1) dsi1.classList.remove('active', 'completed');
        if (dsi2) dsi2.classList.remove('active', 'completed');
        if (dsi3) dsi3.classList.remove('active', 'completed');

        if (step === 1) {
            const s1 = document.getElementById('step1'); if (s1) s1.classList.add('active');
            if (dsi1) dsi1.classList.add('active');
            if (dsiFill1) dsiFill1.style.width = '0%';
            if (dsiFill2) dsiFill2.style.width = '0%';
        } else if (step === 2) {
            const s2 = document.getElementById('step2'); if (s2) s2.classList.add('active');
            if (dsi1) dsi1.classList.add('completed');
            if (dsi2) dsi2.classList.add('active');
            if (dsiFill1) dsiFill1.style.width = '100%';
            if (dsiFill2) dsiFill2.style.width = '0%';
        } else if (step === 3) {
            const s3 = document.getElementById('step3'); if (s3) s3.classList.add('active');
            if (dsi1) dsi1.classList.add('completed');
            if (dsi2) dsi2.classList.add('completed');
            if (dsi3) dsi3.classList.add('active');
            if (dsiFill1) dsiFill1.style.width = '100%';
            if (dsiFill2) dsiFill2.style.width = '100%';
        }
    }

    if (generateKeyBtn) {
        generateKeyBtn.addEventListener('click', function() {
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
            setTimeout(() => {
                const key = generateTrialKey();
                if (trialKeyEl) trialKeyEl.textContent = key;
                setStep(2);

                // Show archive password popup after key is generated
                showArchivePasswordModal();
            }, 1500);
        });
    }

    if (copyKeyBtn && trialKeyEl) {
        copyKeyBtn.addEventListener('click', function() {
            const keyText = trialKeyEl.textContent;
            navigator.clipboard.writeText(keyText).then(() => {
                this.classList.add('copied');
                this.innerHTML = '<i class="fas fa-check"></i>';
                setTimeout(() => { this.classList.remove('copied'); this.innerHTML = '<i class="fas fa-copy"></i>'; }, 2000);
            }).catch(() => {
                const range = document.createRange();
                range.selectNode(trialKeyEl);
                window.getSelection().removeAllRanges();
                window.getSelection().addRange(range);
                document.execCommand('copy');
                window.getSelection().removeAllRanges();
                this.classList.add('copied');
                this.innerHTML = '<i class="fas fa-check"></i>';
                setTimeout(() => { this.classList.remove('copied'); this.innerHTML = '<i class="fas fa-copy"></i>'; }, 2000);
            });
        });
    }

    if (goStep3Btn) goStep3Btn.addEventListener('click', () => setStep(3));

    // FIX: Send JSON with action field instead of x-www-form-urlencoded
    if (downloadBtn) {
        downloadBtn.addEventListener('click', function() {
            const key = trialKeyEl ? trialKeyEl.textContent : '';
            fetch('admin/api/log_download.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'download_clicked',
                    trial_code: key
                })
            }).catch(() => {});
        });
    }

    function generateTrialKey() {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        let key = '';
        for (let i = 0; i < 4; i++) {
            if (i > 0) key += '-';
            for (let j = 0; j < 4; j++) key += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return key;
    }

    // ===== TIMELINE PROGRESS =====
    const stepsTimeline = document.querySelector('.steps-timeline');
    const timelineProgress = document.getElementById('timelineProgress');
    if (stepsTimeline && timelineProgress) {
        window.addEventListener('scroll', () => {
            const rect = stepsTimeline.getBoundingClientRect();
            const windowH = window.innerHeight;
            if (rect.top < windowH && rect.bottom > 0) {
                const progress = Math.min(1, Math.max(0, (windowH - rect.top) / (rect.height + windowH * 0.5)));
                timelineProgress.style.width = (progress * 100) + '%';
            }
        });
    }

    // ===== CHAT WIDGET TOGGLE =====
    const chatToggle = document.getElementById('chatToggle');
    const chatClose = document.getElementById('chatClose');
    const chatWindow = document.getElementById('chatWindow');
    if (chatToggle && chatWindow) chatToggle.addEventListener('click', () => chatWindow.classList.toggle('open'));
    if (chatClose && chatWindow) chatClose.addEventListener('click', () => chatWindow.classList.remove('open'));

    // ===== PROMO MODAL =====
    if (window.SITE_CONFIG && window.SITE_CONFIG.promoEnabled) {
        const promoOverlay = document.getElementById('promoOverlay');
        const promoClose = document.getElementById('promoClose');

        if (promoOverlay) {
            setTimeout(() => {
                promoOverlay.classList.add('active');
                startPromoCountdown();
                createPromoParticles();
            }, window.SITE_CONFIG.promoDelay * 1000);

            if (promoClose) promoClose.addEventListener('click', closePromo);
            promoOverlay.addEventListener('click', (e) => { if (e.target === promoOverlay) closePromo(); });

            const promoBtn = document.getElementById('promoBtn');
            if (promoBtn) promoBtn.addEventListener('click', closePromo);

            function closePromo() { promoOverlay.classList.remove('active'); }

            function startPromoCountdown() {
                let remaining = window.SITE_CONFIG.promoCountdown;
                const minEl = document.getElementById('countMinutes');
                const secEl = document.getElementById('countSeconds');
                function update() {
                    if (remaining <= 0) { if (minEl) minEl.textContent = '00'; if (secEl) secEl.textContent = '00'; return; }
                    const m = Math.floor(remaining / 60);
                    const s = remaining % 60;
                    if (minEl) minEl.textContent = String(m).padStart(2, '0');
                    if (secEl) secEl.textContent = String(s).padStart(2, '0');
                    remaining--;
                    setTimeout(update, 1000);
                }
                update();
            }

            function createPromoParticles() {
                const container = document.getElementById('promoParticles');
                if (!container) return;
                for (let i = 0; i < 20; i++) {
                    const particle = document.createElement('div');
                    particle.style.cssText = `position:absolute;width:${Math.random()*6+2}px;height:${Math.random()*6+2}px;background:rgba(37,99,235,${Math.random()*0.4+0.1});border-radius:50%;top:${Math.random()*100}%;left:${Math.random()*100}%;animation:promoFloat ${Math.random()*3+2}s ease-in-out infinite alternate;pointer-events:none;`;
                    container.appendChild(particle);
                }
            }
        }
    }

}); // end DOMContentLoaded