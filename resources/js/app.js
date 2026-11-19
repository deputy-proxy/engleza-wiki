import './bootstrap';
// NOTE: Do not import Alpine here. Livewire v4 bundles Alpine internally.
// Importing a second Alpine instance causes "Detected multiple instances of Alpine"
// which breaks wire:click, wire:model and other Livewire directives.

import { speakingPractice } from './speaking-practice';

// Livewire v4 owns the Alpine instance. Register without importing a second runtime.
document.addEventListener('alpine:init', () => {
    window.Alpine.data('speakingPractice', speakingPractice);
});
