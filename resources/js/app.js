import 'trix';

document.addEventListener('trix-file-accept', (event) => {
    event.preventDefault();
});

const applyStoredAppearance = () => {
    const appearance = window.localStorage.getItem('flux.appearance') || 'system';
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const pauseTransitions = document.createElement('style');

    pauseTransitions.textContent = '*, *::before, *::after { transition: none !important; }';
    document.head.appendChild(pauseTransitions);
    document.documentElement.classList.toggle('dark', appearance === 'dark' || (appearance === 'system' && prefersDark));
    window.getComputedStyle(document.body).getPropertyValue('color');
    requestAnimationFrame(() => requestAnimationFrame(() => pauseTransitions.remove()));
};

document.addEventListener('livewire:navigating', (event) => {
    event.detail.onSwap(applyStoredAppearance);
});

const isInPinnedBar = (element) => {
    for (let node = element; node && node !== document.body; node = node.parentElement) {
        if (['fixed', 'sticky'].includes(window.getComputedStyle(node).position)) {
            return true;
        }
    }

    return false;
};

const pinnedDropdownPlacement = (trigger, { position, align, gap }) => {
    const rect = trigger.getBoundingClientRect();
    const rtl = window.getComputedStyle(trigger).direction === 'rtl';
    const side = rtl && align !== 'center' ? { start: 'end', end: 'start' }[align] : align;
    const edge = 8;
    const place = { top: 'auto', right: 'auto', bottom: 'auto', left: 'auto', translate: 'none', 'max-height': 'none' };

    if (position === 'top' || position === 'bottom') {
        if (position === 'bottom') {
            place.top = rect.bottom + gap;
            place['max-height'] = window.innerHeight - place.top - edge;
        } else {
            place.bottom = window.innerHeight - rect.top + gap;
            place['max-height'] = rect.top - gap - edge;
        }

        if (side === 'center') {
            place.left = rect.left + rect.width / 2;
            place.translate = '-50% 0';
        } else if (side === 'end') {
            place.right = window.innerWidth - rect.right;
        } else {
            place.left = rect.left;
        }
    } else {
        if (position === 'right') {
            place.left = rect.right + gap;
        } else {
            place.right = window.innerWidth - rect.left + gap;
        }

        if (side === 'center') {
            place.top = rect.top + rect.height / 2;
            place.translate = '0 -50%';
        } else if (side === 'end') {
            place.bottom = window.innerHeight - rect.bottom;
            place['max-height'] = rect.bottom - edge;
        } else {
            place.top = rect.top;
            place['max-height'] = window.innerHeight - rect.top - edge;
        }
    }

    return place;
};

const applyDropdownPlacement = (menu, placement) => {
    Object.entries(placement).forEach(([property, value]) => {
        menu.style.setProperty(`--dropdown-${property}`, typeof value === 'number' ? `${value}px` : value);
    });
};

const keepDropdownInViewport = (menu, placement, edge = 8) => {
    const rect = menu.getBoundingClientRect();
    const shiftX = Math.max(edge - rect.left, 0) - Math.max(rect.right - (window.innerWidth - edge), 0);
    const shiftY = Math.max(edge - rect.top, 0) - Math.max(rect.bottom - (window.innerHeight - edge), 0);
    const shifted = { ...placement };

    if (typeof shifted.left === 'number') {
        shifted.left += shiftX;
    } else if (typeof shifted.right === 'number') {
        shifted.right -= shiftX;
    }

    if (typeof shifted.top === 'number') {
        shifted.top += shiftY;
    } else if (typeof shifted.bottom === 'number') {
        shifted.bottom -= shiftY;
    }

    return shifted;
};

const timeLeftParts = (seconds) => [['d', Math.floor(seconds / 86400)], ['h', Math.floor((seconds % 86400) / 3600)], ['m', Math.floor((seconds % 3600) / 60)], ['s', seconds % 60]];

const loadStripe = () => window.Stripe
    ? Promise.resolve(window.Stripe)
    : new Promise((resolve, reject) => {
        const script = document.createElement('script');

        script.src = 'https://js.stripe.com/v3/';
        script.onload = () => resolve(window.Stripe);
        script.onerror = reject;
        document.head.appendChild(script);
    });

const themeColor = (value) => {
    const probe = document.createElement('span');
    const context = document.createElement('canvas').getContext('2d');

    probe.style.color = value;
    document.body.appendChild(probe);
    context.fillStyle = window.getComputedStyle(probe).color;
    probe.remove();
    context.fillRect(0, 0, 1, 1);

    return `#${[...context.getImageData(0, 0, 1, 1).data.slice(0, 3)].map((channel) => channel.toString(16).padStart(2, '0')).join('')}`;
};

