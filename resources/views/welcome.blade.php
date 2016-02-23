<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title>Home</title>

    <!-- Vendor styles -->
    <link rel="stylesheet" href="vendor/fontawesome/css/font-awesome.css"/>
    <link rel="stylesheet" href="vendor/bootstrap/dist/css/bootstrap.css"/>

    <!-- App styles -->
    <link rel="stylesheet" href="fonts/pe-icon-7-stroke/css/pe-icon-7-stroke.css"/>
    <link rel="stylesheet" href="fonts/pe-icon-7-stroke/css/helper.css"/>
    <link rel="stylesheet" href="styles/style.css">
    <style>
        /* Charts */
        .flot-chart {
            display: block;
            height: auto;
            position: relative;
        }
    </style>

</head>
<body>
<div class="content" id="app">
    <div class="row" v-for="sensor in sensors">
        <sensor inline-template :sensor="sensor">
            <div class="col-lg-12">
                <div class="hpanel">
                    <div class="panel-heading">
                        <div class="panel-tools">
                            @{{ sensor.status.status }} <span v-bind:class="statusClass"><i
                                        class="fa fa-circle"></i></span>
                        </div>
                        @{{ sensorName  }}: Information and statistics
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="row">
                                    <div class="col-xs-12 text-center">
                                        <div class="small">
                                            <i class="fa fa-bolt"></i> Today
                                        </div>
                                        <div class="m-b-md">
                                            <h1 class="font-extra-bold m-t-md m-b-xs">
                                                @{{ sensor.stats.today }}
                                            </h1>
                                            <small>kWh</small>
                                        </div>
                                        <div class="small">
                                            <i class="fa fa-bolt"></i> This Week
                                        </div>
                                        <div>
                                            <h3 class="font-extra-bold m-t-sm m-b-xs">
                                                @{{ sensor.stats.thisWeek }}
                                            </h3>
                                            <small>kWh</small>
                                        </div>
                                    </div>

                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-center small">
                                    <i class="fa fa-laptop"></i> Energy Consumption
                                </div>
                                <div class="flot-chart">
                                    <div>
                                        <canvas height="70" id="chart-@{{sensor.id}}"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="small">
                                    <i class="fa fa-bolt"></i> This Month
                                </div>
                                <div class="m-b-md">
                                    <h1 class="font-extra-bold m-t-md m-b-xs">
                                        @{{ sensor.stats.thisMonth }}
                                    </h1>
                                    <small>kWh</small>
                                </div>
                                <div class="small">
                                    <i class="fa fa-bolt"></i> Last Month
                                </div>
                                <div>
                                    <h3 class="font-extra-bold m-t-sm m-b-xs">
                                        @{{ sensor.stats.lastMonth }}
                                    </h3>
                                    <small>kWh</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="panel-footer">
                        Last update: <span>@{{ statusTime }}</span>
                    </div>
                </div>
            </div>
        </sensor>
    </div>
    <!-- Footer-->
    <footer class="footer">
        <span class="pull-right">
            &copy <a href="http://mikhailkozlov.com/">Mikhail Kozlov</a>
        </span>
        <a href="http://mikhailkozlov.com/">Home</a> | <a href="http://mikhailkozlov.com/blog/">Blog</a> | <a
                href="http://mikhailkozlov.com/portfolio/#/">Portfolio</a>
    </footer>
</div>

<!-- Vendor scripts -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/lodash.js/3.10.1/lodash.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.10.6/moment.min.js"></script>
<script src="//code.jquery.com/jquery-1.11.3.min.js"></script>
<script src="//code.jquery.com/jquery-migrate-1.2.1.min.js"></script>
<!-- Latest compiled and minified JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"
        integrity="sha512-K1qjQ+NcF2TYO/eI3M6v8EiNYZfA95pQumfvcVrTHtwQVDG+aHRqLi/ETn2uB+1JqwYqVG3LIvdm9lj6imS/pQ=="
        crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/1.0.2/Chart.min.js"></script>
