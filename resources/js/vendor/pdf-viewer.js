import * as pdfjsLib from 'pdfjs-dist';
import workerUrl from 'pdfjs-dist/build/pdf.worker.mjs?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = workerUrl;

const MIN_ZOOM = 0.5;
const MAX_ZOOM = 3;
const ZOOM_STEP = 0.25;

const MAX_CANVAS_PIXELS = 16_000_000;

const VISIBILITY_RETRY_MS = 100;
const MAX_VISIBILITY_RETRIES = 50;

const instances = new Set();

class PdfViewer {
    constructor(element) {
        this.element = element;
        this.canvas = element.querySelector('[data-pdf-canvas]');
        this.context = this.canvas.getContext('2d');
        this.loading = element.querySelector('[data-pdf-loading]');
        this.error = element.querySelector('[data-pdf-error]');
        this.currentPageEl = element.querySelector('[data-pdf-current-page]');
        this.totalPagesEl = element.querySelector('[data-pdf-total-pages]');
        this.controls = [...element.querySelectorAll('[data-pdf-action]')];
        this.viewportContainer = element.querySelector('[data-pdf-viewport]') || this.canvas.parentElement;

        this.loadingTask = null;
        this.pdf = null;
        this.currentPage = Math.max(1, Number.parseInt(element.dataset.initialPage || '1', 10));
        this.zoomFactor = 1;

        this.rendering = false;
        this.renderTask = null;
        this.pendingPage = null;
        this.lastRenderedWidth = 0;
        this.visibilityRetries = 0;

        this.resizeObserver = null;
        this.resizeTimer = null;
        this.visibilityTimer = null;
        this.destroyed = false;

        this.onVisible = () => this.requestRender(this.currentPage);
    }

    async init() {
        this.prepareLayout();
        this.bindControls();
        this.bindResizeObserver();
        this.element.addEventListener('pdf-viewer-visible', this.onVisible);
        this.setLoading(true);

        try {
            this.loadingTask = pdfjsLib.getDocument({ url: this.element.dataset.pdfUrl });
            this.pdf = await this.loadingTask.promise;

            if (this.destroyed) {
                return;
            }

            this.currentPage = Math.min(this.currentPage, this.pdf.numPages);
            this.updateState();
            await this.requestRender(this.currentPage);
        } catch (error) {
            if (!this.destroyed) {
                this.showError(error);
                this.setLoading(false);
            }
        }
    }

    prepareLayout() {
        this.viewportContainer.style.display = 'block';
        this.viewportContainer.style.scrollbarGutter = 'stable';
        this.updateOverflow();
        this.canvas.style.margin = '0 auto';
        this.canvas.style.maxWidth = 'none';
        this.canvas.style.visibility = 'hidden';
    }

    bindControls() {
        this.controls.forEach((control) => {
            control.addEventListener('click', () => {
                const action = control.dataset.pdfAction;

                if (action === 'previous') {
                    this.goToPage(this.currentPage - 1);
                } else if (action === 'next') {
                    this.goToPage(this.currentPage + 1);
                } else if (action === 'zoom-out') {
                    this.zoom(-ZOOM_STEP);
                } else if (action === 'zoom-in') {
                    this.zoom(ZOOM_STEP);
                }
            });
        });
    }

    bindResizeObserver() {
        if (!window.ResizeObserver) {
            return;
        }

        this.resizeObserver = new ResizeObserver(() => {
            window.clearTimeout(this.resizeTimer);

            this.resizeTimer = window.setTimeout(() => {
                if (this.pdf && this.getAvailableWidth() !== this.lastRenderedWidth) {
                    this.requestRender(this.currentPage);
                }
            }, 120);
        });

        this.resizeObserver.observe(this.viewportContainer);
    }

    goToPage(pageNumber) {
        if (!this.pdf || pageNumber < 1 || pageNumber > this.pdf.numPages) {
            return;
        }

        this.requestRender(pageNumber).then(() => {
            this.viewportContainer.scrollTop = 0;
        });
    }

    zoom(delta) {
        const next = Math.min(MAX_ZOOM, Math.max(MIN_ZOOM, this.zoomFactor + delta));

        if (next === this.zoomFactor) {
            return;
        }

        this.zoomFactor = next;
        this.updateOverflow();
        this.requestRender(this.currentPage);
    }

    async requestRender(pageNumber) {
        if (!this.pdf || this.destroyed) {
            return;
        }

        this.pendingPage = pageNumber;

        if (this.rendering) {
            this.renderTask?.cancel();
            return;
        }

        this.rendering = true;
        this.setLoading(true);

        try {
            while (this.pendingPage !== null && !this.destroyed) {
                const page = this.pendingPage;
                this.pendingPage = null;
                await this.renderPage(page);
            }
        } finally {
            this.rendering = false;

            if (!this.destroyed) {
                this.setLoading(false);
            }
        }
    }