const stripeAppearance = () => {
    const body = window.getComputedStyle(document.body);
    const primary = themeColor('var(--color-brand)');
    const line = themeColor('var(--color-line)');

    return {
        theme: document.documentElement.classList.contains('dark') ? 'night' : 'stripe',
        variables: {
            colorPrimary: primary,
            colorBackground: themeColor('var(--color-surface)'),
            colorText: themeColor(body.color),
            colorTextSecondary: themeColor('var(--color-zinc-500)'),
            colorDanger: themeColor('var(--color-red-500)'),
            fontFamily: body.fontFamily,
            fontSizeBase: '16px',
            borderRadius: '8px',
            spacingUnit: '4px',
        },
        rules: {
            '.Input': { borderColor: line, boxShadow: 'none' },
            '.Input:focus': { borderColor: primary, boxShadow: `0 0 0 3px ${primary}40` },
            '.Tab': { borderColor: line, boxShadow: 'none' },
            '.Tab--selected, .Tab--selected:focus': { borderColor: primary, boxShadow: `0 0 0 1px ${primary}` },
            '.Label': { fontWeight: '500' },
        },
    };
};

const stripeFonts = () => {
    const family = window.getComputedStyle(document.body).fontFamily.split(',')[0].replaceAll(/['"]/g, '').trim();

    return [{ cssSrc: `https://fonts.bunny.net/css?family=${family.toLowerCase().replaceAll(' ', '-')}:400,500,600` }];
};

document.addEventListener('alpine:init', () => {
    window.Alpine.data('stripePayment', ({ publishableKey, clientSecret, returnUrl, email, amount, currency, placeUrl }) => ({
        ready: false,
        processing: false,
        error: '',
        clientSecret,
        returnUrl,
        orderUrl: '',

        async init() {
            try {
                const Stripe = await loadStripe();

                this.stripe = Stripe(publishableKey);
                this.elements = this.stripe.elements({
                    ...(clientSecret ? { clientSecret } : { mode: 'payment', amount, currency }),
                    appearance: stripeAppearance(),
                    fonts: stripeFonts(),
                });

                const element = this.elements.create('payment', {
                    layout: 'tabs',
                    defaultValues: { billingDetails: { email } },
                });

                element.on('ready', () => (this.ready = true));
                element.on('loaderror', (event) => (this.error = event.error.message));
                element.mount(this.$refs.element);
            } catch {
                this.error = this.$el.dataset.loadError;
            }
        },

        async pay() {
            if (! this.ready || this.processing) {
                return;
            }

            this.processing = true;
            this.error = '';

            if (! this.clientSecret && ! (await this.placeOrder())) {
                this.processing = false;

                return;
            }

            const { error } = await this.stripe.confirmPayment({
                elements: this.elements,
                clientSecret: this.clientSecret,
                confirmParams: { return_url: this.returnUrl },
            });

            this.error = error.message;
            this.processing = false;
        },

        async placeOrder() {
            const { error } = await this.elements.submit();

            if (error) {
                this.error = error.message;

                return false;
            }

            try {
                const response = await fetch(placeUrl, {
                    method: 'POST',
                    headers: { Accept: 'application/json' },
                    body: new FormData(this.$el),
                });
                const data = await response.json();

                if (data.redirect) {
                    window.location.href = data.redirect;

                    return false;
                }

                if (! response.ok) {
                    this.error = data.message;

                    return false;
                }

                Object.assign(this, { clientSecret: data.clientSecret, returnUrl: data.returnUrl, orderUrl: data.orderUrl });

                return true;
            } catch {
                this.error = this.$el.dataset.placeError;

                return false;
            }
        },
    }));
});

document.addEventListener('alpine:init', () => {
    window.Alpine.data('countdown', ({ to, now, units, separator }) => ({
        left: '',
        done: false,
        timer: null,

        init() {
            const end = new Date(to);
            const skew = new Date(now).getTime() - Date.now();

            const tick = () => {
                const current = new Date(Date.now() + skew);

                if (current >= end) {
                    this.done = true;
                    clearInterval(this.timer);

                    return;
                }

                this.left = timeLeftParts(Math.floor((end - current) / 1000))
                    .filter(([unit, count]) => unit !== 'd' || count > 0)
                    .map(([unit, count]) => `${String(count).padStart(2, '0')}${units[unit]}`)
                    .join(` ${separator} `);
            };

            tick();
            this.timer = setInterval(tick, 1000);
        },

        destroy() {
            clearInterval(this.timer);
        },
    }));

    window.Alpine.data('dropdown', ({ position = 'bottom', align = 'start', gap = 5 } = {}) => ({
        place(menu) {
            const trigger = menu.previousElementSibling;
            const pinned = trigger !== null && isInPinnedBar(trigger);

            menu.classList.toggle('dropdown-fixed', pinned);

            if (! pinned) {
                return;
            }

            const placement = pinnedDropdownPlacement(trigger, { position, align, gap: Number(gap) });

            applyDropdownPlacement(menu, placement);
            requestAnimationFrame(() => applyDropdownPlacement(menu, keepDropdownInViewport(menu, placement)));
        },

        replace() {
            const menu = this.$el.querySelector('[popover]:popover-open');

            if (menu) {
                this.place(menu);
            }
        },
    }));
});