<script src="https://cdn.jsdelivr.net/vue/latest/vue.js"></script>
<script src="https://cdn.firebase.com/js/client/2.3.1/firebase.js"></script>
<!-- App scripts -->
<script src="scripts/homer.js"></script>

<script>
    /**
     * energy user Bar chart
     */
    var singleBarOptions = {
        scaleBeginAtZero: true,
        scaleShowGridLines: true,
        scaleGridLineColor: "rgba(0,0,0,.05)",
        scaleGridLineWidth: 1,
        barShowStroke: true,
        barStrokeWidth: 1,
        barValueSpacing: 5,
        barDatasetSpacing: 1,
        responsive: true,
        tooltipTemplate: "<%if (label){%><%=label%>: <%}%><%= value %> kWt"
    };

    var myFirebaseRef = new Firebase("https://vivid-heat-6441.firebaseio.com/");

    // read list of sensors and setup new view
    myFirebaseRef.once("value", function (snapshot) {
        _.forEach(snapshot.val(), function (val, key) {
            var item = val;
            item.id = key;
            app.sensors.push(item);
        });
    });

    /**
     * Create Sensor component
     */
    var VueSensor = Vue.extend({
        template: '<div>A custom component!</div>',
        props: ['sensor'],
        ready: function () {
            var self = this;

//            // call chart
//            this.sensor.log = _.slice(this.sensor.log, (this.sensor.log.length - 30));
//            self.$emit('chart', this.sensor.id, this.sensor.log);

            // listen to log updates
            var logRef = myFirebaseRef.child(this.sensor.id + "/log");
            var lastMessagesQuery = logRef.limitToLast(30);
            lastMessagesQuery.on("value", function (snapshot) {
                self.$emit('chart', self.sensor.id, snapshot.val());
            });

            // stats update on sensor
            myFirebaseRef.child(this.sensor.id + "/stats").on("child_changed", function (snapshot) {
                self.$set('sensor.stats.' + snapshot.key(), snapshot.val());
            });

            // stats update
            myFirebaseRef.child(this.sensor.id + "/status").on("value", function (snapshot) {
                self.$set('sensor.status', snapshot.val());
            });
        },
        events: {
            'chart': function (id, log) {
                var labels = [],
                        data = [];

                _.forEach(log, function (n, key) {
                    var day = moment(n.label);
                    labels.push(day.format('MMM DD'));
                    data.push(n.value);
                });

                /**
                 * Data for Bar chart
                 */
                var singleBarData = {
                    labels: labels,
                    datasets: [
                        {
                            label: "This Month Consumption",
                            fillColor: "rgba(98,203,49,0.5)",
                            strokeColor: "rgba(98,203,49,0.8)",
                            highlightFill: "rgba(98,203,49,0.75)",
                            highlightStroke: "rgba(98,203,49,1)",
                            data: data
                        }
                    ]
                };

                var ctx = document.getElementById("chart-" + id).getContext("2d");
                // @TODO - we need to clear out canvas or re-write the code to only update and add new data
                var myNewChart = new Chart(ctx).Bar(singleBarData, singleBarOptions);
            }
        },
        computed: {
            statusClass: function () {
                if (this.sensor.status.status == 'On' || this.sensor.status.status == 'Charging') {
                    return 'text-success';
                }

                if (this.sensor.status.status == 'Climate On') {
                    return 'text-warning';
                }

                return 'text-default';
            },
            statusTime: function () {
                return moment(this.sensor.status.time).format('LLL');
            },
            sensorName: function () {
                var names = {
                            'evcs': 'Car Charger',
                            'acfan': 'AC Fan'
                        };
                return names[this.sensor.id];
            }
        }
    });

    /**
     * Adding Sensor componet
     */
    Vue.component('sensor', VueSensor)

    /**
     * main app
     *
     * @type {Vue}
     */
    var app = new Vue({
        // element to mount to
        el: '#app',
        // initial data
        data: {
            sensors: []
        }
    })

</script>
</body>
</html>