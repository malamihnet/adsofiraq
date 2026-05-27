import './bootstrap';
import 'plyr/dist/plyr.css';
import Alpine from 'alpinejs';
import homeHeroSlider from './home-hero-slider';
import taxonomyMultiselect from './taxonomy-multiselect';
import campaignVideosManager from './campaign-videos-manager';
import setupPlyr from './plyr-init';

window.Alpine = Alpine;
Alpine.data('homeHeroSlider', homeHeroSlider);
Alpine.data('taxonomyMultiselect', taxonomyMultiselect);
Alpine.data('campaignVideosManager', campaignVideosManager);
Alpine.start();

document.addEventListener('DOMContentLoaded', setupPlyr);
