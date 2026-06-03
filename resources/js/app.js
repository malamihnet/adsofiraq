import './bootstrap';
import './credits-mentions-vanilla';
import 'plyr/dist/plyr.css';
import Alpine from 'alpinejs';
import homeHeroSlider from './home-hero-slider';
import taxonomyMultiselect from './taxonomy-multiselect';
import campaignVideosManager from './campaign-videos-manager';
import setupPlyr from './plyr-init';
import campaignGallery from './campaign-gallery';
import { initCreditsMentions } from './credits-mentions-vanilla';

window.Alpine = Alpine;
Alpine.data('homeHeroSlider', homeHeroSlider);
Alpine.data('taxonomyMultiselect', taxonomyMultiselect);
Alpine.data('campaignVideosManager', campaignVideosManager);
Alpine.data('campaignGallery', campaignGallery);

function bootPageScripts() {
    setupPlyr();
    initCreditsMentions();
}

try {
    Alpine.start();
} catch (error) {
    console.error('[app] Alpine.start() failed; credits mentions still load separately.', error);
}

bootPageScripts();
document.addEventListener('DOMContentLoaded', bootPageScripts);
