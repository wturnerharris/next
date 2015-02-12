/*
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 */
var app = {
    trainTimes: [],
    initialize: function() {
        this.eventsElement = document.getElementById('events');
        this.loaderElement = document.getElementById('loading');
        this.events();
    },
    trainTemplate: [
        '<div class="line-route_id line-stop_id"></div>',
        '<div class="time-stop_id"></div>',
        '<div class="dest-stop_id"></div>',
    ],
    trainFactory: function(item, date, col, dir, dest){
        var train = item.next_train,
            trainEl = document.createElement('DIV'),
            stop_id = item.stop_id+(dir===1?'N':'S'),
            tpl = app.trainTemplate.join('').replace(/stop_id/g, stop_id),
            min = Math.floor((train.ts - date.getTime()/1000 )/60); 
            
        trainEl.id = "Train-"+stop_id;
        trainEl.className = 'train';
        trainEl.innerHTML = tpl.replace('route_id', train.route_id);
    
        Col = document.querySelectorAll('.columns')[col].querySelectorAll('.col')[dir];
        if (dest) Col.innerHTML = '<div class="destiny">'+(dir===1?'Uptown':'Downtown')+'</div>';
        Col.appendChild(trainEl);
    
        trainEl.querySelectorAll('.line-'+stop_id)[0].innerHTML = train.route_id;
        trainEl.querySelectorAll('.time-'+stop_id)[0].innerHTML = (min<1?'&lt; 1':min) + ' Mins';
        trainEl.querySelectorAll('.dest-'+stop_id)[0].innerHTML = train.stop_name;
    },
    setNearest: function () {
        var d = new Date();
        for(var i = 0; i < 2; i++) {
            var times = app.trainTimes[i],
                first = times.shift();

            // activity 1 
            app.trainFactory(first, d, 0, i, true);

            // activity 2
            for(var j=0,next; j < 3,next=times[j]; j++) {
                app.trainFactory(next, d, 1, i, j<1);
            }
        }
        app.trainLoader(false);
    },
    update: function (soft) {
        if (!soft) this.handlers.geolocation();
        else this.handlers.getTrains();
    },
    loading: false,
    trainLoader: function(on) {
        if (this.loading === on) return;
        this.loading = on;
        $(this.eventsElement)[on?'removeClass':'addClass']('reloaded');
        $(this.loaderElement)[on?'addClass':'removeClass']('active');
    },
    toggleActivity: function(index) {
        var self = this, 
            preventTimeout = false,
            activity = $('.activity');

        // scroll instead
        if (self.currentActivity === 1 && activity[ self.currentActivity ].scrollTop >= 1 ) return;

        if ( typeof(self.currentActivity) == 'undefined' || self.currentActivity !== index ) {
            self.eventsElement.className = "app";
            activity[ self.currentActivity||0 ].className = "activity";
        } else if ( self.currentActivity === 0 ) {
            self.update(false);
        } else if ( self.currentActivity === 1 ) {

        }
        self.currentActivity = index;

        setTimeout( function (){
            self.eventsElement.className = "app ready";
            activity[ index ].className = "activity active";
        }, 250);
    },
    events: function() {
        var self = this;
        Hammer( self.eventsElement )
            .on('tap', function (e){
    			self.trainLoader(false);
            })
            .on('swipe', self.handlers.swipe)
            .get('swipe')
            .set({ direction: Hammer.DIRECTION_ALL });
        document.addEventListener('deviceready', self.handlers.onDeviceReady, false);
    },
    handlers: {
        onDeviceReady: function() {
            app.toggleActivity(0);
            app.handlers.geolocation();
        },
        geolocation: function() {
            var geolocationError = function (){
                navigator.notification.alert("GEOLOCATION_ERROR");
            };

    		if(navigator.geolocation) {
    			navigator.geolocation.getCurrentPosition( app.callbacks.geolocation, geolocationError);
    		} else {
    			geolocationError();
    		}
        },
        getTrains: function() {
            app.trainLoader(true);
            app.trainTimes = [];
			_.get('http://www.turnerharris.com/nearest/next.php', {
				action: 'getTrains',
				lat : app.Origin.lat,
				lon : app.Origin.lon
			}, app.callbacks.getTrains);
        },
        swipe: function (event) {
            var direction
            switch(event.direction) {
            case Hammer.DIRECTION_LEFT:
                break;
            case Hammer.DIRECTION_RIGHT:
                break;
            case Hammer.DIRECTION_UP:
                app.toggleActivity(1);
                break;
            case Hammer.DIRECTION_DOWN:
                app.toggleActivity(0);
                break;
            }
        }
    },
	callbacks: {
		geolocation: function (geo) {
			app.Origin = {
				lat : geo.coords.latitude,
				lon : geo.coords.longitude
			};
            app.handlers.getTrains();
		},
		getTrains: function (data){
			app.trainLoader(false);
			if ( data && data.payload.length ) {
                app.trainTimes = data.payload;
			} else {
			    navigator.notification.alert('UPDATE_FAILED');
			}
            if ( app.trainTimes.length === 2 ) {
                app.setNearest();
            }
		},
	}
};
var _ = {
	get: function(url, data, callback){
        var xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4) {
                callback( JSON.parse(xhr.responseText) );
            }
        };
        xhr.open("GET", url+'?'+_.toQueryString(data), true);
        xhr.send();
	},
    toQueryString: function(obj, prefix) {
        var str = [];
        for(var p in obj) {
            if (obj.hasOwnProperty(p)) {
                var k = prefix ? prefix + "[" + p + "]" : p, v = obj[p];
                str.push(typeof v == "object" ?
                _.toQueryString(v, k) :
                encodeURIComponent(k) + "=" + encodeURIComponent(v));
            }
        }
        return str.join("&");
    }
};