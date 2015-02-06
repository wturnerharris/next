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
        this.events();
        this.eventsElement = document.getElementById('events');
    },
    trainTemplate: [
        '<div class="line-route_id"></div>',
        '<div class="time-stop_id"></div>',
        '<div class="dest-stop_id"></div>',
    ],
    setNearest: function () {
        var d = new Date();
        for(var i = 0; i < 2; i++) {
            var times = app.trainTimes[i],
                first = times.shift(),
                train = first.next_train,
                trainEl = document.createElement('DIV'),
                full_stop_id = first.stop_id+(i===1?'N':'S'),
                tpl = app.trainTemplate.join('').replace(/stop_id/g, full_stop_id),
                min = Math.floor((train.ts - d.getTime()/1000 )/60);
                
            trainEl.id = "Train-"+full_stop_id;
            trainEl.innerHTML = tpl.replace('route_id', train.route_id);
            
            Col = document.querySelectorAll('.columns')[0].querySelectorAll('.col')[i];
            Col.innerHTML = '';
            Col.appendChild(trainEl);
            
            Col.querySelectorAll('.line-'+train.route_id)[0].innerHTML = train.route_id;
            Col.querySelectorAll('.time-'+full_stop_id)[0].innerHTML = (min===0?'&lt; 1':min) + ' Mins';
            Col.querySelectorAll('.dest-'+full_stop_id)[0].innerHTML = (i===1?'Uptown':'Downtown');
            
            for(var j=0,next; j < 3,next=times[j]; j++) {
                var train = next.next_train,
                    trainEl = document.createElement('DIV'),
                    full_stop_id = next.stop_id+(i===1?'N':'S'),
                    tpl = app.trainTemplate.slice(0,2).join('').replace(/stop_id/g, full_stop_id),
                    min = Math.floor((train.ts - d.getTime()/1000 )/60);
                
                trainEl.id = "Train-"+full_stop_id;
                trainEl.innerHTML = tpl.replace('route_id', train.route_id);
            
                Col = document.querySelectorAll('.columns')[1].querySelectorAll('.col')[i];
                if ( j === 0 ) {
                    Col.innerHTML = '<div class="destiny">'+(i===1?'Uptown':'Downtown')+'</div>';
                }
                Col.appendChild(trainEl);
            
                Col.querySelectorAll('.line-'+train.route_id)[0].innerHTML = train.route_id;
                Col.querySelectorAll('.time-'+full_stop_id)[0].innerHTML = (min===0?'&lt; 1':min) + ' Mins';
            }
        }
    },
    toggleActivity: function(index) {
        var self = this, 
            activity = document.querySelectorAll('.activity');

        if ( typeof(self.currentActivity) == 'undefined' || self.currentActivity !== index ) {
            self.eventsElement.className = "app";
            activity[ self.currentActivity||0 ].className = "activity";
        }
        self.currentActivity = index;

        setTimeout( function (){
            self.eventsElement.className = "app ready";
            activity[ index ].className = "activity active";
        }, 250);
    },
    events: function() {
        var self = this;
        Hammer( document.getElementById('events') )
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
            var geolocationError = "GEOLOCATION_ERROR";

    		if(navigator.geolocation) {
    			navigator.geolocation.getCurrentPosition( app.callbacks.geolocation, function (e) {
    				navigator.notification.alert(geolocationError);
    			});
    		} else {
    			navigator.notification.alert(geolocationError);
    		}
        
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
		getTrains: function (data){
			if ( data && data.payload.length ) {
                app.trainTimes.push(data.payload);
			} else {
			    navigator.notification.alert('UPDATE_FAILED');
			}
            if ( app.trainTimes.length === 2 ) {
                app.setNearest();
            }
		},
		geolocation: function (geo) {
			app.Origin = {
				lat : geo.coords.latitude,
				lon : geo.coords.longitude
			};
            for(var i = 0; i < 2; i++) {
    			_.get('http://www.turnerharris.com/nearest/next.php', {
    				action: 'getTrains',
    				lat : geo.coords.latitude,
    				lon : geo.coords.longitude,
    				dir : i
    			}, app.callbacks.getTrains );
            }
		}
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
}
