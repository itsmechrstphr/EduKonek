<?php
// Reusable chart templates using Chart.js
// Usage: include this file and call render_bar_chart(), render_line_chart(), or render_ocean_wave_chart()

function render_bar_chart($canvasId, $labels, $data, $colors, $title = '') {
    ?>
    <div class="ocean-bar-container card p-4 shadow rounded" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 20px; overflow: hidden; position: relative;">
        <!-- Animated subtle wave background -->
        <div class="ocean-waves-subtle">
            <div class="wave-subtle wave-subtle1"></div>
            <div class="wave-subtle wave-subtle2"></div>
        </div>
        
        <div style="position: relative; z-index: 10;">
            <?php if ($title): ?>
                <h3 class="text-lg font-semibold mb-3" style="color: white; text-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                    <i class="fas fa-chart-bar me-2"></i><?php echo htmlspecialchars($title); ?>
                </h3>
            <?php endif; ?>
            <canvas id="<?php echo htmlspecialchars($canvasId); ?>" style="max-height: 350px; height: 350px;"></canvas>
        </div>
    </div>
    
    <style>
        .ocean-bar-container {
            position: relative;
            min-height: 470px;
            height: 100%;
        }
        
        .ocean-waves-subtle {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            opacity: 0.1;
            pointer-events: none;
        }
        
        .wave-subtle {
            position: absolute;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.3) 50%, transparent 70%);
            animation: wave-animation 20s linear infinite;
        }
        
        .wave-subtle1 {
            top: -100%;
            left: -50%;
            animation-duration: 20s;
        }
        
        .wave-subtle2 {
            top: -120%;
            left: -30%;
            animation-duration: 25s;
            animation-delay: -10s;
        }
    </style>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('<?php echo $canvasId; ?>');
            if (ctx) {
                // Create ocean-inspired gradient colors for bars
                const oceanColors = [
                    'rgba(102, 226, 255, 0.9)',  // Bright cyan
                    'rgba(255, 206, 86, 0.9)',   // Warm yellow
                    'rgba(255, 99, 132, 0.9)'    // Coral pink
                ];
                
                const oceanBorders = [
                    'rgba(102, 226, 255, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(255, 99, 132, 1)'
                ];

                new Chart(ctx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode($labels); ?>,
                        datasets: [{
                            label: 'Count',
                            data: <?php echo json_encode($data); ?>,
                            backgroundColor: oceanColors,
                            borderColor: oceanBorders,
                            borderWidth: 2,
                            borderRadius: 8,
                            borderSkipped: false
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.2)',
                                    lineWidth: 1
                                },
                                ticks: {
                                    color: 'rgba(255, 255, 255, 0.9)',
                                    font: {
                                        size: 12,
                                        weight: '600'
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: 'rgba(255, 255, 255, 0.9)',
                                    font: {
                                        size: 12,
                                        weight: '600'
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: { 
                                display: false 
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleColor: 'rgba(255, 255, 255, 1)',
                                bodyColor: 'rgba(255, 255, 255, 0.9)',
                                borderColor: 'rgba(102, 226, 255, 1)',
                                borderWidth: 2,
                                padding: 12,
                                displayColors: true,
                                callbacks: {
                                    label: function(context) {
                                        return context.label + ': ' + context.parsed.y + ' user' + (context.parsed.y !== 1 ? 's' : '');
                                    }
                                }
                            }
                        },
                        animation: {
                            duration: 1500,
                            easing: 'easeInOutQuart'
                        }
                    }
                });
            }
        });
    </script>
    <?php
}

