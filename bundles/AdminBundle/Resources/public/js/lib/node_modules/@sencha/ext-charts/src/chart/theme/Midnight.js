Ext.define('Ext.chart.theme.Midnight', {
    extend: 'Ext.chart.theme.Base',
    singleton: true,
    alias: [
        'chart.theme.midnight',
        'chart.theme.Midnight'
    ],
    config: {
        colors: [
            '#a837ff',
            '#4ac0f2',
            '#ff4d35',
            '#ff8809',
            '#61c102',
            '#ff37ea'
        ],

        chart: {
            defaults: {
                captions: {
                    title: {
                        docked: 'top',
                        padding: 5,
                        style: {
                            textAlign: 'center',
                            fontFamily: 'default',
                            fontWeight: 'bold',
                            fillStyle: 'rgb(224, 224, 227)',
                            fontSize: 'default*1.6'
                        }
                    },
                    subtitle: {
                        docked: 'top',
                        style: {
                            textAlign: 'center',
                            fontFamily: 'default',
                            fontWeight: 'normal',
                            fillStyle: 'rgb(224, 224, 227)',
                            fontSize: 'default*1.3'
                        }
                    },
                    credits: {
                        docked: 'bottom',
                        padding: 5,
                        style: {
                            textAlign: 'left',
                            fontFamily: 'default',
                            fontWeight: 'lighter',
                            fillStyle: 'rgb(224, 224, 227)',
                            fontSize: 'default'
                        }
                    }
                },
                background: 'rgb(52, 52, 53)'
            }
        },

        axis: {
            defaults: {
                style: {
                    strokeStyle: 'rgb(224, 224, 227)'
                },
                label: {
                    fillStyle: 'rgb(224, 224, 227)'
                },
                title: {
                    fillStyle: 'rgb(224, 224, 227)'
                },
                grid: {
                    strokeStyle: 'rgb(112, 112, 115)'
                }
            }
        },

        series: {
            defaults: {
                label: {
                    fillStyle: 'rgb(224, 224, 227)'
                }
            }
        },

        sprites: {
            text: {
                fillStyle: 'rgb(224, 224, 227)'
            }
        },

        legend: {
            label: {
                fillStyle: 'white'
            },
            border: {
                lineWidth: 2,
                fillStyle: 'rgba(255, 255, 255, 0.3)',
                strokeStyle: 'rgb(150, 150, 150)'
            },
            background: 'rgb(52, 52, 53)'
        }
    }
});