    updateOverflow() {
        this.viewportContainer.style.overflowX = this.zoomFactor > 1 ? 'auto' : 'hidden';
    }

    getAvailableWidth() {
        const container = this.viewportContainer;
        const styles = window.getComputedStyle(container);
        const paddingX =
            Number.parseFloat(styles.paddingLeft || '0') + Number.parseFloat(styles.paddingRight || '0');

        return Math.max(0, Math.floor(container.clientWidth - paddingX));
    }

    async renderPage(pageNumber) {
        const availableWidth = this.getAvailableWidth();

        if (availableWidth <= 0) {
            this.scheduleVisibilityRetry(pageNumber);
            return;
        }

        this.visibilityRetries = 0;

        try {
            const page = await this.pdf.getPage(pageNumber);
            const baseViewport = page.getViewport({ scale: 1 });
            const fitWidthScale = availableWidth / baseViewport.width;
            const viewport = page.getViewport({ scale: fitWidthScale * this.zoomFactor });

            const devicePixelRatio = window.devicePixelRatio || 1;
            const maxOutputScale = Math.sqrt(MAX_CANVAS_PIXELS / (viewport.width * viewport.height));
            const outputScale = Math.max(0.1, Math.min(devicePixelRatio, maxOutputScale));

            const buffer = document.createElement('canvas');
            buffer.width = Math.floor(viewport.width * outputScale);
            buffer.height = Math.floor(viewport.height * outputScale);

            this.renderTask = page.render({
                canvas: buffer,
                canvasContext: buffer.getContext('2d'),
                viewport,
                transform: outputScale !== 1 ? [outputScale, 0, 0, outputScale, 0, 0] : null,
            });

            await this.renderTask.promise;

            if (this.destroyed) {
                return;
            }

            this.canvas.width = buffer.width;
            this.canvas.height = buffer.height;
            this.canvas.style.width = `${Math.floor(viewport.width)}px`;
            this.canvas.style.height = `${Math.floor(viewport.height)}px`;
            this.context.drawImage(buffer, 0, 0);
            this.canvas.style.visibility = 'visible';

            this.lastRenderedWidth = availableWidth;
            this.currentPage = pageNumber;
            this.hideError();
            this.updateState();
        } catch (error) {
            if (error?.name !== 'RenderingCancelledException' && !this.destroyed) {
                this.showError(error);
            }
        } finally {
            this.renderTask = null;
        }
    }

    scheduleVisibilityRetry(pageNumber) {
        if (this.visibilityRetries >= MAX_VISIBILITY_RETRIES) {
            return;
        }

        this.visibilityRetries++;
        window.clearTimeout(this.visibilityTimer);
        this.visibilityTimer = window.setTimeout(() => this.requestRender(pageNumber), VISIBILITY_RETRY_MS);
    }

    updateState() {
        if (this.currentPageEl) {
            this.currentPageEl.textContent = String(this.currentPage);
        }

        if (this.totalPagesEl) {
            this.totalPagesEl.textContent = String(this.pdf ? this.pdf.numPages : 0);
        }

        this.controls.forEach((control) => {
            const action = control.dataset.pdfAction;

            control.disabled =
                !this.pdf ||
                (action === 'previous' && this.currentPage <= 1) ||
                (action === 'next' && this.currentPage >= this.pdf.numPages) ||
                (action === 'zoom-out' && this.zoomFactor <= MIN_ZOOM) ||
                (action === 'zoom-in' && this.zoomFactor >= MAX_ZOOM);
        });
    }

    setLoading(isLoading) {
        if (this.loading) {
            this.loading.classList.toggle('hidden', !isLoading);
            this.loading.classList.toggle('flex', isLoading);
        }
    }

    showError(error) {
        if (this.error) {
            this.error.textContent = error?.message || 'Unable to load this PDF.';
            this.error.classList.remove('hidden');
        }
    }

    hideError() {
        this.error?.classList.add('hidden');
    }

    destroy() {
        this.destroyed = true;
        this.renderTask?.cancel();
        this.resizeObserver?.disconnect();
        window.clearTimeout(this.resizeTimer);
        window.clearTimeout(this.visibilityTimer);
        this.element.removeEventListener('pdf-viewer-visible', this.onVisible);
        this.loadingTask?.destroy();
        this.pdf = null;
        instances.delete(this);
    }
}

export function destroyDetachedPdfViewers() {
    instances.forEach((instance) => {
        if (!instance.element.isConnected) {
            instance.destroy();
        }
    });
}

export function initPdfViewers(root = document) {
    destroyDetachedPdfViewers();

    root.querySelectorAll('[data-pdf-viewer]:not([data-pdf-ready])').forEach((element) => {
        element.dataset.pdfReady = 'true';
        const instance = new PdfViewer(element);
        instances.add(instance);
        instance.init();
    });
}

document.addEventListener('DOMContentLoaded', () => initPdfViewers());
document.addEventListener('livewire:navigated', () => initPdfViewers());