function render_line_chart($canvasId, $labels, $data, $title = '') {
    ?>
    <div class="ocean-wave-container card p-4 shadow rounded" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 20px; overflow: hidden; position: relative;">
        <!-- Animated wave background -->
        <div class="ocean-waves">
            <div class="wave wave1"></div>
            <div class="wave wave2"></div>
            <div class="wave wave3"></div>
        </div>
        
        <div style="position: relative; z-index: 10;">
            <?php if ($title): ?>
                <h3 class="text-lg font-semibold mb-3" style="color: white; text-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                    <i class="fas fa-chart-area me-2"></i><?php echo htmlspecialchars($title); ?>
                </h3>
            <?php endif; ?>
            <canvas id="<?php echo htmlspecialchars($canvasId); ?>" style="max-height: 350px; height: 350px;"></canvas>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('<?php echo $canvasId; ?>');
            if (ctx) {
                // Create gradient for ocean effect
                const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 350);
                gradient.addColorStop(0, 'rgba(102, 226, 255, 0.9)');
                gradient.addColorStop(0.5, 'rgba(64, 196, 255, 0.7)');
                gradient.addColorStop(1, 'rgba(25, 150, 255, 0.5)');
                
                const borderGradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 350);
                borderGradient.addColorStop(0, 'rgba(255, 255, 255, 1)');
                borderGradient.addColorStop(1, 'rgba(200, 240, 255, 0.8)');

                new Chart(ctx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode($labels); ?>,
                        datasets: [{
                            label: 'Registrations',
                            data: <?php echo json_encode($data); ?>,
                            backgroundColor: gradient,
                            borderColor: borderGradient,
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 6,
                            pointHoverRadius: 8,
                            pointBackgroundColor: 'rgba(255, 255, 255, 1)',
                            pointBorderColor: 'rgba(102, 226, 255, 1)',
                            pointBorderWidth: 2,
                            pointHoverBackgroundColor: 'rgba(255, 255, 255, 1)',
                            pointHoverBorderColor: 'rgba(102, 226, 255, 1)',
                            pointHoverBorderWidth: 3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.2)',
                                    lineWidth: 1
                                },
                                ticks: {
                                    color: 'rgba(255, 255, 255, 0.9)',
                                    font: {
                                        size: 12,
                                        weight: '600'
                                    },
                                    callback: function(value) {
                                        return value + ' users';
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.1)',
                                    lineWidth: 1
                                },
                                ticks: {
                                    color: 'rgba(255, 255, 255, 0.9)',
                                    font: {
                                        size: 11,
                                        weight: '500'
                                    },
                                    maxRotation: 45,
                                    minRotation: 45
                                }
                            }
                        },
                        plugins: {
                            legend: { 
                                display: true,
                                labels: {
                                    color: 'white',
                                    font: {
                                        size: 14,
                                        weight: '600'
                                    },
                                    padding: 15
                                }
                            },
                            tooltip: {
                                enabled: true,
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleColor: 'rgba(255, 255, 255, 1)',
                                bodyColor: 'rgba(255, 255, 255, 0.9)',
                                borderColor: 'rgba(102, 226, 255, 1)',
                                borderWidth: 2,
                                padding: 12,
                                displayColors: true,
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': ' + context.parsed.y + ' user' + (context.parsed.y !== 1 ? 's' : '');
                                    },
                                    title: function(context) {
                                        return 'Month: ' + context[0].label;
                                    }
                                }
                            }
                        },
                        animation: {
                            duration: 2000,
                            easing: 'easeInOutQuart'
                        }
                    }
                });
            }
        });
    </script>
    <?php
}

