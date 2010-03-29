var AUTOSCROLL_BOTH, AUTOSCROLL_X, AUTOSCROLL_Y;
// jQuery autoscroll plugin
//
// Copyright 2010 Wilker Lucio <wilkerlucio@gmail.com>
//
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at
//
//     http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.
AUTOSCROLL_X = 1;
AUTOSCROLL_Y = 2;
AUTOSCROLL_BOTH = 3;
(function($) {
  $.autoscroll = {};
  $.autoscroll.Easemove = function Easemove() {
    this.current_point = 0;
    this.end_point = 0;
    this.running = false;
    this.speed = 0.07;
    this.onmove = function onmove() {    };
    return this;
  };
  $.autoscroll.Easemove.prototype.set_end_point = function set_end_point(point) {
    this.end_point = point;
    if (this.running) {
      return null;
    }
    this.running = true;
    return this.move();
  };
  $.autoscroll.Easemove.prototype.move = function move() {
    var distance, move;
    distance = this.end_point - this.current_point;
    move = distance * this.speed;
    this.current_point += move;
    this.onmove(this.current_point);
    if (Math.round(this.current_point) !== this.end_point) {
      return setTimeout(((function(func, obj, args) {
        return function() {
          return func.apply(obj, args.concat(Array.prototype.slice.call(arguments, 0)));
        };
      }(this.move, this, []))), 20);
    } else {
      this.running = false;
      return this.running;
    }
  };
  $.autoscroll.zip = function zip(callback) {
    var _a, _b, _c, _d, _e, _f, i, items, line, results, x;
    items = Array.prototype.slice.call(arguments, 1, arguments.length - 0);
    results = [];
    _b = 0; _c = items[0].length;
    for (_a = 0, i = _b; (_b <= _c ? i < _c : i > _c); (_b <= _c ? i += 1 : i -= 1), _a++) {
      line = [];
      _e = 0; _f = items.length;
      for (_d = 0, x = _e; (_e <= _f ? x < _f : x > _f); (_e <= _f ? x += 1 : x -= 1), _d++) {
        line.push(items[x][i]);
      }
      results.push(callback.apply(this, line));
    }
    return results;
  };
  $.fn.autoscroll = function autoscroll(mode, degree_window, speed) {
    var directions;
    mode = mode || AUTOSCROLL_BOTH;
    degree_window = degree_window || [20, 20];
    speed = speed || 0.07;
    directions = ['left', 'top'];
    return this.each(function() {
      var ava_size, container, easemove, inner_container, offset, position, real_size;
      container = $(this);
      inner_container = container.children(":first");
      ava_size = [container.width(), container.height()];
      // workaround to make possible to determine real size of content
      position = inner_container.css('position');
      inner_container.css('position', 'absolute');
      real_size = [inner_container.width(), inner_container.height()];
      // back to previous state
      inner_container.css('position', position);
      offset = container.offset();
      offset = [offset.left, offset.top];
      easemove = [];
      jQuery.each(directions, function(i, v) {
        var obj;
        obj = new jQuery.autoscroll.Easemove();
        obj.speed = speed;
        obj.onmove = function onmove(position) {
          return inner_container.css('margin-' + v, -position);
        };
        return easemove.push(obj);
      });
      return container.mousemove(function(event) {
        var mouse;
        mouse = [event.pageX, event.pageY];
        return jQuery.autoscroll.zip((function(m, o, a, r, e, w, t) {
          var d, f, real;
          if (!(t & mode)) {
            return null;
          }
          d = m - o;
          d = Math.min(Math.max(d - w, 0), a - w * 2);
          f = d / (a - w * 2);
          real = f * (r - a);
          return e.set_end_point(real);
        }), mouse, offset, ava_size, real_size, easemove, degree_window, [AUTOSCROLL_X, AUTOSCROLL_Y]);
      });
    });
  };
  return $.fn.autoscroll;
})(jQuery);