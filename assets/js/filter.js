document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('ajaxFilterForm');
    if (!form) return;

    const metaDescriptionTag = document.querySelector('meta[name="description"]');
    const canonicalLinkTag = document.querySelector('link[rel="canonical"]');
    const ogUrlMetaTag = document.querySelector('meta[property="og:url"]');
    const baseTitle = (document.body && document.body.dataset.baseTitle) || document.title;
    const baseDescription = (document.body && document.body.dataset.baseDescription) || (metaDescriptionTag ? metaDescriptionTag.getAttribute('content') || '' : '');
    const descriptionFallback = (document.body && document.body.dataset.descriptionFallback) || '';
    const headingEl = document.querySelector('[data-base-heading]');
    const headingBaseText = headingEl ? headingEl.dataset.baseHeading || headingEl.textContent.trim() : null;
    const hideOnPaginationNodes = document.querySelectorAll('[data-hide-on-pagination]');

    function composeWithSuffix(base, suffix) {
        const safeBase = typeof base === 'string' ? base : '';
        return safeBase.replace(/\s+$/g, '') + suffix;
    }

    function applyPaginationUiState(pageNumber, absoluteUrl) {
        const isPaginated = pageNumber > 1;
        hideOnPaginationNodes.forEach(el => {
            if (isPaginated) {
                el.setAttribute('hidden', 'hidden');
            } else {
                el.removeAttribute('hidden');
            }
        });

        const suffix = isPaginated ? ` | Страница ${pageNumber}` : '';
        document.title = isPaginated ? composeWithSuffix(baseTitle, suffix) : baseTitle;

        if (metaDescriptionTag) {
            if (isPaginated) {
                const descBase = baseDescription !== '' ? baseDescription : descriptionFallback;
                metaDescriptionTag.setAttribute('content', composeWithSuffix(descBase, suffix));
            } else {
                metaDescriptionTag.setAttribute('content', baseDescription);
            }
        }

        if (headingEl && headingBaseText !== null) {
            headingEl.textContent = isPaginated ? composeWithSuffix(headingBaseText, suffix) : headingBaseText;
        }

        if (absoluteUrl) {
            if (canonicalLinkTag) canonicalLinkTag.setAttribute('href', absoluteUrl);
            if (ogUrlMetaTag) ogUrlMetaTag.setAttribute('content', absoluteUrl);
        }
    }

    function createEl(tag, className, text) {
        const el = document.createElement(tag);
        if (className) el.className = className;
        if (text !== undefined && text !== null) el.textContent = text;
        return el;
    }

    function buildImageEl(card, index, extraClass) {
        if (card.image && card.image.src) {
            const img = document.createElement('img');
            img.src = card.image.src;
            if (card.image.width) img.width = card.image.width;
            if (card.image.height) img.height = card.image.height;
            img.alt = card.image.alt || '';
            img.decoding = 'async';
            if (index === 0) {
                img.loading = 'eager';
                img.fetchPriority = 'high';
            } else if (index < 4) {
                img.loading = 'eager';
                img.fetchPriority = 'low';
            } else {
                img.loading = 'lazy';
            }
            img.className = extraClass || '';
            return img;
        }

        const placeholder = createEl('div', 'w-full h-full flex items-center justify-center text-sm text-zinc-500', 'Без фото');
        return placeholder;
    }

    function buildHomeCard(card, index) {
        const li = document.createElement('li');
        const article = createEl('article', 'group h-full rounded-lg border border-zinc-200 bg-white overflow-hidden');
        const link = createEl('a', 'block focus:outline-none focus:ring-2 focus:ring-[#b50202]');
        link.href = card.url;

        const figure = createEl('figure', 'relative aspect-[3/4] w-full overflow-hidden bg-zinc-100');
        const img = buildImageEl(card, index, 'w-full h-full object-cover transition-transform duration-300 will-change-transform md:group-hover:scale-105');
        figure.appendChild(img);

        const body = createEl('div', 'p-3');
        const heading = createEl('h3', 'text-base font-semibold text-black leading-snug');
        const name = card.name || card.title || '';
        heading.appendChild(document.createTextNode(name));
        if (card.age) {
            const ageSpan = createEl('span', 'text-[#b50202]', ', ' + card.age);
            heading.appendChild(ageSpan);
        }
        body.appendChild(heading);
        if (card.district) {
            body.appendChild(createEl('p', 'mt-1 text-sm text-zinc-600', card.district));
        }

        link.appendChild(figure);
        link.appendChild(body);
        article.appendChild(link);
        li.appendChild(article);
        return li;
    }

    function buildArchiveCard(card, index) {
        const li = document.createElement('li');
        const cardClasses = 'group h-full flex flex-col rounded-lg border overflow-hidden bg-white text-black border-zinc-200';
        const columnClasses = 'bg-white text-black';
        const dividerBorder = 'border-zinc-200';
        const badgeClasses = 'bg-white/90 text-black border-zinc-200';
        const labelMuted = 'text-zinc-600';
        const labelStrong = 'text-black';
        const chipText = 'text-zinc-700';
        const chipBorder = 'border-zinc-200';
        const buttonBase = 'bg-white text-black border-zinc-200';
        const imgHover = 'transition-transform duration-300 will-change-transform md:group-hover:scale-105';
        const focusRing = 'focus:ring-[#b50202]';

        const article = createEl('article', cardClasses);
        const grid = createEl('div', 'grid grid-cols-1 md:grid-cols-2 h-full');

        const leftCol = createEl('div', 'flex flex-col h-full ' + columnClasses);
        const imageLink = createEl('a', 'block focus:outline-none focus:ring-2 ' + focusRing);
        imageLink.href = card.url;
        const figure = createEl('figure', 'relative aspect-[3/4] w-full ' + columnClasses + ' overflow-hidden');
        figure.appendChild(buildImageEl(card, index, 'w-full h-full object-cover ' + imgHover));

        if (card.photo_count && card.photo_count > 0) {
            const badge = createEl('span', 'absolute top-2 left-2 text-xs font-semibold ' + badgeClasses + ' px-2 py-1 rounded border');
            badge.textContent = 'Фото: ' + card.photo_count;
            figure.appendChild(badge);
        }
        if (card.show_comments) {
            const badge = createEl('span', 'absolute bottom-2 left-2 text-xs font-semibold ' + badgeClasses + ' px-2 py-1 rounded border');
            badge.textContent = 'Отзывы: ' + card.comments_count;
            figure.appendChild(badge);
        }
        if (card.online) {
            figure.appendChild(createEl('span', 'absolute top-2 right-2 h-2.5 w-2.5 rounded-full bg-green-500 ring-2 ring-black/10'));
        }
        if (card.vip) {
            const vip = createEl('span', 'absolute bottom-3 left-1/2 -translate-x-1/2 inline-flex items-center whitespace-nowrap px-6 py-1 text-xs font-semibold bg-[#b50202] text-white rounded-full', 'VIP Модель');
            figure.appendChild(vip);
        }
        imageLink.appendChild(figure);
        leftCol.appendChild(imageLink);

        if (card.wa_link || card.tg_user) {
            const contactWrap = createEl('div', 'px-3 py-3 border-t ' + dividerBorder + ' ' + columnClasses);
            const row = createEl('div', 'flex gap-2');
            if (card.wa_link) {
                const waLink = createEl('a', 'flex-1 inline-flex items-center justify-center gap-2 rounded-lg border ' + buttonBase + ' px-3 py-2 text-xs font-semibold');
                waLink.href = card.wa_link;
                waLink.target = '_blank';
                waLink.rel = 'noopener';
                const waIcon = createEl('span', 'text-[#25D366]');
                waIcon.innerHTML = '<svg class=\"w-4 h-4 fill-current\" viewBox=\"0 0 24 24\"><path d=\"M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z\"/></svg>';
                waLink.appendChild(waIcon);
                waLink.appendChild(document.createTextNode('WhatsApp'));
                row.appendChild(waLink);
            }
            if (card.tg_user) {
                const tgLink = createEl('a', 'flex-1 inline-flex items-center justify-center gap-2 rounded-lg border ' + buttonBase + ' px-3 py-2 text-xs font-semibold');
                tgLink.href = 'https://t.me/' + card.tg_user;
                tgLink.target = '_blank';
                tgLink.rel = 'noopener';
                const tgIcon = createEl('span', 'text-[#229ED9]');
                tgIcon.innerHTML = '<svg class=\"w-4 h-4 fill-current\" viewBox=\"0 0 24 24\"><path d=\"M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 11.944 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z\"/></svg>';
                tgLink.appendChild(tgIcon);
                tgLink.appendChild(document.createTextNode('Telegram'));
                row.appendChild(tgLink);
            }
            contactWrap.appendChild(row);
            leftCol.appendChild(contactWrap);
        }

        const rightCol = createEl('div', 'p-3 flex flex-col gap-3 ' + columnClasses + ' h-full');
        const titleLink = createEl('a', 'block focus:outline-none focus:ring-2 ' + focusRing);
        titleLink.href = card.url;
        const titleWrap = createEl('div', 'border-b ' + dividerBorder + ' pb-2');
        const title = createEl('h3', 'text-base font-semibold leading-snug', card.name || card.title || '');
        titleWrap.appendChild(title);
        titleLink.appendChild(titleWrap);
        rightCol.appendChild(titleLink);

        const infoItems = [];
        if (card.height) infoItems.push(['Рост', card.height + ' см']);
        if (card.age) infoItems.push(['Возраст', card.age]);
        if (card.weight) infoItems.push(['Вес', card.weight + ' кг']);
        if (card.bust) infoItems.push(['Грудь', card.bust]);
        if (infoItems.length) {
            const infoGrid = createEl('div', 'grid grid-cols-2 gap-2 text-sm ' + labelMuted);
            infoItems.forEach(([label, value]) => {
                const row = createEl('div', 'flex items-center justify-between border-b ' + dividerBorder + ' pb-1');
                row.appendChild(createEl('span', labelMuted, label + ':'));
                row.appendChild(createEl('span', 'font-semibold ' + labelStrong, value));
                infoGrid.appendChild(row);
            });
            rightCol.appendChild(infoGrid);
        }

        if (card.services && card.services.length) {
            const servicesWrap = createEl('div', 'border-b ' + dividerBorder + ' pb-2');
            servicesWrap.appendChild(createEl('div', 'text-sm font-semibold ' + labelStrong, 'Услуги'));
            const chips = createEl('div', 'mt-2 flex flex-wrap gap-2');
            card.services.forEach(service => {
                chips.appendChild(createEl('span', 'text-xs px-2 py-1 rounded border ' + chipBorder + ' ' + chipText, service));
            });
            servicesWrap.appendChild(chips);
            rightCol.appendChild(servicesWrap);
        }

        if (card.prices && (card.prices.has_outcall || card.prices.has_incall)) {
            const priceWrap = createEl('div', 'border-b ' + dividerBorder + ' pb-2');
            priceWrap.appendChild(createEl('div', 'text-sm font-semibold ' + labelStrong, 'Стоимость'));
            const headerRow = createEl('div', 'mt-2 flex text-xs ' + labelMuted);
            headerRow.appendChild(createEl('div', 'flex-1'));
            headerRow.appendChild(createEl('div', 'flex-1 text-center', 'Час'));
            headerRow.appendChild(createEl('div', 'flex-1 text-center', 'Два'));
            priceWrap.appendChild(headerRow);

            const format = (value) => value.replace('&nbsp;', ' ');             

            if (card.prices.has_outcall) {
                const row = createEl('div', 'mt-1 flex text-sm');
                row.appendChild(createEl('div', 'flex-1 ' + labelMuted, 'Выезд'));
                row.appendChild(createEl('div', 'flex-1 text-center', format(card.prices.outcall.hour)));
                row.appendChild(createEl('div', 'flex-1 text-center', format(card.prices.outcall.two)));
                priceWrap.appendChild(row);
            }

            if (card.prices.has_incall) {
                const row = createEl('div', 'mt-1 flex text-sm border-t ' + dividerBorder + ' pt-1');
                row.appendChild(createEl('div', 'flex-1 ' + labelMuted, 'У себя'));
                row.appendChild(createEl('div', 'flex-1 text-center', format(card.prices.incall.hour)));
                row.appendChild(createEl('div', 'flex-1 text-center', format(card.prices.incall.two)));
                priceWrap.appendChild(row);
            }

            rightCol.appendChild(priceWrap);
        }

        if (card.description) {
            const details = createEl('details', 'text-sm ' + labelMuted);
            const summary = createEl('summary', 'cursor-pointer text-sm font-semibold ' + labelStrong, 'Обо мне');
            const desc = createEl('p', 'mt-2', card.description);
            details.appendChild(summary);
            details.appendChild(desc);
            rightCol.appendChild(details);
        }

        grid.appendChild(leftCol);
        grid.appendChild(rightCol);
        article.appendChild(grid);
        li.appendChild(article);
        return li;
    }

    function renderCards(cards, viewContextValue) {
        let listEl = getListEl();
        if (!listEl) {
            listEl = document.createElement('ul');
            if (viewContextValue === 'home') {
                listEl.className = 'models-grid-list homepage-grid grid grid-cols-2 md:grid-cols-4 gap-4 mt-4 list-none p-0';
            } else {
                listEl.className = 'models-grid-list grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 mt-4 list-none p-0';
            }
            gridContainer.insertBefore(listEl, paginationWrapper || null);
        }

        const fragment = document.createDocumentFragment();
        if (cards && cards.length) {
            cards.forEach((card, idx) => {
                const item = viewContextValue === 'home' ? buildHomeCard(card, idx) : buildArchiveCard(card, idx);
                fragment.appendChild(item);
            });
        } else {
            const empty = createEl('li', 'col-span-full text-center text-sm text-zinc-500 py-10', 'По вашему запросу моделей не найдено.');
            fragment.appendChild(empty);
        }
        listEl.replaceChildren(fragment);
    }

    // UI (Мультиселекты, слайдеры)
    const multiSelects = document.querySelectorAll('.pf-multiselect');
    function updateMultiSelectLabel(container) {
        const checkboxes = container.querySelectorAll('.pf-ms-checkbox:checked');
        const labelEl = container.querySelector('.pf-ms-label');
        if (!labelEl) return;
        if (!container.getAttribute('data-original-text')) {
            container.setAttribute('data-original-text', labelEl.textContent.trim());
        }
        if (checkboxes.length === 0) {
            labelEl.textContent = container.getAttribute('data-original-text');
            labelEl.classList.add('text-zinc-600');
            labelEl.classList.remove('text-black');
        } else if (checkboxes.length === 1) {
            labelEl.textContent = checkboxes[0].closest('label').querySelector('span').textContent;
            labelEl.classList.remove('text-zinc-600');
            labelEl.classList.add('text-black');
        } else {
            labelEl.textContent = 'Выбрано: ' + checkboxes.length;
            labelEl.classList.remove('text-zinc-600');
            labelEl.classList.add('text-black');
        }
    }

    multiSelects.forEach(ms => {
        const trigger = ms.querySelector('.pf-ms-trigger');
        const dropdown = ms.querySelector('.pf-ms-dropdown');
        const checkboxes = ms.querySelectorAll('.pf-ms-checkbox');
        if (!trigger || !dropdown) return;
        updateMultiSelectLabel(ms);
        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = !dropdown.classList.contains('hidden');
            multiSelects.forEach(o => {
                const dd = o.querySelector('.pf-ms-dropdown');
                if (dd) dd.classList.add('hidden');
                o.classList.remove('z-[100]');
                o.classList.add('z-0');
            });
            if (isOpen) {
                dropdown.classList.add('hidden');
                ms.classList.remove('z-[100]');
            } else {
                dropdown.classList.remove('hidden');
                ms.classList.remove('z-0');
                ms.classList.add('z-[100]');
            }
        });
        checkboxes.forEach(cb => cb.addEventListener('change', () => updateMultiSelectLabel(ms)));
    });

    document.addEventListener('click', (e) => {
        multiSelects.forEach(ms => {
            if (!ms.contains(e.target)) {
                const dd = ms.querySelector('.pf-ms-dropdown');
                if (dd) dd.classList.add('hidden');
                ms.classList.remove('z-[100]');
            }
        });
    });

    const sliderUpdaters = [];
    document.querySelectorAll('.pf-slider-card').forEach(s => {
        const rMin = s.querySelector('.min-range');
        const rMax = s.querySelector('.max-range');
        const fill = s.querySelector('.pf-track-fill');
        const vMin = s.querySelector('.val-min');
        const vMax = s.querySelector('.val-max');
        const minA = parseFloat(s.dataset.min);
        const maxA = parseFloat(s.dataset.max);
        if (!rMin || !rMax || !fill || !vMin || !vMax || Number.isNaN(minA) || Number.isNaN(maxA)) return;

        const update = function() {
            let v1 = parseFloat(rMin.value);
            let v2 = parseFloat(rMax.value);
            if (v2 - v1 < 1) {
                if (this === rMin) rMin.value = v2 - 1;
                else rMax.value = v1 + 1;
            }
            v1 = parseFloat(rMin.value);
            v2 = parseFloat(rMax.value);
            fill.style.left = ((v1 - minA) / (maxA - minA)) * 100 + '%';
            fill.style.width = (((v2 - minA) / (maxA - minA)) * 100 - ((v1 - minA) / (maxA - minA)) * 100) + '%';
            vMin.textContent = v1;
            vMax.textContent = v2;
        };
        rMin.addEventListener('input', update);
        rMax.addEventListener('input', update);
        update();
        sliderUpdaters.push(update);
    });

    const toggleContainer = document.getElementById('typeToggle');
    const labels = toggleContainer ? toggleContainer.querySelectorAll('label') : [];
    function updateToggleStyles() {
        labels.forEach(lbl => {
            const input = lbl.querySelector('input');
            if (!input) return;
            if (input.checked) {
                lbl.classList.remove('text-zinc-700', 'hover:text-black');
                lbl.classList.add('bg-[#b50202]', 'text-white', 'shadow-sm');
            } else {
                lbl.classList.add('text-zinc-700', 'hover:text-black');
                lbl.classList.remove('bg-[#b50202]', 'text-white', 'shadow-sm');
            }
        });
    }
    labels.forEach(lbl => lbl.addEventListener('click', () => setTimeout(updateToggleStyles, 0)));
    updateToggleStyles();

    // --- ЛОГИКА AJAX ---
    const apiUrl = form.dataset.apiUrl;
    const gridContainer = document.getElementById('modelsGrid');
    if (!gridContainer || !apiUrl) return;

    const sortControls = document.getElementById('sortControls');
    let currentSortBy = gridContainer.dataset.sortBy || 'date';
    let currentSortOrder = gridContainer.dataset.sortOrder || 'DESC';
    const viewContext = gridContainer.dataset.viewContext || 'archive';

    function withSortParams(urlValue) {
        const url = new URL(urlValue, window.location.origin);
        url.searchParams.set('sort_by', currentSortBy);
        url.searchParams.set('sort_order', currentSortOrder);
        return url.toString();
    }

    function updateSortButtonsUI() {
        if (!sortControls) return;
        const btns = sortControls.querySelectorAll('.sort-btn');
        btns.forEach(btn => {
            const btnSortType = btn.dataset.sort;
            const arrowSpan = btn.querySelector('.sort-arrow');
            if (!arrowSpan) return;

            if (btnSortType === currentSortBy) {
                btn.classList.add('bg-[#b50202]', 'border-[#b50202]', 'text-white');
                btn.classList.remove('bg-transparent', 'border-transparent', 'text-zinc-500', 'hover:text-zinc-700');
                arrowSpan.textContent = (currentSortOrder === 'ASC') ? '▲' : '▼';
                arrowSpan.classList.remove('opacity-0');
            } else {
                btn.classList.remove('bg-[#b50202]', 'border-[#b50202]', 'text-white');
                btn.classList.add('bg-transparent', 'border-transparent', 'text-zinc-500', 'hover:text-zinc-700');
                arrowSpan.textContent = '▼';
                arrowSpan.classList.add('opacity-0');
            }
        });
    }
    updateSortButtonsUI();

    if (sortControls) {
        sortControls.addEventListener('click', (e) => {
            const btn = e.target.closest('.sort-btn');
            if (!btn) return;
            const type = btn.dataset.sort;
            if (currentSortBy === type) {
                currentSortOrder = (currentSortOrder === 'DESC') ? 'ASC' : 'DESC';
            } else {
                currentSortBy = type;
                currentSortOrder = 'DESC';
            }
            updateSortButtonsUI();
            if (viewContext === 'home') {
                window.location.href = withSortParams(window.location.href);
                return;
            }
            fetchModels(1, true);
        });
    }

    const getListEl = () => gridContainer.querySelector('.models-grid-list');
    const paginationWrapper = document.getElementById('modelsPagination');
    const submitBtn = form.querySelector('.pf-btn-find');

    const perPage = parseInt(gridContainer.dataset.perPage || '24', 10);
    const baseFilters = (() => {
        try { return JSON.parse(gridContainer.dataset.baseFilters || '{}'); } catch (e) { return {}; }
    })();
    const contextTax = gridContainer.dataset.contextTax || '';
    const contextTerm = gridContainer.dataset.contextTerm || '';
    const initialPage = parseInt(gridContainer.dataset.currentPage || '1', 10) || 1;
    applyPaginationUiState(initialPage, window.location.href);

    function setLoadingState(isLoading) {
        const listEl = getListEl();
        if (listEl) listEl.style.opacity = isLoading ? '0.3' : '1';
        if (submitBtn) {
            if (isLoading) {
                submitBtn.dataset.originalText = submitBtn.innerText;
                submitBtn.innerText = '...';
                submitBtn.disabled = true;
            } else {
                submitBtn.innerText = submitBtn.dataset.originalText || 'Найти';
                submitBtn.disabled = false;
            }
        }
        if (isLoading && window.innerWidth < 768) {
            gridContainer.scrollIntoView({ behavior: 'smooth' });
        }
    }

    function appendParam(params, key, value) {
        if (value === undefined || value === null || value === '' || value === false) return;
        if (Array.isArray(value)) {
            value.forEach(val => appendParam(params, key, val));
            return;
        }
        params.append(key, value);
    }

    function buildRequestParams(targetPage = 1) {
        const params = new URLSearchParams();
        params.set('page', targetPage);
        if (perPage) params.set('per_page', perPage);

        params.set('orderby', currentSortBy);
        params.set('order', currentSortOrder);

        Object.keys(baseFilters).forEach(key => appendParam(params, key, baseFilters[key]));
        if (contextTax && contextTerm) {
            params.set('context_tax', contextTax);
            params.set('context_term', contextTerm);
        }
        params.set('view_context', viewContext);

        const formData = new FormData(form);
        formData.forEach((value, key) => {
            if (key.endsWith('_min') || key.endsWith('_max')) return;
            if (value === '' || value === null) return;
            params.append(key, value);
        });

        document.querySelectorAll('.pf-slider-wrapper').forEach(slider => {
            const minInput = slider.querySelector('.min-range');
            const maxInput = slider.querySelector('.max-range');
            if (!minInput || !maxInput) return;
            params.append(minInput.name, minInput.value);
            params.append(maxInput.name, maxInput.value);
        });
        return params;
    }

    async function fetchModels(targetPage = 1, updateUrl = false, urlToPush = '') {
        if (!getListEl()) return;
        setLoadingState(true);
        const params = buildRequestParams(targetPage);
        const baseUrl = urlToPush ? new URL(urlToPush, window.location.origin).toString() : window.location.href;
        const resolvedUrl = withSortParams(baseUrl);

        try {
            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: params.toString(),
                credentials: 'same-origin',
            });
            const data = await response.json();
            const payload = (data && typeof data === 'object' && data.data) ? data.data : data;
            const cards = Array.isArray(payload?.cards) ? payload.cards : [];
            const paginationHtml = payload?.pagination_html || '';

            renderCards(cards, viewContext);
            if (paginationWrapper) {
                paginationWrapper.innerHTML = paginationHtml;
                paginationWrapper.classList.toggle('hidden', !paginationHtml);
            }
            const nextPage = targetPage;
            if (updateUrl && resolvedUrl) history.pushState({ page: nextPage }, '', resolvedUrl);
            applyPaginationUiState(nextPage, resolvedUrl);
        } catch (error) {
            console.error('Error:', error);
        } finally {
            setLoadingState(false);
        }
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        if (window.innerWidth < 768) {
            const closeBtn = document.getElementById('closeFilterBtn');
            const overlay = document.getElementById('mobileFilterOverlay');
            if (closeBtn) {
                closeBtn.click();
            } else if (overlay) {
                overlay.click();
            }
        }

        const baseUrl = window.location.href.replace(/\/page\/\d+/, '').replace(/\/$/, '');
        fetchModels(1, true, baseUrl);
    });

    const resetBtn = document.getElementById('resetFilterBtn');
    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            document.cookie = 'models_filter_state=; path=/; expires=Thu, 01 Jan 1970 00:00:01 GMT;';
            form.reset();
            requestAnimationFrame(() => {
                multiSelects.forEach(ms => updateMultiSelectLabel(ms));
                sliderUpdaters.forEach(fn => fn());
                updateToggleStyles();
                const baseUrl = window.location.href.replace(/\/page\/\d+/, '').replace(/\/$/, '');
                fetchModels(1, true, baseUrl);
            });
        });
    }

    if (paginationWrapper) {
        paginationWrapper.addEventListener('click', function(e) {
            const link = e.target.closest('a');
            if (!link) return;
            e.preventDefault();
            const href = link.getAttribute('href');
            let targetPage = 1;
            const match = href.match(/\/page\/(\d+)/);
            if (match && match[1]) targetPage = parseInt(match[1], 10);
            fetchModels(targetPage, true, href);
        });
    }

    window.addEventListener('popstate', function() {
        window.location.reload();
    });
});
