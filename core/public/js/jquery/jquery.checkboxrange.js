
(function($) {
  $.fn.enableCheckboxRangeSelection = function(opts) {
    var defaults = {
      onRangeSelect: null
      };
    var options = $.extend({}, defaults, opts);
    var lastCheckbox = null;
    var $spec = this;
    $spec.unbind("click.checkboxrange");
    $spec.bind("click.checkboxrange", function(e) {
      if (lastCheckbox != null && (e.shiftKey || e.metaKey)) {
        $spec.slice(
          Math.min($spec.index(lastCheckbox), $spec.index(e.target)),
          Math.max($spec.index(lastCheckbox), $spec.index(e.target)) + 1
        ).attr({checked: e.target.checked ? "checked" : ""});

        if ($.isFunction(options.onRangeSelect)) {
          options.onRangeSelect.call();
        }
      }
      lastCheckbox = e.target;
    });
  };
})(jQuery);
