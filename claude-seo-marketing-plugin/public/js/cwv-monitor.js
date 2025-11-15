/**
 * Real-time Core Web Vitals monitoring - Frontend JavaScript.
 *
 * Lightweight (<12KB) script that monitors CWV metrics in real-time
 * and sends data to backend for processing and alerting.
 *
 * @package    Claude_SEO
 * @subpackage Claude_SEO/public/js
 */

(function() {
    'use strict';

    /**
     * Web Vitals Monitor Class
     */
    class WebVitalsMonitor {
        constructor(config) {
            this.endpoint = config.endpoint || '/wp-json/claude-seo/v1/cwv';
            this.pageId = config.pageId;
            this.siteId = config.siteId;
            this.batch = [];
            this.batchSize = 10;
            this.flushInterval = 5000; // 5 seconds

            this.init();
        }

        init() {
            // Only run if web-vitals library is available
            if (typeof webVitals === 'undefined') {
                console.warn('Web Vitals library not loaded');
                return;
            }

            this.setupMonitoring();
            this.setupEventListeners();
            this.startFlushTimer();
        }

        setupMonitoring() {
            const options = { reportAllChanges: true };

            // Monitor all Core Web Vitals
            webVitals.onLCP(this.handleMetric.bind(this), options);
            webVitals.onINP(this.handleMetric.bind(this), options);
            webVitals.onCLS(this.handleMetric.bind(this), options);
            webVitals.onFCP(this.handleMetric.bind(this), options);
            webVitals.onTTFB(this.handleMetric.bind(this), options);
        }

        handleMetric(metric) {
            const metricData = {
                name: metric.name,
                value: Math.round(metric.value * 100) / 100,
                rating: metric.rating,
                delta: metric.delta,
                id: metric.id,
                pageId: this.pageId,
                siteId: this.siteId,
                deviceType: this.getDeviceType(),
                connectionType: this.getConnectionType(),
                timestamp: Date.now(),
                url: window.location.href,
                navigationType: this.getNavigationType()
            };

            this.batch.push(metricData);

            if (this.batch.length >= this.batchSize) {
                this.flush();
            }
        }

        setupEventListeners() {
            // Flush on page hide (user navigating away)
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'hidden') {
                    this.flush();
                }
            });

            // Flush on page unload
            window.addEventListener('pagehide', () => {
                this.flush();
            });

            // Flush before page freeze
            window.addEventListener('freeze', () => {
                this.flush();
            });
        }

        startFlushTimer() {
            setInterval(() => {
                if (this.batch.length > 0) {
                    this.flush();
                }
            }, this.flushInterval);
        }

        flush() {
            if (this.batch.length === 0) return;

            const payload = JSON.stringify({
                metrics: this.batch.splice(0),
                meta: {
                    userAgent: navigator.userAgent,
                    viewport: {
                        width: window.innerWidth,
                        height: window.innerHeight
                    },
                    screen: {
                        width: screen.width,
                        height: screen.height
                    }
                }
            });

            // Use sendBeacon for reliability (works even during page unload)
            if (navigator.sendBeacon) {
                navigator.sendBeacon(this.endpoint, payload);
            } else {
                // Fallback to fetch with keepalive
                fetch(this.endpoint, {
                    method: 'POST',
                    body: payload,
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    keepalive: true
                }).catch(err => {
                    console.error('Failed to send metrics:', err);
                });
            }
        }

        getDeviceType() {
            const width = window.innerWidth;
            if (width < 768) return 'mobile';
            if (width < 1024) return 'tablet';
            return 'desktop';
        }

        getConnectionType() {
            if (!navigator.connection) return 'unknown';

            const conn = navigator.connection;
            return {
                effectiveType: conn.effectiveType,
                downlink: conn.downlink,
                rtt: conn.rtt,
                saveData: conn.saveData
            };
        }

        getNavigationType() {
            if (!performance.getEntriesByType) return 'unknown';

            const navEntry = performance.getEntriesByType('navigation')[0];
            return navEntry ? navEntry.type : 'unknown';
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMonitor);
    } else {
        initMonitor();
    }

    function initMonitor() {
        if (window.claudeSeoConfig && window.claudeSeoConfig.cwvMonitoring) {
            new WebVitalsMonitor(window.claudeSeoConfig);
        }
    }

})();
