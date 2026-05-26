import Plyr from 'plyr';

const plyrOptions = {
    autoplay: false,
    controls: [
        'play',
        'progress',
        'current-time',
        'duration',
        'mute',
        'volume',
        'settings',
        'pip',
        'fullscreen',
    ],
    settings: ['quality', 'speed'],
    ratio: '16:9',
    youtube: {
        noCookie: true,
        rel: 0,
        showinfo: 0,
        iv_load_policy: 3,
        modestbranding: 1,
        playsinline: 1,
    },
    vimeo: {
        byline: false,
        portrait: false,
        title: false,
        speed: true,
        playsinline: true,
    },
};

const initializedPlayers = new WeakSet();

export function initPlyrPlayers(root = document) {
    root.querySelectorAll('.js-plyr-player').forEach((element) => {
        if (initializedPlayers.has(element) || element.plyr) {
            return;
        }

        const player = new Plyr(element, plyrOptions);

        initializedPlayers.add(element);
    });
}

export default function setupPlyr() {
    initPlyrPlayers();
}
