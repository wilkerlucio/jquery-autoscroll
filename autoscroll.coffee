# jQuery autoscroll plugin
# 
# Copyright 2010 Wilker Lucio <wilkerlucio@gmail.com>
# 
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
# 
#     http://www.apache.org/licenses/LICENSE-2.0
# 
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.

AUTOSCROLL_X: 1
AUTOSCROLL_Y: 2
AUTOSCROLL_BOTH: 3

(($) ->
	$.autoscroll: {}
	
	class $.autoscroll.Easemove
		constructor: ->
			@current_point: 0
			@end_point: 0
			@running: false
			@speed: 0.07

			@onmove: ->
		
		set_end_point: (point) ->
			@end_point: point
			
			return if @running
			
			@running: true
			@move()
		
		move: ->
			distance: @end_point - @current_point
			move: distance * @speed
			
			@current_point += move
			@onmove @current_point
			
			if Math.round(@current_point) != @end_point
				setTimeout (@move <- this), 20
			else
				@running: false
	
	$.autoscroll.zip: (callback, items...) ->
		results: []
		
		for i in [0...items[0].length]
			line: []
			
			for x in [0...items.length]
				line.push items[x][i]
			
			results.push callback.apply(this, line)
		
		results
	
	$.fn.autoscroll: (mode, degree_window, speed) ->
		mode: or AUTOSCROLL_BOTH
		degree_window: or [20, 20]
		speed: or 0.07
		
		directions: ['left', 'top']
		
		@each ->
			container: $(this)
			inner_container: container.children ":first"
			
			ava_size: [container.width(), container.height()]
			
			# workaround to make possible to determine real size of content
			position: inner_container.css 'position'
			
			inner_container.css 'position', 'absolute'
			
			real_size: [inner_container.width(), inner_container.height()]
			
			# back to previous state
			inner_container.css 'position', position
			
			offset: container.offset()
			offset: [offset.left, offset.top]
			
			easemove: [];
			
			jQuery.each directions, (i, v) ->
				obj: new jQuery.autoscroll.Easemove()
				obj.speed: speed;
				
				obj.onmove: (position) ->
					inner_container.css 'margin-' + v, -position
				
				easemove.push obj
			
			container.mousemove (event) ->
				mouse: [event.pageX, event.pageY]

				jQuery.autoscroll.zip(((m, o, a, r, e, w, t) ->
					return unless t & mode

					d: m - o
					d: Math.min(Math.max(d - w, 0), a - w * 2)

					f: d / (a - w * 2)
					real: f * (r - a)

					e.set_end_point real
				), mouse, offset, ava_size, real_size, easemove, degree_window, [AUTOSCROLL_X, AUTOSCROLL_Y])
)(jQuery)