<v-charts-bar {{ $attributes }}></v-charts-bar>

@pushOnce('scripts')
    <!-- SEO Vue Component Template -->
    <script
        type="text/x-template"
        id="v-charts-bar-template"
    >
        <canvas
            :id="$.uid + '_chart'"
            class="flex w-full items-end"
            :class="{ 'h-full': fluidHeight }"
            :style="fluidHeight ? '' : 'aspect-ratio:' + aspectRatio + '/1'"
        ></canvas>
    </script>

    <script type="module">
        app.component('v-charts-bar', {
            template: '#v-charts-bar-template',

            props: {
                labels: {
                    type: Array, 
                    default: [],
                },

                datasets: {
                    type: Array, 
                    default: true,
                },

                aspectRatio: {
                    type: Number, 
                    default: 3.23,
                },

                showLegend: {
                    type: Boolean,
                    default: false,
                },

                fluidHeight: {
                    type: Boolean,
                    default: false,
                },

                showAllXTicks: {
                    type: Boolean,
                    default: false,
                },
            },

            data() {
                return {
                    chart: undefined,
                }
            },

            mounted() {
                this.prepare();
            },

            methods: {
                prepare() {
                    if (this.chart) {
                        this.chart.destroy();
                    }

                    const isDark = document.documentElement.classList.contains('dark');
                    const gridColor = isDark ? 'rgba(148, 163, 184, 0.16)' : 'rgba(203, 213, 225, 0.55)';
                    const axisColor = isDark ? 'rgba(148, 163, 184, 0.34)' : 'rgba(100, 116, 139, 0.42)';
                    const tickColor = isDark ? '#94a3b8' : '#666666';
                    const tooltipBackground = isDark ? '#020617' : '#0f172a';

                    this.chart = new Chart(document.getElementById(this.$.uid + '_chart'), {
                        type: 'bar',
                        
                        data: {
                            labels: this.labels,

                            datasets: this.datasets,
                        },
                
                        options: {
                            responsive: true,
                            maintainAspectRatio: ! this.fluidHeight,
                            aspectRatio: this.aspectRatio,
                            interaction: {
                                mode: 'index',
                                intersect: false,
                            },
                            layout: {
                                padding: {
                                    top: 8,
                                    right: 8,
                                    bottom: 0,
                                    left: 0,
                                },
                            },
                            
                            plugins: {
                                legend: {
                                    display: this.showLegend,
                                    position: 'bottom',
                                    labels: {
                                        boxWidth: 14,
                                        boxHeight: 10,
                                        color: tickColor,
                                        padding: 16,
                                        usePointStyle: false,
                                        font: {
                                            size: 13,
                                            weight: '500',
                                        },
                                    },
                                },

                                tooltip: {
                                    backgroundColor: tooltipBackground,
                                    titleColor: '#f8fafc',
                                    bodyColor: '#e2e8f0',
                                    borderColor: 'rgba(148, 163, 184, 0.22)',
                                    borderWidth: 1,
                                    padding: 12,
                                    cornerRadius: 12,
                                    displayColors: true,
                                    boxPadding: 4,
                                },
                            },
                            
                            scales: {
                                x: {
                                    beginAtZero: true,
                                    offset: true,
                                    grid: {
                                        color: gridColor,
                                        drawTicks: false,
                                        borderDash: [3, 4],
                                    },
                                    border: {
                                        display: true,
                                        color: axisColor,
                                    },
                                    ticks: {
                                        color: tickColor,
                                        padding: 8,
                                        minRotation: this.showAllXTicks ? 45 : 0,
                                        maxRotation: this.showAllXTicks ? 45 : 0,
                                        autoSkip: ! this.showAllXTicks,
                                        autoSkipPadding: 18,
                                        font: {
                                            size: this.showAllXTicks ? 10 : 13,
                                            weight: '500',
                                        },
                                    },
                                },

                                y: {
                                    beginAtZero: true,
                                    grace: '8%',
                                    grid: {
                                        color: gridColor,
                                        drawTicks: false,
                                        borderDash: [3, 4],
                                    },
                                    border: {
                                        display: true,
                                        color: axisColor,
                                    },
                                    ticks: {
                                        color: tickColor,
                                        padding: 10,
                                        precision: 0,
                                        maxTicksLimit: 6,
                                        font: {
                                            size: 13,
                                            weight: '500',
                                        },
                                    },
                                }
                            }
                        }
                    });
                }
            }
        });
    </script>
@endPushOnce
