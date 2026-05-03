<v-charts-line {{ $attributes }}></v-charts-line>

@pushOnce('scripts')
    <!-- SEO Vue Component Template -->
    <script
        type="text/x-template"
        id="v-charts-line-template"
    >
        <canvas
            :id="$.uid + '_chart'"
            class="flex w-full items-end"
            :class="{ 'h-full': fluidHeight }"
            :style="fluidHeight ? '' : 'aspect-ratio:' + aspectRatio + '/1'"
        ></canvas>
    </script>

    <script type="module">
        app.component('v-charts-line', {
            template: '#v-charts-line-template',

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

                multiAxis: {
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
                    const gridColor = isDark ? 'rgba(148, 163, 184, 0.16)' : 'rgba(148, 163, 184, 0.22)';
                    const tickColor = isDark ? '#94a3b8' : '#64748b';
                    const tooltipBackground = isDark ? '#020617' : '#0f172a';

                    this.chart = new Chart(document.getElementById(this.$.uid + '_chart'), {
                        type: 'line',
                        
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

                            elements: {
                                line: {
                                    borderCapStyle: 'round',
                                    borderJoinStyle: 'round',
                                },
                            },
                            
                            plugins: {
                                legend: {
                                    display: this.showLegend,
                                    position: 'bottom',
                                    labels: {
                                        boxWidth: 8,
                                        boxHeight: 8,
                                        color: tickColor,
                                        padding: 18,
                                        usePointStyle: true,
                                        pointStyle: 'circle',
                                        font: {
                                            size: 12,
                                            weight: '600',
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
                                    grid: {
                                        color: gridColor,
                                        drawTicks: false,
                                    },
                                    border: {
                                        display: false,
                                    },
                                    ticks: {
                                        color: tickColor,
                                        padding: 10,
                                        minRotation: this.showAllXTicks ? 45 : 0,
                                        maxRotation: this.showAllXTicks ? 45 : 0,
                                        autoSkip: ! this.showAllXTicks,
                                        autoSkipPadding: 20,
                                        font: {
                                            size: this.showAllXTicks ? 10 : 12,
                                            weight: '600',
                                        },
                                    },
                                },

                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: gridColor,
                                        drawTicks: false,
                                    },
                                    border: {
                                        display: false,
                                    },
                                    ticks: {
                                        color: tickColor,
                                        padding: 10,
                                    },
                                },

                                ...(this.multiAxis ? {
                                    y1: {
                                        beginAtZero: true,
                                        position: 'right',
                                        border: {
                                            display: false,
                                        },
                                        grid: {
                                            drawOnChartArea: false,
                                        },
                                        ticks: {
                                            color: tickColor,
                                            padding: 10,
                                        },
                                    },
                                } : {})
                            }
                        }
                    });
                }
            }
        });
    </script>
@endPushOnce