function render_ocean_wave_chart($canvasId, $labels, $data, $title = '') {
    ?>
    <div class="ocean-wave-container card p-4 shadow rounded" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 20px; overflow: hidden; position: relative;">
        <!-- Animated wave background -->
        <div class="ocean-waves">
            <div class="wave wave1"></div>
            <div class="wave wave2"></div>
            <div class="wave wave3"></div>
        </div>
        
        <div style="position: relative; z-index: 10;">
            <?php if ($title): ?>
                <h3 class="text-lg font-semibold mb-3" style="color: white; text-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                    <i class="fas fa-chart-line me-2"></i><?php echo htmlspecialchars($title); ?>
                </h3>
            <?php endif; ?>
            <canvas id="<?php echo htmlspecialchars($canvasId); ?>" style="max-height: 400px;"></canvas>
        </div>
    </div>
    
    <style>
        .ocean-wave-container {
            position: relative;
            min-height: 500px;
        }
        
        .ocean-waves {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            opacity: 0.15;
            pointer-events: none;
        }
        
        .wave {
            position: absolute;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.3) 50%, transparent 70%);
            animation: wave-animation 15s linear infinite;
        }
        
        .wave1 {
            top: -100%;
            left: -50%;
            animation-duration: 18s;
            animation-delay: 0s;
        }
        
        .wave2 {
            top: -120%;
            left: -30%;
            animation-duration: 22s;
            animation-delay: -5s;
        }
        
        .wave3 {
            top: -140%;
            left: -70%;
            animation-duration: 25s;
            animation-delay: -10s;
        }
        
        @keyframes wave-animation {
            0% {
                transform: rotate(0deg) translateY(0) scale(1);
            }
            50% {
                transform: rotate(180deg) translateY(20px) scale(1.1);
            }
            100% {
                transform: rotate(360deg) translateY(0) scale(1);
            }
        }
    </style>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('<?php echo $canvasId; ?>');
            if (ctx) {
                // Create gradient for ocean wave effect
                const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 400);
                gradient.addColorStop(0, 'rgba(102, 226, 255, 0.9)');
                gradient.addColorStop(0.5, 'rgba(64, 196, 255, 0.7)');
                gradient.addColorStop(1, 'rgba(25, 150, 255, 0.5)');
                
                const borderGradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 400);
                borderGradient.addColorStop(0, 'rgba(255, 255, 255, 1)');
                borderGradient.addColorStop(1, 'rgba(200, 240, 255, 0.8)');

                new Chart(ctx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode($labels); ?>,
                        datasets: [{
                            label: 'Registrations',
                            data: <?php echo json_encode($data); ?>,
                            backgroundColor: gradient,
                            borderColor: borderGradient,
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 6,
                            pointHoverRadius: 8,
                            pointBackgroundColor: 'rgba(255, 255, 255, 1)',
                            pointBorderColor: 'rgba(102, 226, 255, 1)',
                            pointBorderWidth: 2,
                            pointHoverBackgroundColor: 'rgba(255, 255, 255, 1)',
                            pointHoverBorderColor: 'rgba(102, 226, 255, 1)',
                            pointHoverBorderWidth: 3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.2)',
                                    lineWidth: 1
                                },
                                ticks: {
                                    color: 'rgba(255, 255, 255, 0.9)',
                                    font: {
                                        size: 12,
                                        weight: '600'
                                    },
                                    callback: function(value) {
                                        return value + ' users';
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.1)',
                                    lineWidth: 1
                                },
                                ticks: {
                                    color: 'rgba(255, 255, 255, 0.9)',
                                    font: {
                                        size: 11,
                                        weight: '500'
                                    },
                                    maxRotation: 45,
                                    minRotation: 45
                                }
                            }
                        },
                        plugins: {
                            legend: { 
                                display: true,
                                labels: {
                                    color: 'white',
                                    font: {
                                        size: 14,
                                        weight: '600'
                                    },
                                    padding: 15
                                }
                            },
                            tooltip: {
                                enabled: true,
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleColor: 'rgba(255, 255, 255, 1)',
                                bodyColor: 'rgba(255, 255, 255, 0.9)',
                                borderColor: 'rgba(102, 226, 255, 1)',
                                borderWidth: 2,
                                padding: 12,
                                displayColors: true,
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': ' + context.parsed.y + ' user' + (context.parsed.y !== 1 ? 's' : '');
                                    },
                                    title: function(context) {
                                        return 'Date: ' + context[0].label;
                                    }
                                }
                            }
                        },
                        animation: {
                            duration: 2000,
                            easing: 'easeInOutQuart'
                        }
                    }
                });
            }
        });
    </script>
    <?php
}
?>