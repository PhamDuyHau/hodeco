const $ = window.jQuery;
function registerJQueryPlugins() {
  if ($.fn.fadeOutAndRemove) return;
  $.fn.fadeOutAndRemove = function(speed = 400) {
    return this.fadeOut(speed, function() {
      $(this).remove();
    });
  };
  $.fn.serializeObject = function() {
    const obj = {};
    const array = this.serializeArray();
    $.each(array, function() {
      const name = this.name;
      const value = this.value || "";
      const forceArray = name.endsWith("[]");
      const keys = name.match(/[^\[\]]+/g) || [name];
      let current = obj;
      for (let i = 0; i < keys.length; i++) {
        const key = keys[i];
        const isLast = i === keys.length - 1;
        if (isLast) {
          if (forceArray) {
            if (!Array.isArray(current[key])) {
              current[key] = current[key] !== void 0 ? [current[key]] : [];
            }
            current[key].push(value);
          } else if (current[key] !== void 0) {
            if (!Array.isArray(current[key])) {
              current[key] = [current[key]];
            }
            current[key].push(value);
          } else {
            current[key] = value;
          }
        } else {
          const nextKey = keys[i + 1];
          const isNextNumeric = !isNaN(parseInt(nextKey, 10));
          if (current[key] === void 0) {
            current[key] = isNextNumeric ? [] : {};
          }
          current = current[key];
        }
      }
    });
    return obj;
  };
}
registerJQueryPlugins();
//# sourceMappingURL=jquery-plugins.Cyn_OIe4.js.map
