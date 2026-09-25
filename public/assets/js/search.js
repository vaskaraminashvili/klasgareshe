window.KidzioSearch = {
  norm: function (value) {
    return (value || '').toLowerCase().normalize('NFC').replace(/\s+/g, ' ').trim();
  },
  matches: function (haystack, query) {
    var needle = this.norm(query);
    if (!needle) return true;
    var hay = this.norm(haystack);
    return needle.split(' ').every(function (token) {
      return token === '' || hay.indexOf(token) !== -1;
    });
  },
  record: function (query) {
    var root = document.querySelector('[wire\\:id]');
    if (!root || !window.Livewire) return;
    var component = Livewire.find(root.getAttribute('wire:id'));
    if (component) component.call('recordSearch', query);
  }
};
